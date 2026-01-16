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

        return $this->request_gemini($final_system, $contents);
    }

    public function llm_api_v3($system_instruction, $user_query, $history = []): string {
        $contents = [['role' => 'user', 'parts' => [['text' => $user_query]]]];
        return $this->request_gemini($system_instruction, $contents);
    }

    private function request_gemini($system_instruction, $contents) {

        $api_key = get_option('gemini_api_key');
        $model = get_option('gemini_model', 'gemini-2.5-flash');
        $temperature = floatval(get_option('gemini_temperature', '0.7'));
        
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
        
        $payload = [
            'system_instruction' => ['parts' => [['text' => $system_instruction]]],
            'contents'           => $contents,
            'generationConfig'   => ['temperature' => $temperature]
        ];
    
        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($payload),
            'timeout' => 100
        ]);
    
        if (is_wp_error($response)) {
            return "Connection Error: " . $response->get_error_message();
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
    
        // Scenario A: API Level Error (Invalid Key, 429 Rate Limit, etc.)
        if (isset($body['error'])) {
            $msg = $body['error']['message'] ?? 'Unknown Gemini Error';
            return "Gemini API Error: $msg";
        }
    
        // Scenario B: Safety or Prompt Blocking (finishReason / blockReason)
        if (isset($body['promptFeedback']['blockReason'])) {
            return "Gemini Blocked: The prompt was rejected (Reason: " . $body['promptFeedback']['blockReason'] . ").";
        }
    
        $candidate = $body['candidates'][0] ?? null;
        
        if ($candidate) {
            // Check if the specific candidate was stopped due to SAFETY
            if ($candidate['finishReason'] === 'SAFETY') {
                return "Gemini Blocked: Response filtered due to safety concerns.";
            }
    
            // Return the actual text part
            if (isset($candidate['content']['parts'][0]['text'])) {
                return $candidate['content']['parts'][0]['text'];
            }
        }
    
        return "Error: Gemini failed to generate a response candidate.";
    }

    private function extract_text_from_pdf($filename) {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filename);
            return $pdf->getText();
        } catch (\Exception $e) { return "Note: PDF could not be parsed."; }
    }
}