<?php
/** REST session controller. @package AIChatWidget */

namespace AI_Chat_Widget\Rest;

use AI_Chat_Widget\Api\Session_Service;
use AI_Chat_Widget\Security\Security;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Public, rate-limited endpoint for temporary chat sessions. */
final class Session_Controller {
	/** @var Session_Service */
	private $service;

	public function __construct( Session_Service $service ) {
		$this->service = $service;
	}

	/** Register REST routes. */
	public function register_routes() {
		register_rest_route(
			'ai-chat-widget/v1',
			'/session',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_session' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'conversation_id' => array(
						'required'          => false,
						'type'              => 'string',
						'maxLength'         => 128,
						'sanitize_callback' => array( Security::class, 'sanitize_conversation_id' ),
					),
				),
			)
		);
	}

	/** Enforce same-origin and request rate limits. */
	public function permissions_check( WP_REST_Request $request ) {
		if ( ! Security::is_same_origin_request() ) {
			return new \WP_Error( 'ai_chat_invalid_origin', __( 'This request origin is not allowed.', 'ai-chat-widget' ), array( 'status' => 403 ) );
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error( 'ai_chat_invalid_nonce', __( 'The chat session request has expired. Refresh the page and try again.', 'ai-chat-widget' ), array( 'status' => 403 ) );
		}

		$limit  = max( 1, (int) apply_filters( 'ai_chat_widget_session_rate_limit', 10 ) );
		$window = max( 10, (int) apply_filters( 'ai_chat_widget_session_rate_window', 60 ) );
		return Security::rate_limit( 'session', $limit, $window );
	}

	/** Return a frontend-safe temporary session. */
	public function create_session( WP_REST_Request $request ) {
		$result = $this->service->create( $request->get_param( 'conversation_id' ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = new WP_REST_Response( $result, 201 );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		return $response;
	}
}
