<?php
defined( 'ABSPATH' ) || exit;

// Fetch settings
$logo_id       = get_option('ai_agent_logo_id');
$logo_url      = $logo_id ? wp_get_attachment_url($logo_id) : AICA_PLUGIN_URL . 'assets/images/default-avatar.png';
$display_name  = get_option('ai_agent_display_name', 'FOX AI Assistant');
$welcome_msg   = get_option('ai_agent_welcome_msg', "Hi there! I'm your friendly FOX AI Assistant.\nHow can I help you today?");
$placeholder   = get_option('ai_agent_placeholder', 'Message...');
$primary_color = get_option('ai_agent_primary_color', '#000000');

// Split messages for bubbles
$welcome_lines = array_filter(explode("\n", str_replace("\r", "", $welcome_msg)));
?>

<div id="ai-chat-wrapper">
    <div id="ai-chat-window" class="hidden">
        <div class="chat-header">
            <div class="header-info">
                <img src="<?php echo esc_url($logo_url); ?>" class="header-logo">
                <span class="header-name"><?php echo esc_html($display_name); ?></span>
            </div>
            <button id="chat-close-btn" class="header-action-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>

        <div id="chat-messages">
            <?php foreach ($welcome_lines as $index => $line): ?>
                <div class="msg ai">
                    <div class="msg-avatar-container">
                        <?php if ($index === 0): ?>
                            <img src="<?php echo esc_url($logo_url); ?>" class="msg-avatar">
                        <?php endif; ?>
                    </div>
                    <div class="msg-content-wrapper">
                        <?php if ($index === 0): ?>
                            <div class="msg-bot-name"><?php echo esc_html($display_name); ?></div>
                        <?php endif; ?>
                        <div class="bubble"><?php echo esc_html($line); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-input-area">
            <div class="powered-by-brand">Powered by Grok AI</div>
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
                    <?php if ($index === 0): ?>
                        <button id="close-welcome-popup" title="Close">&times;</button>
                    <?php endif; ?>
                    <?php echo esc_html($line); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <button id="ai-chat-bubble" class="launcher-btn" style="background-color: <?php echo esc_attr($primary_color); ?>;">
    <div class="launcher-img-container <?php echo (get_option('ai_agent_bubble_icon', 'default') === 'default') ? 'is-default-icon' : ''; ?>">
        <?php if (get_option('ai_agent_bubble_icon', 'default') === 'logo' && $logo_id): ?>
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