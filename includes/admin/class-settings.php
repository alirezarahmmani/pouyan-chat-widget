<?php
/** Settings registration. @package AIChatWidget */

namespace AI_Chat_Widget\Admin;

use AI_Chat_Widget\Api\Api_Validator;
use AI_Chat_Widget\Security\Security;
use AI_Chat_Widget\Widget\Widget_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Registers and validates plugin options. */
final class Settings {
	/** @var Api_Validator */
	private $validator;
	/** @var Widget_Settings */
	private $widget_settings;

	public function __construct( Api_Validator $validator, Widget_Settings $widget_settings ) {
		$this->validator       = $validator;
		$this->widget_settings = $widget_settings;
	}

	/** Register options with Settings API. */
	public function register() {
		register_setting(
			'ai_chat_widget_settings_group',
			'ai_chat_widget_api_key',
			array( 'sanitize_callback' => array( $this, 'sanitize_api_key' ) )
		);
		register_setting(
			'ai_chat_widget_settings_group',
			'ai_chat_widget_settings',
			array(
				'type'              => 'array',
				'default'           => $this->widget_settings->defaults(),
				'sanitize_callback' => array( $this->widget_settings, 'sanitize' ),
			)
		);
	}

	/** Validate before encrypting and saving a new API key. */
	public function sanitize_api_key( $input ) {
		$current = get_option( 'ai_chat_widget_api_key', '' );

		// WordPress may run option sanitization twice while adding a new option.
		if ( is_string( $input ) && '' !== Security::decrypt( $input ) ) {
			return $input;
		}

		$api_key = Security::sanitize_api_key( $input );

		if ( '' === $api_key ) {
			return $current;
		}

		$valid = $this->validator->validate( $api_key );
		if ( is_wp_error( $valid ) ) {
			add_settings_error( 'ai_chat_widget_api_key', 'invalid_api_key', $valid->get_error_message(), 'error' );
			return $current;
		}

		$encrypted = Security::encrypt( $api_key );
		if ( '' === $encrypted ) {
			add_settings_error( 'ai_chat_widget_api_key', 'encryption_failed', __( 'رمزنگاری کلید API روی این سرور امکان‌پذیر نبود.', 'ai-chat-widget' ), 'error' );
			return $current;
		}

		update_option(
			'ai_chat_widget_api_key_status',
			array( 'valid' => true, 'checked_at' => time() ),
			false
		);
		add_settings_error( 'ai_chat_widget_api_key', 'api_key_valid', __( 'کلید API اعتبارسنجی و به‌صورت امن ذخیره شد.', 'ai-chat-widget' ), 'success' );
		return $encrypted;
	}

	/** Expose safe settings for the admin template. */
	public function view_data() {
		$status = get_option( 'ai_chat_widget_api_key_status', array() );
		return array(
			'widget'    => $this->widget_settings->get(),
			'connected' => ! empty( $status['valid'] ) && '' !== Security::decrypt( get_option( 'ai_chat_widget_api_key', '' ) ),
			'checked'   => ! empty( $status['checked_at'] ) ? (int) $status['checked_at'] : 0,
		);
	}
}
