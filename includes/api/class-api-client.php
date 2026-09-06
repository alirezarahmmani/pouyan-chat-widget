<?php
/**
 * SaaS API client.
 *
 * @package AIChatWidget
 */

namespace AI_Chat_Widget\Api;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Performs authenticated server-to-server SaaS requests. */
final class Api_Client {
	const DEFAULT_BASE_URL = 'https://api.your-saas.com';

	/** Validate a candidate API key. */
	public function validate_api_key( $api_key ) {
		$path = apply_filters( 'ai_chat_widget_validate_path', '/v1/plugin/validate' );
		return $this->request( $path, $api_key, array(), 12 );
	}

	/** Create a short-lived browser session. */
	public function create_session( $api_key, $conversation_id = '' ) {
		$body = array(
			'origin'          => home_url(),
			'conversation_id' => $conversation_id,
			'plugin_version'  => AI_CHAT_WIDGET_VERSION,
		);

		$path = apply_filters( 'ai_chat_widget_session_path', '/v1/plugin/sessions' );
		return $this->request( $path, $api_key, $body, 15 );
	}

	/** Execute JSON POST request. */
	private function request( $path, $api_key, $body, $timeout ) {
		$base_url = defined( 'AI_CHAT_WIDGET_API_BASE_URL' ) ? AI_CHAT_WIDGET_API_BASE_URL : self::DEFAULT_BASE_URL;
		$base_url = apply_filters( 'ai_chat_widget_api_base_url', $base_url );
		$url      = untrailingslashit( esc_url_raw( $base_url ) ) . '/' . ltrim( $path, '/' );

		if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			return new WP_Error( 'ai_chat_insecure_api_url', __( 'The chat service URL must use HTTPS.', 'ai-chat-widget' ) );
		}

		$response = wp_safe_remote_post(
			$url,
			array(
				'timeout'     => $timeout,
				'redirection' => 0,
				'headers'     => array(
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json; charset=utf-8',
					'User-Agent'    => 'AI-Chat-Widget/' . AI_CHAT_WIDGET_VERSION . '; ' . home_url( '/' ),
				),
				'body'        => wp_json_encode( (object) $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'ai_chat_service_unavailable', __( 'The chat service could not be reached.', 'ai-chat-widget' ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 || ! is_array( $data ) ) {
			$message = is_array( $data ) && ! empty( $data['message'] ) ? sanitize_text_field( $data['message'] ) : __( 'The chat service rejected the request.', 'ai-chat-widget' );
			return new WP_Error( 'ai_chat_api_error', $message, array( 'status' => $status ) );
		}

		return $data;
	}
}
