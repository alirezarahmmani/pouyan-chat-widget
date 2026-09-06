<?php
/**
 * API credential validator.
 *
 * @package AIChatWidget
 */

namespace AI_Chat_Widget\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Interprets the SaaS API-key validation contract. */
final class Api_Validator {
	/** @var Api_Client */
	private $client;

	public function __construct( Api_Client $client ) {
		$this->client = $client;
	}

	/** Validate key and return true or WP_Error. */
	public function validate( $api_key ) {
		$result = $this->client->validate_api_key( $api_key );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( empty( $result['valid'] ) ) {
			return new \WP_Error( 'ai_chat_invalid_api_key', __( 'The API key is not valid for this site.', 'ai-chat-widget' ) );
		}

		return true;
	}
}
