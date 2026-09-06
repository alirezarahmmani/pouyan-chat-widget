<?php
/**
 * Uninstall cleanup.
 *
 * @package AIChatWidget
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ai_chat_widget_api_key' );
delete_option( 'ai_chat_widget_api_key_status' );
delete_option( 'ai_chat_widget_settings' );

if ( is_multisite() ) {
	delete_site_option( 'ai_chat_widget_api_key' );
	delete_site_option( 'ai_chat_widget_api_key_status' );
	delete_site_option( 'ai_chat_widget_settings' );
}
