<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_LLM_OpenAI {
    private $plugin_slug;
    private $pdf_parser;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        // Check for class existence to prevent fatal errors
        if ( class_exists( 'AICA_Plugin_Smalot_Parser' ) ) {
            $this->pdf_parser = new AICA_Plugin_Smalot_Parser($this->plugin_slug);
        }
    }

    public function llm_api_v2($system_instruction, $user_query, $history = []): string {
        $attachment_data = $this->prepare_attachments();

        $items = $this->format_conversation_items(
            $attachment_data['multimodal_parts'], 
            $history, 
            $user_query
        );

        $final_dev_text = $system_instruction . $attachment_data['file_context_text'];

        return $this->request_openai($final_dev_text, $items);
    }

    private function prepare_attachments(): array {
        $file_id = get_option('ai_agent_knowledge_file_id');
        $data = ['multimodal_parts' => [], 'file_context_text' => ""];

        if (!$file_id) return $data;

        $file_path = get_attached_file($file_id);
        if (!$file_path || !file_exists($file_path)) {
            return $data;
        }

        $mime_type = get_post_mime_type($file_id);
        $file_size = filesize($file_path);
        $threshold = 10 * 1024 * 1024; // 10MB

        if (strpos($mime_type, 'image') !== false) {
            $data['multimodal_parts'][] = [
                'type' => 'input_image',
                'image_url' => [
                    'url' => "data:$mime_type;base64," . base64_encode(file_get_contents($file_path))
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
                $openai_file_id = $this->ensure_openai_file($file_path, $file_id);
                if ($openai_file_id && !is_wp_error($openai_file_id)) {
                    $data['multimodal_parts'][] = [
                        'type' => 'input_file',
                        'file' => ['file_id' => $openai_file_id]
                    ];
                }
            }
        }

        return $data;
    }

    private function format_conversation_items($multimodal_parts, $history, $user_query): array {
        $items = [];

        if (!empty($multimodal_parts)) {
            $items[] = [
                'role' => 'user', 
                'content' => array_merge($multimodal_parts, [['type' => 'input_text', 'text' => "Use the attached data for grounding."]])
            ];
            $items[] = [
                'role' => 'assistant', 
                'content' => [['type' => 'output_text', 'text' => "Understood."]]
            ];
        }

        foreach ($history as $msg) {
            $is_assistant = ($msg['role'] === 'assistant' || $msg['role'] === 'ai');
            $items[] = [
                'role'    => $is_assistant ? 'assistant' : 'user', 
                'content' => [[
                    'type' => $is_assistant ? 'output_text' : 'input_text', 
                    'text' => $msg['content']
                ]]
            ];
        }

        $items[] = [
            'role'    => 'user', 
            'content' => [['type' => 'input_text', 'text' => $user_query]]
        ];

        return $items;
    }

    public function llm_api_v3($system_instruction, $user_query, $history = []): string {
        $instructions = $system_instruction;
        $items = [];

        if (!empty($history)) {
            foreach ($history as $msg) {
                $is_assistant = ($msg['role'] === 'assistant' || $msg['role'] === 'ai');
                $items[] = [
                    'role'    => $is_assistant ? 'assistant' : 'user',
                    'content' => [[
                        'type' => $is_assistant ? 'output_text' : 'input_text',
                        'text' => $msg['content']
                    ]]
                ];
            }
        }

        $items[] = [
            'role'    => 'user',
            'content' => [['type' => 'input_text', 'text' => $user_query]]
        ];

        return $this->request_openai($instructions, $items);
    }

    private function request_openai($system_instruction, $items) {
        $api_key = get_option('openai_api_key');
        if ( empty($api_key) ) {
            return __( 'Error: OpenAI API key is missing. Please check your settings.', 'aica-plugin' );
        }

        $model = get_option('openai_model', 'gpt-5.1'); 
        $url   = "https://api.openai.com/v1/responses";

        $payload = [
            'model'        => $model,
            'instructions' => $system_instruction,
            'input'        => $items,
            'store'        => true,
            'temperature'  => 0.1
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json'
            ],
            'body'    => json_encode($payload),
            'timeout' => 120
        ]);

        if (is_wp_error($response)) {
            return __( 'Connection Error: ', 'aica-plugin' ) . $response->get_error_message();
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            $msg = $body['error']['message'] ?? __( 'Unknown OpenAI Error', 'aica-plugin' );
            return __( 'OpenAI Error: ', 'aica-plugin' ) . $msg;
        }
    
        if (isset($body['output'][0]['content'][0]['text'])) {
            return $body['output'][0]['content'][0]['text'];
        }

        return __( 'Error: No text returned.', 'aica-plugin' );
    }

    private function ensure_openai_file($file_path, $wp_id) {
        $key = 'aica_oai_f_' . $wp_id;
        $id  = get_transient($key);
        if ($id) return $id;

        $api_key = get_option('openai_api_key');
        if ( empty($api_key) ) return false;
        
        $response = wp_remote_post("https://api.openai.com/v1/files", [
            'headers' => ['Authorization' => 'Bearer ' . $api_key],
            'body'    => [
                'purpose' => 'user_data',
                'file'    => fopen($file_path, 'r')
            ]
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