<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_LLM_Grok {

    private $plugin_slug;
    private $pdf_parser;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        // Ensure parser exists before instantiating to avoid fatal errors
        if ( class_exists( 'AICA_Plugin_Smalot_Parser' ) ) {
            $this->pdf_parser = new AICA_Plugin_Smalot_Parser($this->plugin_slug);
        }
    }

    public function llm_api_v2($system_instruction, $user_query, $history = []): string {
        $attachment_data = $this->prepare_attachments();

        $developer_text = $system_instruction . $attachment_data['file_context_text'];

        $messages = $this->format_conversation_messages(
            $attachment_data['multimodal_parts'], 
            $history, 
            $user_query
        );

        return $this->request_grok($developer_text, $messages);
    }

    public function llm_api_v3($system_instruction, $user_query, $history = []): string {
        $messages = [];
        
        foreach ($history as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'ai') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $user_query];

        return $this->request_grok($system_instruction, $messages);
    }

    private function prepare_attachments(): array {
        $file_id = get_option('ai_agent_knowledge_file_id');
        $data = ['multimodal_parts' => [], 'file_context_text' => ""];

        if (!$file_id) return $data;

        $file_path = get_attached_file($file_id);
        if (!$file_path || !file_exists($file_path)) return $data;

        $mime_type = get_post_mime_type($file_id);
        $file_size = filesize($file_path);
        $threshold = 1 * 1024 * 1024; // 1MB

        if (strpos($mime_type, 'image') !== false) {
            $data['multimodal_parts'][] = [
                'type' => 'image_url',
                'image_url' => [
                    'url'    => "data:$mime_type;base64," . base64_encode(file_get_contents($file_path)), 
                    'detail' => 'auto'
                ]
            ];
        } 
        else if ($mime_type === 'application/pdf' || $mime_type === 'text/plain') {
            if ($file_size < $threshold) {
                $text = '';
                if ($mime_type === 'application/pdf' && $this->pdf_parser) {
                    $text = $this->pdf_parser->extract_text_from_pdf($file_path);
                } else {
                    $text = file_get_contents($file_path);
                }
                
                $data['file_context_text'] = "\n\n### KNOWLEDGE BASE:\n" . mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            } else {
                $grok_file_id = $this->ensure_grok_file($file_path, $file_id);

                if ($grok_file_id && !is_wp_error($grok_file_id)) {
                    $data['multimodal_parts'][] = [
                        'type' => 'file',
                        'file' => ['file_id' => $grok_file_id]
                    ];
                }
            }
        }

        return $data;
    }

    private function format_conversation_messages($multimodal_parts, $history, $user_query): array {
        $messages = [];

        if (!empty($multimodal_parts)) {
            $content = array_merge($multimodal_parts, [['type' => 'text', 'text' => "Analyze the attached data for context."]]);
            $messages[] = ['role' => 'user', 'content' => $content];
            $messages[] = ['role' => 'assistant', 'content' => "Understood. How can I help with this?"];
        }

        foreach ($history as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'ai') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $user_query];

        return $messages;
    }

    private function request_grok($developer_text, $messages) {
        $api_key = get_option('grok_api_key');
        if ( empty( $api_key ) ) {
            return __( 'Error: Grok API key is missing. Please check plugin settings.', 'aica-plugin' );
        }

        $model = get_option('grok_model', 'grok-2-1212'); 
        $temp  = floatval(get_option('grok_temperature', '0.7'));
        
        $api_url = "https://api.x.ai/v1/chat/completions";

        $payload_messages = array_merge(
            [['role' => 'system', 'content' => $developer_text]],
            $messages
        );

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json'
            ],
            'body' => json_encode([
                'model'       => $model,
                'messages'    => $payload_messages,
                'temperature' => $temp,
                'stream'      => false
            ]),
            'timeout' => 120
        ]);

        if (is_wp_error($response)) {
            return __( 'Connection Error: ', 'aica-plugin' ) . $response->get_error_message();
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            $msg = $body['error']['message'] ?? __( 'Unknown error', 'aica-plugin' );
            return __( 'Grok Error: ', 'aica-plugin' ) . $msg;
        }
        
        return $body['choices'][0]['message']['content'] ?? __( 'Error: No text returned.', 'aica-plugin' );
    }

    private function ensure_grok_file($file_path, $wp_id) {
        $key = 'aica_grok_f_' . $wp_id;
        $id  = get_transient($key);
        if ($id) return $id;

        $api_key = get_option('grok_api_key');
        if ( empty( $api_key ) ) return false;

        $boundary = wp_generate_password(24);
        
        $payload = "--$boundary\r\n";
        $payload .= "Content-Disposition: form-data; name=\"purpose\"\r\n\r\nattachment_search\r\n";
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Disposition: form-data; name=\"file\"; filename=\"" . esc_attr( basename($file_path) ) . "\"\r\n";
        $payload .= "Content-Type: application/octet-stream\r\n\r\n";
        $payload .= file_get_contents($file_path) . "\r\n";
        $payload .= "--$boundary--\r\n";

        $response = wp_remote_post("https://api.x.ai/v1/files", [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary
            ],
            'body'    => $payload,
            'timeout' => 120
        ]);

        if (is_wp_error($response)) return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['id'])) {
            set_transient($key, $body['id'], 1 * HOUR_IN_SECONDS);
            return $body['id'];
        }

        return false;
    }
}