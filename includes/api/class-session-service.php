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

		$data           = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : array();
		$token          = isset( $data['token'] ) && is_string( $data['token'] ) ? $data['token'] : '';
		$websocket_url  = esc_url_raw( $this->client->get_websocket_url(), array( 'ws', 'wss' ) );
		$websocket_scheme = wp_parse_url( $websocket_url, PHP_URL_SCHEME );

		if ( empty( $result['success'] ) || '' === $token || strlen( $token ) > 4096 || ! in_array( $websocket_scheme, array( 'ws', 'wss' ), true ) ) {
			return new WP_Error( 'ai_chat_invalid_session', __( 'The chat service returned an invalid session.', 'ai-chat-widget' ), array( 'status' => 502 ) );
		}

		return array(
			'token'           => $token,
			'websocket_url'   => $websocket_url,
			'expires_in'      => isset( $data['expires_in'] ) ? max( 0, (int) $data['expires_in'] ) : 0,
			'session_id'      => $conversation_id,
		);
	}

	/** Retrieve and normalize the history of an existing session. */
	public function history( $session_id ) {
		$status  = get_option( 'ai_chat_widget_api_key_status', array() );
		$api_key = Security::decrypt( get_option( 'ai_chat_widget_api_key', '' ) );

		if ( empty( $status['valid'] ) || '' === $api_key ) {
			return new WP_Error( 'ai_chat_not_configured', __( 'Chat is not configured.', 'ai-chat-widget' ), array( 'status' => 503 ) );
		}
		if ( '' === $session_id ) {
			return new WP_Error( 'ai_chat_invalid_session_id', __( 'The chat session is invalid.', 'ai-chat-widget' ), array( 'status' => 400 ) );
		}

		$result = $this->client->get_session_history( $api_key, $session_id );
		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			if ( is_array( $error_data ) && in_array( (int) ( $error_data['status'] ?? 0 ), array( 401, 403 ), true ) ) {
				update_option( 'ai_chat_widget_api_key_status', array( 'valid' => false, 'checked_at' => time() ), false );
			}
			return $result;
		}
		$history = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : $result;

		$messages            = isset( $history['messages'] ) && is_array( $history['messages'] ) ? $history['messages'] : array();
		$normalized_messages = array();

		foreach ( $messages as $message ) {
			if ( ! is_array( $message ) || ! isset( $message['role'], $message['content'] ) || ! is_string( $message['role'] ) || ! is_string( $message['content'] ) ) {
				continue;
			}

			$role = sanitize_key( $message['role'] );
			if ( ! in_array( $role, array( 'user', 'assistant' ), true ) ) {
				continue;
			}

			$normalized_messages[] = array(
				'role'       => $role,
				'content'    => $message['content'],
				'created_at' => isset( $message['created_at'] ) && is_string( $message['created_at'] ) ? sanitize_text_field( $message['created_at'] ) : '',
			);
		}

		return array(
			'session_id' => isset( $history['session_id'] ) && is_string( $history['session_id'] ) ? $history['session_id'] : $session_id,
			'resumed'    => ! empty( $history['resumed'] ),
			'turn_count' => isset( $history['turn_count'] ) ? max( 0, (int) $history['turn_count'] ) : 0,
			'max_turns'  => isset( $history['max_turns'] ) ? max( 0, (int) $history['max_turns'] ) : 0,
			'messages'   => $normalized_messages,
		);
	}
}
