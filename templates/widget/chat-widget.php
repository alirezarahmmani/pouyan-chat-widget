<?php
/** Frontend widget view. @package AIChatWidget */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<aside id="ai-chat-widget" class="ai-chat-widget ai-chat-widget--<?php echo esc_attr( $settings['position'] ); ?>" style="--ai-chat-accent: <?php echo esc_attr( $settings['primary_color'] ); ?>" aria-label="<?php echo esc_attr( $settings['title'] ); ?>">
	<section id="ai-chat-widget-panel" class="ai-chat-widget__panel" hidden aria-live="polite">
		<header class="ai-chat-widget__header">
			<div><span class="ai-chat-widget__signal" aria-hidden="true"></span><div><h2><?php echo esc_html( $settings['title'] ); ?></h2><p class="ai-chat-widget__status"><?php esc_html_e( 'Ready when you are', 'ai-chat-widget' ); ?></p></div></div>
			<button class="ai-chat-widget__close" type="button" aria-label="<?php esc_attr_e( 'Close chat', 'ai-chat-widget' ); ?>">&times;</button>
		</header>
		<div class="ai-chat-widget__messages" role="log" aria-label="<?php esc_attr_e( 'Chat messages', 'ai-chat-widget' ); ?>">
			<div class="ai-chat-widget__message ai-chat-widget__message--assistant"><?php echo esc_html( $settings['welcome'] ); ?></div>
		</div>
		<form class="ai-chat-widget__composer">
			<label class="screen-reader-text" for="ai-chat-widget-message"><?php esc_html_e( 'Your message', 'ai-chat-widget' ); ?></label>
			<textarea id="ai-chat-widget-message" rows="1" maxlength="4000" placeholder="<?php esc_attr_e( 'Type your message…', 'ai-chat-widget' ); ?>" required></textarea>
			<button type="submit" aria-label="<?php esc_attr_e( 'Send message', 'ai-chat-widget' ); ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
		</form>
	</section>
	<button class="ai-chat-widget__launcher" type="button" aria-expanded="false" aria-controls="ai-chat-widget-panel" aria-label="<?php esc_attr_e( 'Open chat', 'ai-chat-widget' ); ?>">
		<span class="ai-chat-widget__launcher-mark" aria-hidden="true"><i></i><i></i><i></i></span><span><?php esc_html_e( 'Chat', 'ai-chat-widget' ); ?></span>
	</button>
</aside>
