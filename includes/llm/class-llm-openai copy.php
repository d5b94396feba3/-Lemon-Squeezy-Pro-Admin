<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_LLM_OpenAI {
    private $plugin_slug;
    private $pdf_parser;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        $this->pdf_parser = new AICA_Plugin_Smalot_Parser($this->plugin_slug);
    }

    public function llm_api_v2($system_instruction, $user_query, $history = []): string {

        $file_id = get_option('ai_agent_knowledge_file_id');
        $file_context = "";
        $image_data = null;

        if ($file_id) {
            $file_path = get_attached_file($file_id);
            $mime_type = get_post_mime_type($file_id);

            if (strpos($mime_type, 'image') !== false) {
                $image_base64 = base64_encode(file_get_contents($file_path));
                $image_data = "data:$mime_type;base64,$image_base64";
            } else if (file_exists($file_path)) {
                $text = ($mime_type === 'application/pdf') ? $this->pdf_parser->extract_text_from_pdf($file_path) : file_get_contents($file_path);
                $file_context = "\n\n### KNOWLEDGE BASE CONTENT:\n" . mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            }
        }

        $messages = [['role' => 'system', 'content' => $system_instruction . $file_context]];

        foreach ($history as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'ai') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }

        if ($image_data) {
            $messages[] = [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $user_query],
                    ['type' => 'image_url', 'image_url' => ['url' => $image_data]]
                ]
            ];
        } else {
            $messages[] = ['role' => 'user', 'content' => $user_query];
        }

        return $this->request_openai($messages);
    }

    public function llm_api_v3($system_instruction, $user_query, $history = []): string {
        $messages = [['role' => 'system', 'content' => $system_instruction], ['role' => 'user', 'content' => $user_query]];
        return $this->request_openai($messages);
    }

    private function request_openai($messages) {

        $api_key = get_option('openai_api_key');
        $model = get_option('openai_model', 'gpt-5.1'); 
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
            'timeout' => 100
        ]);
    
        if (is_wp_error($response)) {
            return "System Error: " . $response->get_error_message();
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
    
        if (isset($body['error'])) {
            $error_msg = $body['error']['message'] ?? 'Unknown OpenAI Error';
            $error_type = $body['error']['type'] ?? 'api_error';
            error_log("OpenAI API Failure: [$error_type] $error_msg");
            return "OpenAI Error: $error_msg";
        }
    
        return $body['choices'][0]['message']['content'] ?? "Error: No response content received from OpenAI.";
    }

}