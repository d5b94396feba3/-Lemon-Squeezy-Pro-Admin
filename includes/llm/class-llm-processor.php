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

/**
     * Brute Force DOCX Extraction
     * No namespaces, no DOMDocument, just raw XML tag ripping.
     */
    private function extract_text_from_docx($filename) {
        if (!file_exists($filename) || !class_exists('ZipArchive')) return "";

        $zip = new ZipArchive;
        $full_text = "";

        if ($zip->open($filename) === true) {
            // Get the raw XML content
            $xml_content = $zip->getFromName('word/document.xml');
            $zip->close();

            if (!$xml_content) return "";

            // 1. Replace paragraph/column breaks with newlines so text doesn't bunch up
            $xml_content = str_replace(['</w:p>', '</w:br>', '</w:tab>'], "\n", $xml_content);

            // 2. Brutally strip every single XML tag
            $full_text = strip_tags($xml_content);

            // 3. Decode HTML entities (Word loves these)
            $full_text = html_entity_decode($full_text);
        }

        // 4. Clean up the mess (remove multiple spaces/newlines)
        $full_text = preg_replace('/[ \t]+/', ' ', $full_text);
        $full_text = preg_replace('/\n\s*\n/', "\n", $full_text);

        return mb_convert_encoding(trim($full_text), 'UTF-8', 'UTF-8');
    }

    public function llm_api_v2($system_instruction, $user_query, $history = []): string {
        $api_key = get_option('grok_api_key');
        // IMPORTANT: Use a vision-capable model if sending images
        $model = get_option('grok_model', 'grok-2-1212'); 
        $temperature = floatval(get_option('grok_temperature', '0.7'));

        if (empty($api_key)) {
            return "Error: Grok API Key is missing in settings.";
        }

        $file_id = get_option('ai_agent_knowledge_file_id');
        $file_context = "";
        $image_data = null;

        if ($file_id) {
            $file_path = get_attached_file($file_id);
            $mime_type = get_post_mime_type($file_id);

            error_log("file_path: " . $file_path);
            error_log("mime_type: " . $mime_type);

            // 1. Handle Vision (Images)
            if (strpos($mime_type, 'image') !== false && strpos($model, 'vision') !== false) {
                if (file_exists($file_path)) {
                    $image_base64 = base64_encode(file_get_contents($file_path));
                    $image_data = "data:$mime_type;base64,$image_base64";
                }
            } 
            // 2. Handle Documents (PDF, DOCX, TXT)
            else if (file_exists($file_path)) {
                $extracted_text = "";
                
                if ($mime_type === 'application/pdf') {
                    $extracted_text = $this->extract_text_from_pdf($file_path);
                } 
                elseif ($mime_type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    $extracted_text = $this->extract_text_from_docx($file_path);
                    
                    // Debug Log
                    error_log("AICA DOCX Extracted Length: " . strlen($extracted_text));
                    if (empty($extracted_text)) {
                        error_log("AICA Warning: DOCX extraction returned empty string.");
                    }
                }
                else {
                    $extracted_text = file_get_contents($file_path);
                }

                $file_context = "\n\n### KNOWLEDGE BASE CONTENT:\n" . mb_convert_encoding($extracted_text, 'UTF-8', 'UTF-8');
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

    /**
     * Takes the sanitized questionnaire data and generates a structured prompt
     * for the LLM to create a detailed IT service proposal.
     *
     * @param array $submitted_data Sanitized data from the AJAX submission.
     * @return string The generated proposal text (or error message).
     */
    public function generate_proposal_page(array $submitted_data, string $contract_html, string $signing_link): string {

        $formatted_data=$this->format_input_data($submitted_data);

        $system_instruction_path = AICA_PLUGIN_PATH . 'includes/prompts/llm-system-instruction-proposal-page.php';

        if ( ! file_exists( $system_instruction_path ) ) {
            return "Error: Internal configuration file missing. Cannot generate proposal.";
        }
        
        $system_instruction = require( $system_instruction_path );
        
        $user_query = "Please generate a comprehensive IT Service Proposal page based on the Client Data, and Contract HTML page.'.\n\nClient Data:\n" . $formatted_data . "\n\nContract HTML:\n" . $contract_html . "\n\nDocuSign Signing Link:\n" . $signing_link;

        $proposal_page = $this->llm_api($system_instruction, $user_query);

        return $proposal_page;
    }

     /**
     * Takes the sanitized questionnaire data and generates a structured prompt
     * for the LLM to create a detailed DocuSign contract html.
     *
     * @param array $submitted_data Sanitized data from the AJAX submission.
     * @return string The generated contract text (or error message).
     */
    public function generate_docusign_contract_html(array $submitted_data): string {
        
        $formatted_data=$this->format_input_data($submitted_data);
       
        $system_instruction_path = AICA_PLUGIN_PATH . 'includes/prompts/llm-system-instruction-docusign-contract.php';

        if ( ! file_exists( $system_instruction_path ) ) {
            return "Error: Internal configuration file missing. Cannot generate proposal.";
        }
        
        $system_instruction = require( $system_instruction_path );
        $user_query = "Please analyze the following client data and generate a comprehensive DocuSign contract html page :\n\nClient Data:\n" . $formatted_data;
        $proposal_page = $this->llm_api($system_instruction, $user_query);

        return $proposal_page;
    }

    public function format_input_data($submitted_data) {
        $client_name = $submitted_data['contact-name'];
        $company_name = $submitted_data['company-name'];
        $company_address = $submitted_data['company-address'];
        $email = $submitted_data['contact-email'];
        $phone = $submitted_data['contact-phone'];
        
        $business_overview = $submitted_data['business-overview'];
        $company_industry = $submitted_data['company-industry'];
        $current_it_situation = $submitted_data['current-it-situation'];
        $current_challenges = $submitted_data['current-challenges'];
        
        $goals = isset($submitted_data['primary-goals']) ? implode(', ', $submitted_data['primary-goals']) : 'Not specified';
        $goals_other = isset($submitted_data['primary-goals-other-text']) ? $submitted_data['primary-goals-other-text'] : '';
                
        $services = isset($submitted_data['preferred-services']) ? $submitted_data['preferred-services'] : 'Not specified';
        
        $num_employees = isset($submitted_data['exact-employee-count']) ? $submitted_data['exact-employee-count'] : 'Not specified';
        $num_locations = isset($submitted_data['exact-location-count']) ? $submitted_data['exact-location-count'] : 'Not specified';
        $contract_duration = isset($submitted_data['contract-duration']) ? $submitted_data['contract-duration'] : 'Not specified';
        
        $start_timeline = $submitted_data['start-timeline'];
        $budget_range = 0;
        
        $pricing_data = $submitted_data['pricing_data'] ?? [];
        
        $selected_package = $submitted_data['preferred-services'] ?? '';
        $calculated_monthly_price = 0;
        $calculated_monthly_price_before_discount = 0;
        $discount_percentage = 0;
        $monthly_savings = 0;
        $annual_savings = 0;
        
        switch($selected_package) {
            case 'Essential – Business Core':
                $calculated_monthly_price = $pricing_data['essential_core_price'] ?? 0;
                $calculated_monthly_price_before_discount = $pricing_data['essential_core_price_before_discount'] ?? 0;
                break;
            case 'Elite – Business Growth':
                $calculated_monthly_price = $pricing_data['elite_growth_price'] ?? 0;
                $calculated_monthly_price_before_discount = $pricing_data['elite_growth_price_before_discount'] ?? 0;
                break;
            case 'Plus – Business Fortress':
                $calculated_monthly_price = $pricing_data['plus_fortress_price'] ?? 0;
                $calculated_monthly_price_before_discount = $pricing_data['plus_fortress_price_before_discount'] ?? 0;
                break;
            case 'Custom – Block Time':
                $calculated_monthly_price = $pricing_data['custom_block_price'] ?? 0;
                $calculated_monthly_price_before_discount = $pricing_data['custom_block_price'] ?? 0;
                break;
            default:
                $calculated_monthly_price = 0;
                $calculated_monthly_price_before_discount = 0;
        }
        
        // Get discount percentage and calculate savings for selected package only
        $discount_percentage = $pricing_data['discount_percentage'] ?? 0;
        $contract_duration = $pricing_data['contract_duration'] ?? 0;
        
        // Calculate savings only if there's a discount
        if ($discount_percentage > 0 && $selected_package !== 'Custom – Block Time') {
            $monthly_savings = $calculated_monthly_price_before_discount - $calculated_monthly_price;
            $annual_savings = $monthly_savings * 12;
        }
        
        $exact_device_count = isset($submitted_data['exact-device-count']) ? intval($submitted_data['exact-device-count']) : 0;
        $price_per_hour = $pricing_data['price_per_hour'] ?? 0;
        $hours_per_month = $pricing_data['hours_per_month'] ?? 0;
        
        $current_date = date('F j, Y');
        
        if (!empty($goals_other)) {
            $goals .= " (Other: {$goals_other})";
        }
        
        $formatted_data = "Company Name: {$company_name}\n";
        $formatted_data .= "Company Address: {$company_address}\n";
        $formatted_data .= "Client Name: {$client_name}\n";
        $formatted_data .= "Client Contact Email: {$email}\n";
        $formatted_data .= "Client Contact Phone: {$phone}\n";
        $formatted_data .= "Current Date: {$current_date}\n";
        $formatted_data .= "Industry: {$company_industry}\n";
        $formatted_data .= "Employees: {$num_employees}\n";
        $formatted_data .= "Locations: {$num_locations}\n";
        $formatted_data .= "Business Overview: {$business_overview}\n";
        $formatted_data .= "Current IT Setup: {$current_it_situation}\n";
        $formatted_data .= "Current IT Challenges: {$current_challenges}\n";
        $formatted_data .= "Primary Goals/Priorities: {$goals}\n";
        $formatted_data .= "Selected Service Package: {$services}\n";
        $formatted_data .= "Start Timeline: {$start_timeline}\n";
        $formatted_data .= "Contract Duration: {$contract_duration} Month\n";
        $formatted_data .= "Device Count: {$exact_device_count}\n";
        $formatted_data .= "Price per hour: \${$price_per_hour}\n";
        $formatted_data .= "Hours per month: {$hours_per_month}\n";
        
        // Add pricing information for selected package only
        $formatted_data .= "\n--- Selected Package Pricing ---\n";
        $formatted_data .= "Package: {$selected_package}\n";
        
        if ($selected_package === 'Custom – Block Time') {
            $formatted_data .= "One-Time Payment: \${$calculated_monthly_price}\n";
            $formatted_data .= "Service: 40 hours at $275/hour\n";
            $formatted_data .= "Contract: No contract required\n";
        } else {
            $formatted_data .= "Monthly Price Before Discount: \${$calculated_monthly_price_before_discount}\n";
            $formatted_data .= "Discount Percentage: {$discount_percentage}%\n";
            $formatted_data .= "Monthly Price After Discount: \${$calculated_monthly_price}\n";
            
            // Add savings information only if there's a discount
            if ($discount_percentage > 0) {
                $formatted_data .= "Monthly Savings: \${$monthly_savings}\n";
                $formatted_data .= "Annual Savings: \${$annual_savings}\n";
            }
            if($contract_duration > 0){
                $formatted_data .= "Contract Type: {$contract_duration}-month contract\n";
            }else{
                $formatted_data .= "Contract Type: No contract required\n";
            }
        }
        
        return $formatted_data;
    }

    public function llm_api($system_instruction,$user_query){

        $api_key = GROK_API_KEY;
        $model = get_option('grok_model', 'grok-4-fast-reasoning');
        $temperature = floatval(get_option('grok_temperature', '0'));
        $max_tokens = intval(get_option('grok_max_tokens', '4000'));

        $api_url = "https://api.x.ai/v1/chat/completions";

        $payload = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_instruction, 
                ],
                [
                    'role' => 'user',
                    'content' => $user_query,
                ],
            ],
            'model' => $model,
            'stream' => false,
            'temperature' => $temperature,
        ];

        // Check if GROK_API_KEY is empty before proceeding
        if (defined('GROK_API_KEY') && empty(GROK_API_KEY)) {
            return "Error: Grok API Key is missing. Please configure the key in the plugin settings.";
        }
        
        $response = wp_remote_post($api_url, [
            'method'    => 'POST',
            'headers'   => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key, 
            ],
            'body'      => json_encode($payload),
            'timeout'   => 290, 
        ]);

        if (is_wp_error($response)) {
            return "Error: Failed to connect to the Proposal Engine. " . $response->get_error_message();
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['error'])) {
            $error_message = $result['error']['message'] ?? 'Unknown Grok API error.';
            return "Error: Grok API failed. " . $error_message;
        }

        $generated_content = $result['choices'][0]['message']['content'] ?? "Error: Proposal generation failed. Response was incomplete or malformed.";
        
        $generated_content=$this->extract_content($generated_content);

        return $generated_content;
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
}
