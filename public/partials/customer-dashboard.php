<?php
defined( 'ABSPATH' ) || exit;

// Fetch settings
$logo_id       = get_option('ai_agent_logo_id');
$logo_url      = $logo_id ? wp_get_attachment_url($logo_id) : esc_url( WPPP_PLUGIN_URL . 'assets/images/default-avater.svg' );
$display_name  = get_option('ai_agent_display_name', __( 'AI Assistant', 'wppp-plugin' ) );
$welcome_msg   = get_option('ai_agent_welcome_msg', __( "Hi there! I'm your friendly AI Assistant.\nHow can I help you today?", 'wppp-plugin' ) );
$placeholder   = get_option('ai_agent_placeholder', __( 'Message...', 'wppp-plugin' ) );
$primary_color = get_option('ai_agent_primary_color', '#000000');

$welcome_lines = array_filter(explode("\n", str_replace("\r", "", $welcome_msg)));
$theme_mode    = get_option('ai_agent_theme_mode', 'light');

// Ensure $chat_history exists
$chat_history = isset($chat_history) ? $chat_history : array();
?>

<div id="ai-chat-wrapper" class="theme-<?php echo esc_attr($theme_mode); ?>">    
    <div id="ai-chat-window" class="hidden">
        <div class="chat-header">
            <div class="header-info">
                <img src="<?php echo esc_url($logo_url); ?>" class="header-logo" alt="<?php echo esc_attr($display_name); ?>">
                <span class="header-name"><?php echo esc_html($display_name); ?></span>
            </div>
            <div class="header-actions">
                <button id="chat-menu-btn" class="header-action-btn" title="<?php esc_attr_e( 'Menu', 'wppp-plugin' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2.5">
                        <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                        <circle cx="12" cy="5" r="1.5" fill="currentColor"></circle>
                        <circle cx="12" cy="19" r="1.5" fill="currentColor"></circle>
                    </svg>
                </button>
                <div id="chat-options-popup" class="hidden">
                    <button class="menu-item" id="menu-start-new">
                        <i class="fa fa-plus-circle"></i> <?php esc_html_e( 'Start a new chat', 'wppp-plugin' ); ?>
                    </button>
                    <button class="menu-item" id="menu-end-chat">
                        <i class="fa fa-times-circle"></i> <?php esc_html_e( 'End chat', 'wppp-plugin' ); ?>
                    </button>
                    <button class="menu-item" id="menu-view-recent">
                        <i class="fa fa-history"></i> <?php esc_html_e( 'View recent chats', 'wppp-plugin' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <div id="recent-chats-view" class="hidden">
            <div class="recent-header">
                <button id="back-to-chat" class="header-action-btn">
                    <i class="fa fa-arrow-left"></i>
                </button>
                <span><?php esc_html_e( 'Recent Conversations', 'wppp-plugin' ); ?></span>
            </div>
            <div id="recent-chats-list">
                <div class="recent-loading">
                    <i class="fa fa-spinner fa-spin"></i>
                    <span><?php esc_html_e( 'Loading...', 'wppp-plugin' ); ?></span>
                </div>
            </div>
        </div>

        <div id="chat-confirm-overlay" class="hidden">
            <div class="confirm-box">
                <h4 id="confirm-title"><?php esc_html_e( 'Clear conversation?', 'wppp-plugin' ); ?></h4>
                <p id="confirm-desc"><?php esc_html_e( 'This will delete your current chat history and start a fresh session.', 'wppp-plugin' ); ?></p>
                <div class="confirm-actions">
                    <button id="confirm-cancel" class="btn-secondary"><?php esc_html_e( 'Cancel', 'wppp-plugin' ); ?></button>
                    <button id="confirm-execute" class="btn-primary"><?php esc_html_e( 'Confirm', 'wppp-plugin' ); ?></button>
                </div>
            </div>
        </div>

        <div id="chat-messages">
            <?php foreach ($welcome_lines as $index => $line): ?>
                <div class="msg ai welcome-msg">
                    <div class="msg-avatar-container">
                        <?php if (0 === $index): ?><img src="<?php echo esc_url($logo_url); ?>" class="msg-avatar" alt=""><?php endif; ?>
                    </div>
                    <div class="msg-content-wrapper">
                        <?php if (0 === $index): ?><div class="msg-bot-name"><?php echo esc_html($display_name); ?></div><?php endif; ?>
                        <div class="bubble"><?php echo esc_html($line); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($chat_history)): ?>
                <?php foreach ($chat_history as $msg): 
                    $role = ($msg['role'] === 'user') ? 'user' : 'ai';
                    $is_bot = ($role === 'ai');
                    $sent_at = isset($msg['time']) ? $msg['time'] : time();
                    $diff = time() - $sent_at;
                    
                    if ($diff < 60) {
                        $time_text = __( 'just now', 'wppp-plugin' );
                    } else {
                        /* translators: %d: minutes ago */
                        $time_text = sprintf( _n( '%d min ago', '%d mins ago', floor($diff/60), 'wppp-plugin' ), floor($diff/60) );
                    }
                ?>
                    <div class="msg <?php echo esc_attr($role); ?>">
                        <?php if ($is_bot): ?>
                            <div class="msg-avatar-container">
                                <img src="<?php echo esc_url($logo_url); ?>" class="msg-avatar" alt="">
                            </div>
                        <?php endif; ?>
                        <div class="msg-content-wrapper">
                            <?php if ($is_bot): ?>
                                <div class="msg-bot-name"><?php echo esc_html($display_name); ?></div>
                            <?php endif; ?>
                            <div class="bubble"><?php echo wp_kses_post($msg['content']); ?></div>
                            <?php if ($is_bot): ?>
                                <div class="msg-footer">
                                    <span class="msg-time" data-time="<?php echo esc_attr($sent_at); ?>"><?php echo esc_html($time_text); ?></span>
                                    <div class="msg-actions">
                                        <button class="ai-action-btn copy-msg" title="<?php esc_attr_e( 'Copy', 'wppp-plugin' ); ?>"><i class="fa fa-clone"></i></button>
                                        <button class="ai-action-btn regen-msg" title="<?php esc_attr_e( 'Regenerate', 'wppp-plugin' ); ?>"><i class="fa fa-refresh"></i></button>
                                        <button class="ai-action-btn rate-msg like" data-rating="like" title="<?php esc_attr_e( 'Helpful', 'wppp-plugin' ); ?>"><i class="fa fa-thumbs-o-up"></i></button>
                                        <button class="ai-action-btn rate-msg dislike" data-rating="dislike" title="<?php esc_attr_e( 'Not helpful', 'wppp-plugin' ); ?>"><i class="fa fa-thumbs-o-down"></i></button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="chat-input-area">
            <div class="powered-by-brand"><?php printf( esc_html__( 'Powered by %s', 'wppp-plugin' ), 'AutomataIQ' ); ?></div>
            <div class="input-wrapper">
                <textarea id="chat-input" placeholder="<?php echo esc_attr($placeholder); ?>" rows="1"></textarea>
                <button id="chat-send" class="action-icon" aria-label="<?php esc_attr_e( 'Send message', 'wppp-plugin' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div id="ai-chat-welcome-popup" class="hidden welcome-text-click">
        <div class="popup-bubbles-stack">
            <?php foreach ($welcome_lines as $index => $line): ?>
                <div class="popup-bubble-item">
                    <?php if (0 === $index): ?><button id="close-welcome-popup" title="<?php esc_attr_e( 'Close', 'wppp-plugin' ); ?>">&times;</button><?php endif; ?>
                    <?php echo esc_html($line); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <button id="ai-chat-bubble" class="launcher-btn" style="background-color: <?php echo esc_attr($primary_color); ?>;" aria-label="<?php esc_attr_e( 'Open Chat', 'wppp-plugin' ); ?>">
        <div class="launcher-img-container <?php echo (get_option('ai_agent_bubble_icon', 'default') === 'default') ? 'is-default-icon' : ''; ?>">
            <?php if (get_option('ai_agent_bubble_icon', 'default') === 'logo'): ?>
                <img src="<?php echo esc_url($logo_url); ?>" class="launcher-img" alt="">
            <?php else: ?>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            <?php endif; ?>
        </div>
        <div class="launcher-close-icon hidden">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </div>
    </button>
</div>