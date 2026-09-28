<?php

/** Frontend widget view. @package AIChatWidget */
if (! defined('ABSPATH')) {
	exit;
}
$acw_soon    = __('غیرفعال', 'ai-chat-widget');
/* The admin settings page renders this same view as a static, non-interactive preview. */
$acw_preview = ! empty($acw_preview);
?>
<aside id="<?php echo $acw_preview ? 'ai-chat-widget-preview' : 'ai-chat-widget'; ?>" class="ai-chat-widget ai-chat-widget--<?php echo esc_attr($settings['position']); ?><?php echo $acw_preview ? ' ai-chat-widget--preview' : ''; ?>" <?php echo $acw_preview ? ' data-state="connected" inert aria-hidden="true"' : ''; ?> style="--ai-chat-accent: <?php echo esc_attr($settings['primary_color']); ?>" aria-label="<?php echo esc_attr($settings['title']); ?>">
	<section id="ai-chat-widget-panel" class="ai-chat-widget__panel" dir="<?php echo esc_attr($settings['direction']); ?>" <?php echo $acw_preview ? '' : ' hidden'; ?> aria-live="polite">
		<header class="ai-chat-widget__header">
			<div class="ai-chat-widget__identity">
				<span class="ai-chat-widget__avatar" aria-hidden="true">
					<svg viewBox="0 0 24 24">
						<path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3zM18.5 15l.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9.9-2.1z" fill="currentColor" />
					</svg>
					<span class="ai-chat-widget__signal"></span>
				</span>
				<div>
					<h2><?php echo esc_html($settings['title']); ?></h2>
					<p class="ai-chat-widget__status"><?php $acw_preview ? esc_html_e('Online', 'ai-chat-widget') : esc_html_e('Ready when you are', 'ai-chat-widget'); ?></p>
				</div>
			</div>
			<button class="ai-chat-widget__close ai-chat-widget__icon-btn" type="button" aria-label="<?php esc_attr_e('Close chat', 'ai-chat-widget'); ?>">
				<svg aria-hidden="true" viewBox="0 0 24 24">
					<path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
				</svg>
			</button>
		</header>
		<div class="ai-chat-widget__messages" role="log" aria-label="<?php esc_attr_e('Chat messages', 'ai-chat-widget'); ?>">
			<div class="ai-chat-widget__message ai-chat-widget__message--assistant" dir="auto"><?php echo esc_html($settings['welcome']); ?></div>
			<?php if ($acw_preview) : ?>
				<div class="ai-chat-widget__message ai-chat-widget__message--user" dir="auto" data-acw-sample="user"></div>
				<div class="ai-chat-widget__message ai-chat-widget__message--assistant" dir="auto" data-acw-sample="reply"></div>
			<?php endif; ?>
		</div>
		<form class="ai-chat-widget__composer is-empty">
			<div class="ai-chat-widget__field">
				<label class="screen-reader-text" for="<?php echo $acw_preview ? 'ai-chat-widget-preview-message' : 'ai-chat-widget-message'; ?>"><?php esc_html_e('Your message', 'ai-chat-widget'); ?></label>
				<textarea id="<?php echo $acw_preview ? 'ai-chat-widget-preview-message' : 'ai-chat-widget-message'; ?>" rows="1" maxlength="4000" dir="auto" placeholder="<?php esc_attr_e('Ask anything…', 'ai-chat-widget'); ?>" <?php echo $acw_preview ? '' : ' required'; ?>></textarea>
				<div class="ai-chat-widget__toolbar">
					<div class="ai-chat-widget__tools">
						<button class="ai-chat-widget__tool ai-chat-widget__icon-btn" type="button" aria-disabled="true" data-tooltip="<?php echo esc_attr($acw_soon); ?>" aria-label="<?php echo esc_attr(__('Attach file', 'ai-chat-widget') . ' — ' . $acw_soon); ?>">
							<svg aria-hidden="true" viewBox="0 0 24 24">
								<path d="M20.5 11.5l-8.1 8.1a5 5 0 01-7.1-7.1l8.5-8.5a3.3 3.3 0 014.7 4.7l-8.5 8.5a1.7 1.7 0 01-2.4-2.4l7.8-7.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</button>
						<button class="ai-chat-widget__tool ai-chat-widget__icon-btn" type="button" aria-disabled="true" data-tooltip="<?php echo esc_attr($acw_soon); ?>" aria-label="<?php echo esc_attr(__('Voice message', 'ai-chat-widget') . ' — ' . $acw_soon); ?>">
							<svg aria-hidden="true" viewBox="0 0 24 24">
								<rect x="9" y="3" width="6" height="11" rx="3" fill="none" stroke="currentColor" stroke-width="1.8" />
								<path d="M5.5 11a6.5 6.5 0 0013 0M12 17.5V21" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
							</svg>
						</button>
					</div>
					<div class="ai-chat-widget__actions">
						<span class="ai-chat-widget__counter" aria-hidden="true"></span>
						<button class="ai-chat-widget__send" type="submit" aria-label="<?php esc_attr_e('Send message', 'ai-chat-widget'); ?>">
							<svg aria-hidden="true" viewBox="0 0 24 24">
								<path d="M12 19V5M6 11l6-6 6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</button>
					</div>
				</div>
			</div>
			<p class="ai-chat-widget__hint"><?php esc_html_e('Enter to send · Shift + Enter for a new line', 'ai-chat-widget'); ?></p>
		</form>
	</section>
	<button class="ai-chat-widget__launcher" type="button" aria-expanded="false" aria-controls="ai-chat-widget-panel" aria-label="<?php esc_attr_e('Open chat', 'ai-chat-widget'); ?>">
		<svg class="ai-chat-widget__launcher-open" aria-hidden="true" viewBox="0 0 24 24">
			<path d="M4 12a8 8 0 1 1 3.4 6.5L4 19.5l1-3.3A8 8 0 0 1 4 12z" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" />
			<circle cx="8.5" cy="12" r="1.1" fill="currentColor" />
			<circle cx="12" cy="12" r="1.1" fill="currentColor" />
			<circle cx="15.5" cy="12" r="1.1" fill="currentColor" />
		</svg>
		<svg class="ai-chat-widget__launcher-close" aria-hidden="true" viewBox="0 0 24 24">
			<path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
		</svg>
	</button>
</aside>