<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_LLM_OpenAI {
    private $plugin_slug;
    private $pdf_parser;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        $this->pdf_parser = new AICA_Plugin_Smalot_Parser($this->plugin_slug);
    }

    /**
     * Main Controller for OpenAI Logic
     */
    public function llm_api_v2($system_instruction, $user_query, $history = []): string {
        $attachment_data = $this->prepare_attachments();

        $messages = $this->format_conversation_messages(
            $system_instruction,
            $attachment_data['file_context_text'],
            $attachment_data['image_url'],
            $history,
            $user_query
        );

        return $this->request_openai($messages);
    }

    /**
     * Handles File logic: Extracts text from docs or encodes images
     */
    private function prepare_attachments(): array {
        $file_id = get_option('ai_agent_knowledge_file_id');
        $data = [
            'image_url'         => null,
            'file_context_text' => ""
        ];

        if (!$file_id) return $data;

        $file_path = get_attached_file($file_id);
        $mime_type = get_post_mime_type($file_id);

        if (strpos($mime_type, 'image') !== false) {
            $image_base64 = base64_encode(file_get_contents($file_path));
            $data['image_url'] = "data:$mime_type;base64,$image_base64";
        } 
        else if (file_exists($file_path)) {
            $text = ($mime_type === 'application/pdf') 
                ? $this->pdf_parser->extract_text_from_pdf($file_path) 
                : file_get_contents($file_path);
            
            $data['file_context_text'] = "\n\n### KNOWLEDGE BASE CONTENT:\n" . mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        return $data;
    }

    /**
     * Formats the OpenAI messages array
     */
    private function format_conversation_messages($system_instruction, $file_context, $image_url, $history, $user_query): array {
        $messages = [['role' => 'system', 'content' => $system_instruction . $file_context]];

        foreach ($history as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'ai') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }

        if ($image_url) {
            $messages[] = [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $user_query],
                    ['type' => 'image_url', 'image_url' => ['url' => $image_url]]
                ]
            ];
        } else {
            $messages[] = ['role' => 'user', 'content' => $user_query];
        }

        return $messages;
    }

    public function llm_api_v3($system_instruction, $user_query, $history = []): string {
        $messages = [
            ['role' => 'system', 'content' => $system_instruction],
            ['role' => 'user', 'content' => $user_query]
        ];
        return $this->request_openai($messages);
    }

    private function request_openai($messages) {
        $api_key = get_option('openai_api_key');
        $model = get_option('openai_model', 'gpt-4o');
        $temp = floatval(get_option('openai_temperature', '0.7'));
        
        if (empty($api_key)) return "Error: OpenAI API Key missing.";

        $response = wp_remote_post("https://api.openai.com/v1/chat/completions", [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key, 
                'Content-Type'  => 'application/json'
            ],
            'body'    => json_encode([
                'model'       => $model, 
                'messages'    => $messages, 
                'temperature' => $temp
            ]),
            'timeout' => 120
        ]);
    
        if (is_wp_error($response)) {
            return "System Error: " . $response->get_error_message();
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
    
        if (isset($body['error'])) {
            return "OpenAI Error: " . ($body['error']['message'] ?? 'Unknown Error');
        }
    
        return $body['choices'][0]['message']['content'] ?? "Error: No response content.";
    }
}