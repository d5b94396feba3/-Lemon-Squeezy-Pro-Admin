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

        $file_id = get_option('ai_agent_knowledge_file_id');
        $file_context = "";
        $image_payload = null;
        $file_data_payload = null; // New payload for File API

        if ($file_id) {
            $file_path = get_attached_file($file_id);
            $mime_type = get_post_mime_type($file_id);

            // 1. Handle Images via Inline Data (Your existing logic)
            if (strpos($mime_type, 'image') !== false) {
                $image_payload = [
                    'inline_data' => [
                        'mime_type' => $mime_type,
                        'data' => base64_encode(file_get_contents($file_path))
                    ]
                ];
            } 
            // 2. Handle Large PDFs/Text via Gemini File API (Extended Logic)
            else if ($mime_type === 'application/pdf' || $mime_type === 'text/plain') {
                $file_uri = $this->upload_to_gemini($file_path, $mime_type);
                error_log("file_uri: ". $file_uri);
                if ($file_uri) {
                    $file_data_payload = [
                        'file_data' => [
                            'mime_type' => $mime_type,
                            'file_uri'  => $file_uri
                        ]
                    ];
                } else {
                    // Fallback to your original manual extraction if upload fails
                    $text = ($mime_type === 'application/pdf') ? $this->pdf_parser->extract_text_from_pdf($file_path) : file_get_contents($file_path);
                    $file_context = "\n\n### KNOWLEDGE BASE CONTENT:\n" . mb_convert_encoding($text, 'UTF-8', 'UTF-8');
                }
            }
            // 3. Fallback for other file types
            else if (file_exists($file_path)) {
                $text = file_get_contents($file_path);
                $file_context = "\n\n### KNOWLEDGE BASE CONTENT:\n" . mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            }
        }

        $final_system = $system_instruction . $file_context;
        $contents = [];
        foreach ($history as $msg) {
            $role = ($msg['role'] === 'user') ? 'user' : 'model';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
        }

        $user_parts = [['text' => $user_query]];
        if ($image_payload) { $user_parts[] = $image_payload; }
        if ($file_data_payload) { $user_parts[] = $file_data_payload; } // Include File API reference
        
        $contents[] = ['role' => 'user', 'parts' => $user_parts];

        return $this->request_gemini($final_system, $contents);
    }

    /**
     * Uploads a file to the Gemini File API and returns the fileUri
     */
    private function upload_to_gemini($file_path, $mime_type) {
        $api_key = get_option('gemini_api_key');
        if (!$api_key || !file_exists($file_path)) return false;

        $file_size = filesize($file_path);
        $display_name = basename($file_path);

        // Stage 1: Initial resumable upload request
        $url = "https://generativelanguage.googleapis.com/upload/v1beta/files?key={$api_key}";
        
        $headers = [
            'X-Goog-Upload-Protocol' => 'resumable',
            'X-Goog-Upload-Command'  => 'start',
            'X-Goog-Upload-Header-Content-Length' => $file_size,
            'X-Goog-Upload-Header-Content-Type'   => $mime_type,
            'Content-Type' => 'application/json'
        ];

        $init_response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => json_encode(['file' => ['display_name' => $display_name]]),
        ]);

        if (is_wp_error($init_response)) return false;
        
        $upload_url = wp_remote_retrieve_header($init_response, 'x-goog-upload-url');
        if (!$upload_url) return false;

        // Stage 2: Upload the actual file data
        $upload_response = wp_remote_post($upload_url, [
            'headers' => [
                'Content-Length' => $file_size,
                'X-Goog-Upload-Offset' => 0,
                'X-Goog-Upload-Command' => 'upload, finalize',
            ],
            'body' => file_get_contents($file_path),
            'timeout' => 300
        ]);

        if (is_wp_error($upload_response)) return false;

        $body = json_decode(wp_remote_retrieve_body($upload_response), true);
        return $body['file']['uri'] ?? false;
    }

    // ... Rest of your existing functions (llm_api_v3, request_gemini, extract_text_from_pdf) remain unchanged
    
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
    
        if (isset($body['error'])) {
            $msg = $body['error']['message'] ?? 'Unknown Gemini Error';
            return "Gemini API Error: $msg";
        }
    
        if (isset($body['promptFeedback']['blockReason'])) {
            return "Gemini Blocked: The prompt was rejected (Reason: " . $body['promptFeedback']['blockReason'] . ").";
        }
    
        $candidate = $body['candidates'][0] ?? null;
        
        if ($candidate) {
            if ($candidate['finishReason'] === 'SAFETY') {
                return "Gemini Blocked: Response filtered due to safety concerns.";
            }
    
            if (isset($candidate['content']['parts'][0]['text'])) {
                return $candidate['content']['parts'][0]['text'];
            }
        }
    
        return "Error: Gemini failed to generate a response candidate.";
    }
}