<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_LLM_Processor {

    private $plugin_slug;
    private $system_instruction;
    private $llm_model;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
        $this->system_instruction = get_option('ai_agent_system_prompt', __( 'You are an AI Assistant...', 'aica-plugin' ));

        $active_llm_model = get_option('llm_model', 'Grok');

        // Factory pattern for LLM Models
        if ($active_llm_model === "Grok" && class_exists('AICA_Plugin_LLM_Grok')) {
            $this->llm_model = new AICA_Plugin_LLM_Grok($this->plugin_slug);
        } elseif ($active_llm_model === "OpenAI" && class_exists('AICA_Plugin_LLM_OpenAI')) {
            $this->llm_model = new AICA_Plugin_LLM_OpenAI($this->plugin_slug);
        } elseif ($active_llm_model === "Gemini" && class_exists('AICA_Plugin_LLM_Gemini')) {
            $this->llm_model = new AICA_Plugin_LLM_Gemini($this->plugin_slug);
        } else {
            // Fallback to Grok
            if (class_exists('AICA_Plugin_LLM_Grok')) {
                $this->llm_model = new AICA_Plugin_LLM_Grok($this->plugin_slug);
            }
        }
    }

    public function get_llm_response(string $user_query, array $history = []): string {
        if (!$this->llm_model) {
            return __( 'Error: LLM Model not properly initialized.', 'aica-plugin' );
        }

        $system_instruction_path = AICA_PLUGIN_PATH . 'includes/prompts/llm-system-instruction-core.php';

        if ( ! file_exists( $system_instruction_path ) ) {
            return __( 'Error: Internal configuration file missing.', 'aica-plugin' );
        }
        
        $core_system_instruction = include( $system_instruction_path );
        
        $db_manager = new AICA_Plugin_DB_Manager($this->plugin_slug);
        $liked_examples = $db_manager->get_feedback_by_type('like', 3);
        $disliked_examples = $db_manager->get_feedback_by_type('dislike', 3);
        
        $feedback_context = "";

        if (!empty($liked_examples)) {
            $feedback_context .= "\n\n### POSITIVE EXAMPLES (FOLLOW THIS STYLE):";
            foreach ($liked_examples as $example) {
                $feedback_context .= "\nUser: " . esc_html($example['user_query']);
                $feedback_context .= "\nSuccessful Response: " . $example['ai_response'] . "\n---";
            }
        }

        if (!empty($disliked_examples)) {
            $feedback_context .= "\n\n### NEGATIVE EXAMPLES (AVOID THESE MISTAKES):";
            foreach ($disliked_examples as $example) {
                $feedback_context .= "\nUser: " . esc_html($example['user_query']);
                $feedback_context .= "\nRejected Response: " . $example['ai_response'];
                $feedback_context .= "\nReason: The user found this unhelpful. Do not repeat this approach.\n---";
            }
        }

        $kb_content = $db_manager->get_kb_content_as_string();
        
        if (strlen($kb_content) > 500000) {
            $kb_content = substr($kb_content, 0, 500000) . "... [Truncated]";
        }

        $final_instruction = $this->system_instruction . "\n\n" . $core_system_instruction . $feedback_context;
        $final_instruction .= "\n\n### WEBSITE KNOWLEDGE:\n" . $kb_content;

        return $this->llm_model->llm_api_v2($final_instruction, $user_query, $history);
    }

    public function get_chat_summary(array $history): string {
        if (!$this->llm_model) return "";
        if (empty($history)) return __( 'No conversation history to summarize.', 'aica-plugin' );

        $summarizer_prompt_path = AICA_PLUGIN_PATH . 'includes/prompts/llm-chat-summarizer.php';
        if (!file_exists($summarizer_prompt_path)) {
            return __( 'Error: Summarizer prompt file missing.', 'aica-plugin' );
        }

        $system_instruction = include($summarizer_prompt_path);
        
        $chat_text = "CONVERSATION TRANSCRIPT:\n";
        foreach ($history as $msg) {
            $role = ($msg['role'] === 'user') ? 'Visitor' : 'AI Assistant';
            $chat_text .= "[$role]: " . strip_tags($msg['content']) . "\n";
        }

        $user_query = "Summarize this chat now. Follow the HTML formatting rules strictly:\n\n" . $chat_text;

        return $this->llm_model->llm_api_v3($system_instruction, $user_query, []);
    }

    public function generate_automated_system_prompt() {
        if (!$this->llm_model) return __( 'Error: LLM Model missing.', 'aica-plugin' );

        $db_manager = new AICA_Plugin_DB_Manager($this->plugin_slug);
        $website_content = $db_manager->get_kb_content_as_string();

        if (empty($website_content)) {
            return __( 'Error: No website content found. Please sync your pages first.', 'aica-plugin' );
        }

        $generator_prompt_path = AICA_PLUGIN_PATH . 'includes/prompts/llm-system-prompt-generator.php';
        if (!file_exists($generator_prompt_path)) {
            return __( 'Error: Generator template missing.', 'aica-plugin' );
        }

        $base_instruction = include($generator_prompt_path);
        $final_system_instruction = str_replace('{{WEBSITE_CONTENT}}', substr($website_content, 0, 50000), $base_instruction);

        $user_query = "Based on the website knowledge provided, generate my professional system prompt now.";

        return $this->llm_model->llm_api_v3($final_system_instruction, $user_query, []);
    }

    public function extract_content($content) {
        if (is_array($content)) return $content;

        if (strpos($content, '```json') !== false) {
            preg_match('/```json\s*([\s\S]*?)\s*```/', $content, $matches);
            $content = $matches[1] ?? $content;
        } elseif (strpos($content, '```html') !== false) {
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
}