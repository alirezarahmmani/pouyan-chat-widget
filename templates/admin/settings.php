<?php

/** Admin settings view. @package AIChatWidget */
if (! defined('ABSPATH')) {
	exit;
}
?>
<div class="wrap acw-admin">
	<header class="acw-admin__header">
		<div>
			<p class="acw-admin__eyebrow"><?php esc_html_e('Secure SaaS connection', 'ai-chat-widget'); ?></p>
			<h1><?php esc_html_e('AI Chat Widget', 'ai-chat-widget'); ?></h1>
			<p><?php esc_html_e('Connect your account, then shape the chat experience locally in WordPress.', 'ai-chat-widget'); ?></p>
		</div>
		<span class="acw-status <?php echo $data['connected'] ? 'is-connected' : 'is-disconnected'; ?>"><span aria-hidden="true"></span><?php echo $data['connected'] ? esc_html__('Connected', 'ai-chat-widget') : esc_html__('Not connected', 'ai-chat-widget'); ?></span>
	</header>
	<?php settings_errors(); ?>
	<form action="options.php" method="post" autocomplete="off">
		<?php settings_fields('ai_chat_widget_settings_group'); ?>
		<section class="acw-card" aria-labelledby="acw-connection-title">
			<div class="acw-card__number" aria-hidden="true">01</div>
			<div class="acw-card__content">
				<h2 id="acw-connection-title"><?php esc_html_e('Connect the service', 'ai-chat-widget'); ?></h2>
				<p><?php esc_html_e('The saved key is encrypted with WordPress salts, used only by PHP, and never printed back into the page.', 'ai-chat-widget'); ?></p>
				<label for="ai-chat-widget-api-key"><?php esc_html_e('API key', 'ai-chat-widget'); ?></label>
				<input id="ai-chat-widget-api-key" name="ai_chat_widget_api_key" type="password" value="" class="regular-text" maxlength="512" spellcheck="false" autocomplete="new-password" placeholder="sk_live_••••••••••••">
				<p class="description"><?php echo $data['connected'] ? esc_html__('Leave blank to keep the existing key. A new key is validated before replacement.', 'ai-chat-widget') : esc_html__('The key is validated with the SaaS backend before storage.', 'ai-chat-widget'); ?></p>
			</div>
		</section>
		<section class="acw-card" aria-labelledby="acw-experience-title">
			<div class="acw-card__number" aria-hidden="true">02</div>
			<div class="acw-card__content">
				<h2 id="acw-experience-title"><?php esc_html_e('Shape the experience', 'ai-chat-widget'); ?></h2>
				<p><?php esc_html_e('These values stay in WordPress and are not fetched from the SaaS backend.', 'ai-chat-widget'); ?></p>
				<label class="acw-toggle"><input type="checkbox" name="ai_chat_widget_settings[enabled]" value="1" <?php checked(! empty($data['widget']['enabled'])); ?>><span><?php esc_html_e('Show widget when the connection is valid', 'ai-chat-widget'); ?></span></label>
				<div class="acw-field-grid">
					<div><label for="acw-title"><?php esc_html_e('Widget title', 'ai-chat-widget'); ?></label><input id="acw-title" name="ai_chat_widget_settings[title]" type="text" value="<?php echo esc_attr($data['widget']['title']); ?>" maxlength="80"></div>
					<div><label for="acw-color"><?php esc_html_e('Accent color', 'ai-chat-widget'); ?></label>
						<div class="acw-color-field"><input id="acw-color" name="ai_chat_widget_settings[primary_color]" type="color" value="<?php echo esc_attr($data['widget']['primary_color']); ?>"><output for="acw-color"><?php echo esc_html(strtoupper($data['widget']['primary_color'])); ?></output></div>
					</div>
				</div>
				<label for="acw-welcome"><?php esc_html_e('Welcome message', 'ai-chat-widget'); ?></label><textarea id="acw-welcome" name="ai_chat_widget_settings[welcome]" rows="3" maxlength="280"><?php echo esc_textarea($data['widget']['welcome']); ?></textarea>
				<fieldset>
					<legend><?php esc_html_e('Screen position', 'ai-chat-widget'); ?></legend>
					<div class="acw-position-options">
						<label><input type="radio" name="ai_chat_widget_settings[position]" value="right" <?php checked('right', $data['widget']['position']); ?>><span><?php esc_html_e('Bottom right', 'ai-chat-widget'); ?></span></label>
						<label><input type="radio" name="ai_chat_widget_settings[position]" value="left" <?php checked('left', $data['widget']['position']); ?>><span><?php esc_html_e('Bottom left', 'ai-chat-widget'); ?></span></label>
					</div>
				</fieldset>
			</div>
		</section>
		<?php submit_button(__('Save changes', 'ai-chat-widget'), 'primary acw-save'); ?>
	</form>
</div>