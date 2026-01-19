<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_LLM_Grok {

    private $plugin_slug;
    private $pdf_parser;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        $this->pdf_parser = new AICA_Plugin_Smalot_Parser($this->plugin_slug);
    }

    /**
     * Version 2: Supports Attachments (Images & Documents)
     */
    public function llm_api_v2($system_instruction, $user_query, $history = []): string {
        $attachment_data = $this->prepare_attachments($user_query);
        
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $system_instruction . $attachment_data['file_context']];

        foreach ($history as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'ai') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $attachment_data['user_content']
        ];

        return $this->request_grok($messages);
    }

    /**
     * Version 3: Simple Text-only Queries
     */
    public function llm_api_v3($system_instruction, $user_query, $history = []): string {
        $messages = [['role' => 'system', 'content' => $system_instruction]];

        foreach ($history as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'ai') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $user_query];

        return $this->request_grok($messages);
    }

    /**
     * Private helper to process file/image attachments
     */
    private function prepare_attachments($user_query): array {
        $file_id = get_option('ai_agent_knowledge_file_id');
        $data = [
            'file_context' => "",
            'user_content' => $user_query
        ];

        if (!$file_id) return $data;

        $file_path = get_attached_file($file_id);
        if (!$file_path || !file_exists($file_path)) return $data;

        $mime_type = get_post_mime_type($file_id);
        $model = get_option('grok_model', 'grok-2-1212');

        if (strpos($mime_type, 'image') !== false) {
            $image_base64 = base64_encode(file_get_contents($file_path));
            $data['user_content'] = [
                ['type' => 'text', 'text' => $user_query],
                ['type' => 'image_url', 'image_url' => ['url' => "data:$mime_type;base64,$image_base64", 'detail' => 'auto']]
            ];
        } 
        else {
            if ($mime_type === 'application/pdf') {
                $text = $this->pdf_parser->extract_text_from_pdf($file_path);
            } else {
                $text = file_get_contents($file_path);
            }
            $data['file_context'] = "\n\n### KNOWLEDGE BASE:\n" . mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        return $data;
    }

    /**
     * Standardized Grok API Request Handler
     */
    private function request_grok($messages) {
        $api_key = get_option('grok_api_key');
        $model = get_option('grok_model', 'grok-2-1212'); 
        $temp = floatval(get_option('grok_temperature', '0.7'));

        if (empty($api_key)) return "Error: Grok API Key missing.";

        $api_url = "https://api.x.ai/v1/chat/completions";

        $response = wp_remote_post($api_url, [
            'headers'   => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key, 
            ],
            'body'      => json_encode([
                'messages'    => $messages,
                'model'       => $model,
                'temperature' => $temp,
                'stream'      => false
            ]),
            'timeout'   => 100, 
        ]);

        if (is_wp_error($response)) {
            return "Connection Error: " . $response->get_error_message();
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return "Grok Error: " . ($body['error']['message'] ?? 'Unknown');
        }

        return $body['choices'][0]['message']['content'] ?? "Error: No response from Grok.";
    }
}