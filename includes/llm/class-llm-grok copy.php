<?php
defined( 'ABSPATH' ) || exit;
class AICA_Plugin_LLM_Grok {

    private $plugin_slug;
    private $system_instruction;
    private $pdf_parser;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        $this->system_instruction = get_option('ai_agent_system_prompt', 'You are FOX AI Assistant...');
        $this->pdf_parser = new AICA_Plugin_Smalot_Parser($this->plugin_slug);
    }

    public function llm_api_v2($system_instruction, $user_query, $history = []): string {
        $api_key = get_option('grok_api_key');
        $model = get_option('grok_model', 'grok-2-1212'); 
        $temperature = floatval(get_option('grok_temperature', '0.7'));

        if (empty($api_key)) {
            return "Error: Grok API Key is missing in settings.";
        }

        $db_manager = new AICA_Plugin_DB_Manager($this->plugin_slug);
        $website_kb = $db_manager->get_kb_content_as_string();

        if (!empty($website_kb)) {
            $system_instruction .= "\n\n### WEBSITE KNOWLEDGE:\n" . $website_kb;
        }

        $file_id = get_option('ai_agent_knowledge_file_id');
        $file_context = "";
        $image_data = null;

        if ($file_id) {
            $file_path = get_attached_file($file_id);
            $mime_type = get_post_mime_type($file_id);

            if (strpos($mime_type, 'image') !== false && strpos($model, 'vision') !== false) {
                if (file_exists($file_path)) {
                    $image_base64 = base64_encode(file_get_contents($file_path));
                    $image_data = "data:$mime_type;base64,$image_base64";
                }
            } else if (file_exists($file_path)) {
                if ($mime_type === 'application/pdf') {
                    $extracted_text = $this->pdf_parser->extract_text_from_pdf($file_path);
                    $file_context = "\n\n### KNOWLEDGE BASE CONTENT:\n" . mb_convert_encoding($extracted_text, 'UTF-8', 'UTF-8');
                } else {
                    $raw_content = file_get_contents($file_path);
                    $file_context = "\n\n### KNOWLEDGE BASE CONTENT:\n" . mb_convert_encoding($raw_content, 'UTF-8', 'UTF-8');
                }
            }
        }

        $api_url = "https://api.x.ai/v1/chat/completions";

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $system_instruction . $file_context];

        foreach ($history as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'ai') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }

        if ($image_data) {
            $messages[] = [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $user_query],
                    ['type' => 'image_url', 'image_url' => ['url' => $image_data, 'detail' => 'auto']]
                ]
            ];
        } else {
            $messages[] = ['role' => 'user', 'content' => $user_query];
        }

        $payload = [
            'messages'    => $messages,
            'model'       => $model,
            'temperature' => $temperature,
            'stream'      => false
        ];

        $json_body = json_encode($payload);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Grok JSON Encode Error: " . json_last_error_msg());
            return "Error: Could not encode request payload. Check file encoding.";
        }

        $response = wp_remote_post($api_url, [
            'method'    => 'POST',
            'headers'   => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key, 
            ],
            'body'      => $json_body,
            'timeout'   => 100, 
        ]);

        if (is_wp_error($response)) {
            return "Error: " . $response->get_error_message();
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['error'])) {
            return "Error: " . ($result['error']['message'] ?? 'Unknown API error.');
        }

        $content = $result['choices'][0]['message']['content'] ?? "Error: No response from Grok.";
        return is_array($content) ? json_encode($content) : (string)$content;
    }


    public function llm_api_v3($system_instruction, $user_query, $history = []): string {
        $api_key = get_option('grok_api_key');
        $model = get_option('grok_model', 'grok-2-1212'); 
        $temperature = 0.3;

        if (empty($api_key)) {
            return "Error: Grok API Key is missing in settings.";
        }

        $api_url = "https://api.x.ai/v1/chat/completions";

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $system_instruction];

        foreach ($history as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'ai') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }

        $messages[] = ['role' => 'user', 'content' => $user_query];

        $payload = [
            'messages'    => $messages,
            'model'       => $model,
            'temperature' => $temperature,
            'stream'      => false
        ];

        $json_body = json_encode($payload);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Error: Could not encode request payload.";
        }

        $response = wp_remote_post($api_url, [
            'method'    => 'POST',
            'headers'   => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key, 
            ],
            'body'      => $json_body,
            'timeout'   => 100,
        ]);

        if (is_wp_error($response)) {
            return "Error: " . $response->get_error_message();
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['error'])) {
            return "Error: " . ($result['error']['message'] ?? 'Unknown API error.');
        }

        $content = $result['choices'][0]['message']['content'] ?? "Error: No response from Grok.";
        return (string)$content;
    }

}
