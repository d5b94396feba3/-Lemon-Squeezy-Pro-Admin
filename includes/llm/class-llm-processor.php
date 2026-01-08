<?php
defined( 'ABSPATH' ) || exit;
/**
 * Handles communication with the Grok API (x.ai) to generate complex proposals
 * based on user questionnaire data.
 */
class AICA_Plugin_LLM_Processor {

    private $plugin_slug;
    private $system_instruction;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        $this->system_instruction = get_option('ai_agent_system_prompt', 'You are FOX AI Assistant...');
    }

    public function get_llm_response(string $user_query, array $history = []): string {
        $system_instruction = $this->system_instruction;
        $system_instruction_path = AICA_PLUGIN_PATH . 'includes/prompts/llm-system-instruction-core.php';

        if ( ! file_exists( $system_instruction_path ) ) {
            return "Error: Internal configuration file missing. Cannot process response.";
        }
        
        $core_system_instruction = require( $system_instruction_path );
        
        // --- START FEEDBACK INJECTION ---
        $db_manager = new AICA_Plugin_DB_Manager($this->plugin_slug);
        $liked_examples = $db_manager->get_feedback_by_type('like', 3);
        $disliked_examples = $db_manager->get_feedback_by_type('dislike', 3);
        
        $feedback_context = "";

        // 1. Positive Reinforcement
        if (!empty($liked_examples)) {
            $feedback_context .= "\n\n### POSITIVE EXAMPLES (FOLLOW THIS STYLE):";
            // This line logs positive feedback examples for debugging. 
            // Remove or comment out for production use. 
            error_log("feedback_context:: ".print_r($liked_examples, true));
            foreach ($liked_examples as $example) {
                $feedback_context .= "\nUser: " . $example['user_query'];
                $feedback_context .= "\nSuccessful Response: " . $example['ai_response'] . "\n---";
            }
        }

        // 2. Negative Constraints (Crucial for learning)
        if (!empty($disliked_examples)) {
            $feedback_context .= "\n\n### NEGATIVE EXAMPLES (AVOID THESE MISTAKES):";
            foreach ($disliked_examples as $example) {
                $feedback_context .= "\nUser: " . $example['user_query'];
                $feedback_context .= "\nRejected Response: " . $example['ai_response'];
                $feedback_context .= "\nReason: The user found this unhelpful or incorrect. Do not repeat this approach.\n---";
            }
        }
        // --- END FEEDBACK INJECTION ---

        $system_instruction = $this->system_instruction . "\n\n" . $core_system_instruction . $feedback_context;

        return $this->llm_api_v2($system_instruction, $user_query, $history);
    }

    /**
     * Extract text using the manually loaded Smalot library
     */
    private function extract_text_from_pdf($filename) {
        if (!file_exists($filename)) {
            return "";
        }
    
        try {
            // Instantiate using the full Smalot namespace
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($filename);
            
            $text   = $pdf->getText();
            
            // Clean up whitespace for better LLM processing
            $text = preg_replace('/[ \t]+/', ' ', $text);
            $text = preg_replace('/\n\s*\n/', "\n", $text);
            
            return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        } catch (\Exception $e) {
            error_log("AICA PDF Parse Error: " . $e->getMessage());
            return "Note: The attached PDF knowledge base could not be parsed.";
        }
    }

    public function llm_api_v2($system_instruction, $user_query, $history = []): string {
        $api_key = get_option('grok_api_key');
        // IMPORTANT: Use a vision-capable model if sending images
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

        error_log("WEBSITE KNOWLEDGE: \n\n$website_kb");

        $file_id = get_option('ai_agent_knowledge_file_id');
        $file_context = "";
        $image_data = null;

        if ($file_id) {
            $file_path = get_attached_file($file_id);
            $mime_type = get_post_mime_type($file_id);

            error_log("AICA Debug: File ID found: $file_id | Mime: $mime_type | Model: $model");

            // Handle Images ONLY if model is vision-capable
            if (strpos($mime_type, 'image') !== false && strpos($model, 'vision') !== false) {
                if (file_exists($file_path)) {
                    $image_base64 = base64_encode(file_get_contents($file_path));
                    $image_data = "data:$mime_type;base64,$image_base64";
                }
            } else if (file_exists($file_path)) {
                if ($mime_type === 'application/pdf') {
                    $extracted_text = $this->extract_text_from_pdf($file_path);
                    // Ensure text is valid UTF-8 for JSON
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

        // SAFE JSON ENCODING
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

    public function extract_content($content) {
    
        if (strpos($content, '```json') !== false) {
            preg_match('/```json\s*([\s\S]*?)\s*```/', $content, $matches);
            $content = $matches[1] ?? $content;
        }
        elseif (strpos($content, '```html') !== false) {
            preg_match('/```html\s*([\s\S]*?)\s*```/', $content, $matches);
            $content = $matches[1] ?? $content;
        }
    
        $content = preg_replace('/^`+|`+$/', '', $content);
        
        if (preg_match('/^\s*[\{\[]/', $content)) {
            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
    
        return trim($content);
    }

    /**
     * LLM API v3: Optimized for pure text tasks (like Summarization)
     * Strips out Knowledge Base, PDF, and Image logic for maximum speed.
     */
    public function llm_api_v3($system_instruction, $user_query, $history = []): string {
        $api_key = get_option('grok_api_key');
        // Use a standard text model for summarization
        $model = get_option('grok_model', 'grok-2-1212'); 
        $temperature = 0.3; // Lower temperature is better for concise summaries

        if (empty($api_key)) {
            return "Error: Grok API Key is missing in settings.";
        }

        $api_url = "https://api.x.ai/v1/chat/completions";

        // Build simple message payload without file context
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $system_instruction];

        // Process History if any
        foreach ($history as $msg) {
            $role = ($msg['role'] === 'assistant' || $msg['role'] === 'ai') ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }

        // Add the summarization request
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
            'timeout'   => 60, // Summarization is usually fast
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

    /**
     * Generates a concise HTML summary of the chat history using v3 (No Files)
     */
    public function get_chat_summary(array $history): string {
        if (empty($history)) return "No conversation history to summarize.";

        $summarizer_prompt_path = AICA_PLUGIN_PATH . 'includes/prompts/llm-chat-summarizer.php';
        if (!file_exists($summarizer_prompt_path)) {
            return "Error: Summarizer prompt file missing.";
        }

        $system_instruction = require($summarizer_prompt_path);
        
        // Format history into a readable transcript for the AI
        $chat_text = "CONVERSATION TRANSCRIPT:\n";
        foreach ($history as $msg) {
            $role = ($msg['role'] === 'user') ? 'Visitor' : 'AI Assistant';
            $chat_text .= "[$role]: " . $msg['content'] . "\n";
        }

        $user_query = "Summarize this chat now. Follow the HTML formatting rules strictly:\n\n" . $chat_text;

        // Call v3 to ignore Knowledge Base files during summarization
        return $this->llm_api_v3($system_instruction, $user_query, []);
    }
}
