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

    /**
     * Modified to accept history array
     */
    public function get_llm_response(string $user_query, array $history = []): string {

        // $system_instruction = "You are FOX AI Assistant, a professional IT expert. Answer the user's questions clearly and concisely. If they ask about services, refer to our IT management packages.";
        $system_instruction = $this->system_instruction;

        $system_instruction_path = AICA_PLUGIN_PATH . 'includes/prompts/llm-system-instruction-core.php';

        if ( ! file_exists( $system_instruction_path ) ) {
            return "Error: Internal configuration file missing. Cannot process response.";
        }
        
        $core_system_instruction = require( $system_instruction_path);

        error_log("core_system_instruction: ". $core_system_instruction);
        
        $system_instruction = $this->system_instruction . "\n\n" . $core_system_instruction;

        return $this->llm_api_v2($system_instruction, $user_query, $history);
    }

    public function llm_api_v2($system_instruction, $user_query, $history = []) {

        $api_key = GROK_API_KEY;
        // Note: Ensure model name is correct (e.g., 'grok-beta' or 'grok-2')
        $model = get_option('grok_model', 'grok-beta'); 
        $temperature = floatval(get_option('grok_temperature', '0.7'));

        $api_url = "https://api.x.ai/v1/chat/completions";

        // Build the messages payload
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $system_instruction];

        // If history exists, we use it. 
        // If history is empty (first message), we just use the user_query.
        if (!empty($history)) {
            foreach ($history as $msg) {
                $messages[] = [
                    'role'    => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        } else {
            $messages[] = ['role' => 'user', 'content' => $user_query];
        }

        $payload = [
            'messages'    => $messages,
            'model'       => $model,
            'stream'      => false,
            'temperature' => $temperature,
        ];

        if (defined('GROK_API_KEY') && empty(GROK_API_KEY)) {
            return "Error: Grok API Key is missing.";
        }
        
        $response = wp_remote_post($api_url, [
            'method'    => 'POST',
            'headers'   => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key, 
            ],
            'body'      => json_encode($payload),
            'timeout'   => 120, 
        ]);

        if (is_wp_error($response)) {
            return "Error: " . $response->get_error_message();
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['error'])) {
            return "Error: " . ($result['error']['message'] ?? 'Unknown API error.');
        }

        $generated_content = $result['choices'][0]['message']['content'] ?? "Error: No response.";
        return $this->extract_content($generated_content);
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
