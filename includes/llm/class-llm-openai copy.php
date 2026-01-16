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
        // $api_key = get_option('openai_api_key');
        $api_key = OPENAI_API_KEY;
        $model = get_option('openai_model', 'gpt-4o'); 
        $temperature = floatval(get_option('openai_temperature', '0.7'));
        
        error_log("in openai api_key...: ".$api_key);

        if (empty($api_key)) return "Error: OpenAI API Key missing.";

        $db_manager = new AICA_Plugin_DB_Manager($this->plugin_slug);
        $system_instruction .= "\n\n### WEBSITE KNOWLEDGE:\n" . $db_manager->get_kb_content_as_string();

        // --- START FILE HANDLING ---
        $file_id = get_option('ai_agent_knowledge_file_id');
        $file_context = "";
        $image_data = null;

        if ($file_id) {
            $file_path = get_attached_file($file_id);
            $mime_type = get_post_mime_type($file_id);
            error_log("in openai api_key... mime_type : ".$mime_type);

            if (strpos($mime_type, 'image') !== false) {
                $image_base64 = base64_encode(file_get_contents($file_path));
                $image_data = "data:$mime_type;base64,$image_base64";
            } else if (file_exists($file_path)) {
                $text = ($mime_type === 'application/pdf') ? $this->pdf_parser->extract_text_from_pdf($file_path) : file_get_contents($file_path);
                $file_context = "\n\n### KNOWLEDGE BASE CONTENT:\n" . mb_convert_encoding($text, 'UTF-8', 'UTF-8');
                error_log("in openai api_key... file_context : \n".$file_context);
            }
        }
        // --- END FILE HANDLING ---

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

        return $this->request_openai($api_key, $model, $messages, $temperature);
    }

    public function llm_api_v3($system_instruction, $user_query, $history = []): string {
        $messages = [['role' => 'system', 'content' => $system_instruction], ['role' => 'user', 'content' => $user_query]];
        return $this->request_openai(get_option('openai_api_key'), get_option('openai_model', 'gpt-4o'), $messages, $temperature);
    }

    private function request_openai($api_key, $model, $messages, $temp) {
        $response = wp_remote_post("https://api.openai.com/v1/chat/completions", [
            'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Content-Type' => 'application/json'],
            'body' => json_encode(['model' => $model, 'messages' => $messages, 'temperature' => $temp]),
            'timeout' => 100
        ]);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        error_log("openai resposne: ". print_r($response, true));
        return $body['choices'][0]['message']['content'] ?? "Error: OpenAI API Error.";
    }

}