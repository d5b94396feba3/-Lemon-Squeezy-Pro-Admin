<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_LLM_Gemini {
    private $plugin_slug;
    private $pdf_parser;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        $this->pdf_parser = new AICA_Plugin_Smalot_Parser($this->plugin_slug);
    }

    public function llm_api_v2($system_instruction, $user_query, $history = []): string {

        /** OPENAI API KEY */
// define( 'OPENAI_API_KEY', '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' );

// /** GEMINI API KEY */
// define('GEMINI_API_KEY', 'AIzaSyBSbuC8cJUmdqmo40cQ0bmZoJsaWXLkaYI');

        $api_key = GEMINI_API_KEY;
        $api_key = get_option('gemini_api_key');
        $model = get_option('gemini_model', 'gemini-2.5-flash');
        $temperature = floatval(get_option('gemini_temperature', '0.7'));

        error_log("in gemini api_key...: ".$api_key);

        $db_manager = new AICA_Plugin_DB_Manager($this->plugin_slug);
        $system_instruction .= "\n\n### WEBSITE KNOWLEDGE:\n" . $db_manager->get_kb_content_as_string();

        // --- START FILE HANDLING ---
        $file_id = get_option('ai_agent_knowledge_file_id');
        $file_context = "";
        $image_payload = null;

        if ($file_id) {
            $file_path = get_attached_file($file_id);
            $mime_type = get_post_mime_type($file_id);
            error_log("in gemini api_key... mime_type : ".$mime_type);

            if (strpos($mime_type, 'image') !== false) {
                $image_payload = [
                    'inline_data' => [
                        'mime_type' => $mime_type,
                        'data' => base64_encode(file_get_contents($file_path))
                    ]
                ];
            } else if (file_exists($file_path)) {
                $text = ($mime_type === 'application/pdf') ? $this->pdf_parser->extract_text_from_pdf($file_path) : file_get_contents($file_path);
                $file_context = "\n\n### KNOWLEDGE BASE CONTENT:\n" . mb_convert_encoding($text, 'UTF-8', 'UTF-8');

                error_log("in gemini api_key... file_context : \n".$file_context);

            }
        }
        // --- END FILE HANDLING ---

        $final_system = $system_instruction . $file_context;
        $contents = [];
        foreach ($history as $msg) {
            $role = ($msg['role'] === 'user') ? 'user' : 'model';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
        }

        $user_parts = [['text' => $user_query]];
        if ($image_payload) { $user_parts[] = $image_payload; }
        $contents[] = ['role' => 'user', 'parts' => $user_parts];

        return $this->request_gemini($api_key, $model, $final_system, $contents, $temperature);
    }

    public function llm_api_v3($system_instruction, $user_query, $history = []): string {
        $contents = [['role' => 'user', 'parts' => [['text' => $user_query]]]];
        return $this->request_gemini(get_option('gemini_api_key'), get_option('gemini_model', 'gemini-1.5-flash'), $system_instruction, $contents);
    }

    private function request_gemini($api_key, $model, $system_instruction, $contents, $temperature) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
        $payload = [
            'system_instruction' => ['parts' => [['text' => $system_instruction]]],
            'contents' => $contents,
            'generationConfig' => ['temperature' => $temperature]
        ];
        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload),
            'timeout' => 100
        ]);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['candidates'][0]['content']['parts'][0]['text'] ?? "Error: Gemini API Error.";
    }

    private function extract_text_from_pdf($filename) {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filename);
            return $pdf->getText();
        } catch (\Exception $e) { return "Note: PDF could not be parsed."; }
    }
}