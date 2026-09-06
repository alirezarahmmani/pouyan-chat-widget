<?php
/**
 * Temporary session service.
 *
 * @package AIChatWidget
 */

namespace AI_Chat_Widget\Api;

use AI_Chat_Widget\Security\Security;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Exchanges the server-only API key for a short-lived WebSocket token. */
final class Session_Service {
	/** @var Api_Client */
	private $client;

	public function __construct( Api_Client $client ) {
		$this->client = $client;
	}

	/** Create and normalize a frontend-safe session. */
	public function create( $conversation_id = '' ) {
		$status  = get_option( 'ai_chat_widget_api_key_status', array() );
		$api_key = Security::decrypt( get_option( 'ai_chat_widget_api_key', '' ) );

		if ( empty( $status['valid'] ) || '' === $api_key ) {
			return new WP_Error( 'ai_chat_not_configured', __( 'Chat is not configured.', 'ai-chat-widget' ), array( 'status' => 503 ) );
		}

		$result = $this->client->create_session( $api_key, $conversation_id );
		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			if ( is_array( $error_data ) && in_array( (int) ( $error_data['status'] ?? 0 ), array( 401, 403 ), true ) ) {
				update_option( 'ai_chat_widget_api_key_status', array( 'valid' => false, 'checked_at' => time() ), false );
			}
			return $result;
		}

		$token         = isset( $result['token'] ) && is_string( $result['token'] ) ? $result['token'] : '';
		$websocket_url = isset( $result['websocket_url'] ) && is_string( $result['websocket_url'] ) ? esc_url_raw( $result['websocket_url'], array( 'wss' ) ) : '';

		if ( '' === $token || strlen( $token ) > 4096 || 'wss' !== wp_parse_url( $websocket_url, PHP_URL_SCHEME ) ) {
			return new WP_Error( 'ai_chat_invalid_session', __( 'The chat service returned an invalid session.', 'ai-chat-widget' ), array( 'status' => 502 ) );
		}

		return array(
			'token'           => $token,
			'websocket_url'   => $websocket_url,
			'expires_at'      => isset( $result['expires_at'] ) && is_scalar( $result['expires_at'] ) ? sanitize_text_field( (string) $result['expires_at'] ) : '',
			'conversation_id' => isset( $result['conversation_id'] ) ? Security::sanitize_conversation_id( $result['conversation_id'] ) : $conversation_id,
		);
	}
}
