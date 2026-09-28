<?php
/** Widget settings model. @package AIChatWidget */

namespace AI_Chat_Widget\Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Owns widget defaults and sanitization. */
final class Widget_Settings {
	/** Get defaults. */
	public function defaults() {
		return array(
			'enabled'          => true,
			'show_without_api' => false,
			'title'         => __( 'Ask our AI', 'ai-chat-widget' ),
			'welcome'       => __( 'Hi! How can I help you today?', 'ai-chat-widget' ),
			'primary_color' => '#2855d9',
			'position'      => 'right',
			'direction'     => 'ltr',
		);
	}

	/** Get merged settings. */
	public function get() {
		return wp_parse_args( get_option( 'ai_chat_widget_settings', array() ), $this->defaults() );
	}

	/** Sanitize settings option. */
	public function sanitize( $input ) {
		$defaults = $this->defaults();
		$input    = is_array( $input ) ? $input : array();
		$color    = isset( $input['primary_color'] ) ? sanitize_hex_color( $input['primary_color'] ) : '';
		$position = isset( $input['position'] ) && in_array( $input['position'], array( 'left', 'right' ), true ) ? $input['position'] : $defaults['position'];
		$direction = isset( $input['direction'] ) && in_array( $input['direction'], array( 'ltr', 'rtl' ), true ) ? $input['direction'] : $defaults['direction'];

		return array(
			'enabled'          => ! empty( $input['enabled'] ),
			'show_without_api' => ! empty( $input['show_without_api'] ),
			'title'         => isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : $defaults['title'],
			'welcome'       => isset( $input['welcome'] ) ? sanitize_textarea_field( $input['welcome'] ) : $defaults['welcome'],
			'primary_color' => $color ? $color : $defaults['primary_color'],
			'position'      => $position,
			'direction'     => $direction,
		);
	}
}
