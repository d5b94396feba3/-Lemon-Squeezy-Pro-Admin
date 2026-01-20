<?php
/**
 * Handles all email functionality for the AI Proposal System
 */

class AICA_Plugin_Mail {
    private $plugin_slug;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
    }

    /**
     * Send notification to admin when a new chat session starts
     */
    public function send_chat_notification_admin($user_message) {
        $admin_email = get_option('ai_agent_admin_email', get_option('admin_email'));
        $site_name = get_bloginfo('name');
        $site_name = wp_specialchars_decode($site_name, ENT_QUOTES);
        $subject = 'New AI Chat Session Started - ' . $site_name;
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background: #f4f4f4; padding: 20px; border-radius: 8px; }
                .content { background: white; padding: 20px; border-radius: 5px; border-left: 4px solid #007cba; }
                .footer { font-size: 12px; color: #666; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>New Chat Interaction Detected</h2>
                <div class="content">
                    <p><strong>Initial Message:</strong><br>
                    <?php echo nl2br(esc_html($user_message)); ?></p>
                    <hr>
                    <p><strong>Visitor Info:</strong><br>
                    IP: <?php echo esc_html($ip); ?><br>
                    Browser: <?php echo esc_html($user_agent); ?></p>
                </div>
                <div class="footer">
                    <p>Sent via <?php echo esc_html(AICA_PLUGIN_NAME); ?> on <?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        $message = ob_get_clean();

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_from_header()
        );

        return wp_mail($admin_email, $subject, $message, $headers);
    }

    /**
     * Send the final full transcript and AI summary to admin when chat ends
     */
    public function send_final_transcript_admin($history, $summary_html = "") {
        $admin_email = get_option('ai_agent_admin_email', get_option('admin_email'));
        $site_name = get_bloginfo('name');
        $site_name = wp_specialchars_decode($site_name, ENT_QUOTES);
        $subject = 'Chat Ended: Full Transcript & Summary - ' . $site_name;
        
        $transcript_html = "";
        foreach($history as $msg) {
            $label = ($msg['role'] === 'user') ? 'Visitor' : 'AI Assistant';
            $transcript_html .= "<p><strong>{$label}:</strong><br>" . nl2br(esc_html($msg['content'])) . "</p><hr style='border:0;border-top:1px solid #eee;'>";
        }

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background: #f4f4f4; padding: 20px; border-radius: 8px; }
                .summary-section { background: #fffbeb; padding: 20px; border-radius: 5px; border: 1px solid #fef3c7; margin-bottom: 20px; }
                .content { background: white; padding: 20px; border-radius: 5px; border-left: 4px solid #ea580c; }
                .footer { font-size: 11px; color: #999; margin-top: 20px; }
                .ai-summary ul { padding-left: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <?php if (!empty($summary_html)) : ?>
                    <div class="summary-section">
                        <h3 style="margin-top:0; color: #92400e;">AI Conversation Summary</h3>
                        <?php echo $summary_html; ?>
                    </div>
                <?php endif; ?>

                <h2>Full Conversation Transcript</h2>
                <div class="content">
                    <?php echo $transcript_html; ?>
                </div>
                <div class="footer">
                    <p>Session ended at <?php echo date('Y-m-d H:i:s'); ?> | IP: <?php echo esc_html($_SERVER['REMOTE_ADDR']); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        $message = ob_get_clean();

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->get_from_header()
        );

        return wp_mail($admin_email, $subject, $message, $headers);
    }

    /**
     * Get the from header for emails
     */
    private function get_from_header() {
        $site_name = get_bloginfo('name');
        $site_name = wp_specialchars_decode($site_name, ENT_QUOTES);
        $admin_email = get_option('admin_email');
        
        return "$site_name <$admin_email>";
    }

}