<?php
defined( 'ABSPATH' ) || exit;

// Fetch settings
$logo_id       = get_option('ai_agent_logo_id');
$logo_url      = $logo_id ? wp_get_attachment_url($logo_id) : AICA_PLUGIN_URL . 'assets/images/default-avater.svg';
$display_name  = get_option('ai_agent_display_name', 'AI Assistant');
$welcome_msg   = get_option('ai_agent_welcome_msg', "Hi there! I'm your friendly AI Assistant.\nHow can I help you today?");
$placeholder   = get_option('ai_agent_placeholder', 'Message...');
$primary_color = get_option('ai_agent_primary_color', '#000000');

$welcome_lines = array_filter(explode("\n", str_replace("\r", "", $welcome_msg)));
$theme_mode = get_option('ai_agent_theme_mode', 'light');

?>

<div id="ai-chat-wrapper" class="theme-<?php echo esc_attr($theme_mode); ?>">    
<div id="ai-chat-window" class="hidden">
        <div class="chat-header">
            <div class="header-info">
                <img src="<?php echo esc_url($logo_url); ?>" class="header-logo">
                <span class="header-name"><?php echo esc_html($display_name); ?></span>
            </div>
            <div class="header-actions">
                <button id="chat-menu-btn" class="header-action-btn" title="Menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2.5">
                        <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                        <circle cx="12" cy="5" r="1.5" fill="currentColor"></circle>
                        <circle cx="12" cy="19" r="1.5" fill="currentColor"></circle>
                    </svg>
                </button>
                <div id="chat-options-popup" class="hidden">
                    <button class="menu-item" id="menu-start-new">
                        <i class="fa fa-plus-circle"></i> Start a new chat
                    </button>
                    <button class="menu-item" id="menu-end-chat">
                        <i class="fa fa-times-circle"></i> End chat
                    </button>
                    <button class="menu-item" id="menu-view-recent">
                        <i class="fa fa-history"></i> View recent chats
                    </button>
                </div>
            </div>
        </div>

        <div id="recent-chats-view" class="hidden">
            <div class="recent-header">
                <button id="back-to-chat" class="header-action-btn">
                    <i class="fa fa-arrow-left"></i>
                </button>
                <span>Recent Conversations</span>
            </div>
            <div id="recent-chats-list">
                <div class="recent-loading">
                    <i class="fa fa-spinner fa-spin"></i>
                    <span>Loading...</span>
                </div>
            </div>
        </div>

        <div id="chat-confirm-overlay" class="hidden">
            <div class="confirm-box">
                <h4 id="confirm-title">Clear conversation?</h4>
                <p id="confirm-desc">This will delete your current chat history and start a fresh session.</p>
                <div class="confirm-actions">
                    <button id="confirm-cancel" class="btn-secondary">Cancel</button>
                    <button id="confirm-execute" class="btn-primary">Confirm</button>
                </div>
            </div>
        </div>

        <div id="chat-messages">
            <?php foreach ($welcome_lines as $index => $line): ?>
                <div class="msg ai welcome-msg">
                    <div class="msg-avatar-container">
                        <?php if ($index === 0): ?><img src="<?php echo esc_url($logo_url); ?>" class="msg-avatar"><?php endif; ?>
                    </div>
                    <div class="msg-content-wrapper">
                        <?php if ($index === 0): ?><div class="msg-bot-name"><?php echo esc_html($display_name); ?></div><?php endif; ?>
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
                    $time_text = ($diff < 60) ? 'just now' : floor($diff/60) . 'm ago';
                ?>
                    <div class="msg <?php echo $role; ?>">
                        <?php if ($is_bot): ?>
                            <div class="msg-avatar-container">
                                <img src="<?php echo esc_url($logo_url); ?>" class="msg-avatar">
                            </div>
                        <?php endif; ?>
                        <div class="msg-content-wrapper">
                            <?php if ($is_bot): ?>
                                <div class="msg-bot-name"><?php echo esc_html($display_name); ?></div>
                            <?php endif; ?>
                            <div class="bubble"><?php echo wp_kses_post($msg['content']); ?></div>
                            <?php if ($is_bot): ?>
                                <div class="msg-footer">
                                    <span class="msg-time" data-time="<?php echo $sent_at; ?>"><?php echo $time_text; ?></span>
                                    <div class="msg-actions">
                                        <button class="ai-action-btn copy-msg" title="Copy"><i class="fa fa-clone"></i></button>
                                        <button class="ai-action-btn regen-msg" title="Regenerate"><i class="fa fa-refresh"></i></button>
                                        <button class="ai-action-btn rate-msg like" data-rating="like"><i class="fa fa-thumbs-o-up"></i></button>
                                        <button class="ai-action-btn rate-msg dislike" data-rating="dislike"><i class="fa fa-thumbs-o-down"></i></button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="chat-input-area">
            <div class="powered-by-brand">Powered by AutomataIQ</div>
            <div class="input-wrapper">
                <textarea id="chat-input" placeholder="<?php echo esc_attr($placeholder); ?>" rows="1"></textarea>
                <button id="chat-send" class="action-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div id="ai-chat-welcome-popup" class="hidden welcome-text-click">
        <div class="popup-bubbles-stack">
            <?php foreach ($welcome_lines as $index => $line): ?>
                <div class="popup-bubble-item">
                    <?php if ($index === 0): ?><button id="close-welcome-popup" title="Close">&times;</button><?php endif; ?>
                    <?php echo esc_html($line); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <button id="ai-chat-bubble" class="launcher-btn" style="background-color: <?php echo esc_attr($primary_color); ?>;">
        <div class="launcher-img-container <?php echo (get_option('ai_agent_bubble_icon', 'default') === 'default') ? 'is-default-icon' : ''; ?>">
            <?php if (get_option('ai_agent_bubble_icon', 'default') === 'logo'): ?>
                <img src="<?php echo esc_url($logo_url); ?>" class="launcher-img">
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