<?php
/**
 * Handles all email functionality for the AI Chat System
 */
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_Mail {
    private $plugin_slug;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
    }

    /**
     * Define allowed HTML for rich email transcripts and summaries
     */
    private function get_allowed_html_tags() {
        return array(
            'div'    => array('class' => array(), 'style' => array(), 'id' => array()),
            'span'   => array('class' => array(), 'style' => array()),
            'p'      => array('class' => array(), 'style' => array()),
            'br'     => array(),
            'hr'     => array('style' => array(), 'class' => array()),
            
            // Formatting
            'strong' => array(),
            'em'     => array(),
            'b'      => array(),
            'i'      => array(),
            'u'      => array(),
            'blockquote' => array('style' => array()),
            'code'   => array(),
            'pre'    => array('class' => array()),
            
            // Lists
            'ul'     => array('style' => array(), 'class' => array()),
            'ol'     => array('style' => array(), 'class' => array()),
            'li'     => array('style' => array(), 'class' => array()),
            
            // Headings
            'h1'     => array('style' => array()),
            'h2'     => array('style' => array()),
            'h3'     => array('style' => array()),
            'h4'     => array('style' => array()),
            
            // Links
            'a'      => array(
                'href'   => array(),
                'title'  => array(),
                'target' => array(),
                'style'  => array(),
                'class'  => array(),
            ),

            // Tables
            'table'  => array('style' => array(), 'class' => array(), 'border' => array()),
            'thead'  => array(),
            'tbody'  => array(),
            'tr'     => array(),
            'th'     => array('style' => array()),
            'td'     => array('style' => array()),
        );
    }

    /**
     * Send notification to admin when a new chat session starts
     */
    public function send_chat_notification_admin($user_message) {
        $admin_email = get_option('ai_agent_admin_email', get_option('admin_email'));
        $site_name   = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        
        $subject = sprintf( __( 'New AI Chat Session Started - %s', 'aica-plugin' ), $site_name );
        
        $ip         = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'Unknown';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : 'Unknown';
        
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
                <h2><?php esc_html_e( 'New Chat Interaction Detected', 'aica-plugin' ); ?></h2>
                <div class="content">
                    <p><strong><?php esc_html_e( 'Initial Message:', 'aica-plugin' ); ?></strong><br>
                    <?php echo nl2br(esc_html($user_message)); ?></p>
                    <hr>
                    <p><strong><?php esc_html_e( 'Visitor Info:', 'aica-plugin' ); ?></strong><br>
                    <?php esc_html_e( 'IP:', 'aica-plugin' ); ?> <?php echo esc_html($ip); ?><br>
                    <?php esc_html_e( 'Browser:', 'aica-plugin' ); ?> <?php echo esc_html($user_agent); ?></p>
                </div>
                <div class="footer">
                    <p><?php printf( esc_html__( 'Sent via %1$s on %2$s', 'aica-plugin' ), esc_html(AICA_PLUGIN_NAME), esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format')))); ?></p>
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

        return wp_mail($admin_email, wp_strip_all_tags($subject), $message, $headers);
    }

    /**
     * Send the final full transcript and AI summary to admin when chat ends
     */
    public function send_final_transcript_admin($history, $summary_html = "") {
        $admin_email = get_option('ai_agent_admin_email', get_option('admin_email'));
        $site_name   = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        
        $subject = sprintf( __( 'Chat Ended: Full Transcript & Summary - %s', 'aica-plugin' ), $site_name );
        
        // Call the new helper function
        $allowed_html = $this->get_allowed_html_tags();

        $transcript_html = "";
        foreach($history as $msg) {
            $label = ($msg['role'] === 'user') ? __( 'Visitor', 'aica-plugin' ) : __( 'AI Assistant', 'aica-plugin' );
            
            // Clean content using our allowed tag list
            $content = wp_kses($msg['content'], $allowed_html);
            
            $transcript_html .= "<p><strong>" . esc_html($label) . ":</strong><br>" . nl2br($content) . "</p><hr style='border:0;border-top:1px solid #eee;'>";
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'Unknown';

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
                .ai-summary ul, .content ul { padding-left: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <?php if (!empty($summary_html)) : ?>
                    <div class="summary-section">
                        <h3 style="margin-top:0; color: #92400e;"><?php esc_html_e( 'AI Conversation Summary', 'aica-plugin' ); ?></h3>
                        <?php echo wp_kses($summary_html, $allowed_html); ?>
                    </div>
                <?php endif; ?>

                <h2><?php esc_html_e( 'Full Conversation Transcript', 'aica-plugin' ); ?></h2>
                <div class="content">
                    <?php echo $transcript_html; ?>
                </div>
                <div class="footer">
                    <p><?php printf( esc_html__( 'Session ended at %1$s | IP: %2$s', 'aica-plugin' ), esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'))), esc_html($ip)); ?></p>
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

        return wp_mail($admin_email, wp_strip_all_tags($subject), $message, $headers);
    }

    /**
     * Get the from header for emails
     */
    private function get_from_header() {
        $site_name   = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $admin_email = get_option('admin_email');
        $site_name = str_replace(array('"', '<', '>'), '', $site_name);
        return sprintf( '%1$s <%2$s>', $site_name, $admin_email );
    }
}