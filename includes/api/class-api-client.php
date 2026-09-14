<?php

/**
 * SaaS API client.
 *
 * @package AIChatWidget
 */

namespace AI_Chat_Widget\Api;

use WP_Error;

if (! defined('ABSPATH')) {
	exit;
}

/** Performs authenticated server-to-server SaaS requests. */
final class Api_Client
{
	const DEFAULT_BASE_URL = 'http://192.168.100.108:8000/';

	/** Validate API key and request widget auth token. */
	public function validate_api_key($api_key)
	{
		$path = apply_filters(
			'ai_chat_widget_validate_path',
			'/api/v1/provider/widget/auth-token/'
		);

		return $this->request($path, $api_key, array(), 12);
	}

	/** Request a fresh, single-use WebSocket token. */
	public function create_session($api_key, $conversation_id = '')
	{
		$path = apply_filters(
			'ai_chat_widget_session_path',
			'/api/v1/provider/widget/auth-token/'
		);

		return $this->request($path, $api_key, array(), 15);
	}

	/** Retrieve the messages belonging to an existing widget session. */
	public function get_session_history($api_key, $session_id)
	{
		$path = apply_filters(
			'ai_chat_widget_session_history_path',
			'/api/v1/provider/widget/session-history/'
		);

		return $this->request(
			$path,
			$api_key,
			array('session_id' => $session_id),
			15
		);
	}

	/** Build the public WebSocket endpoint from the configured API host. */
	public function get_websocket_url()
	{
		$base_url = defined('AI_CHAT_WIDGET_API_BASE_URL')
			? AI_CHAT_WIDGET_API_BASE_URL
			: self::DEFAULT_BASE_URL;

		$base_url = apply_filters('ai_chat_widget_api_base_url', $base_url);
		$parts    = wp_parse_url($base_url);

		if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
			return '';
		}

		$scheme = 'https' === strtolower($parts['scheme']) ? 'wss' : 'ws';
		$port   = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
		$url    = $scheme . '://' . $parts['host'] . $port . '/ws/v1/chat/';

		return apply_filters('ai_chat_widget_websocket_url', $url);
	}

	/** Execute JSON POST request. */
	private function request($path, $api_key, $body, $timeout)
	{
		$base_url = defined('AI_CHAT_WIDGET_API_BASE_URL')
			? AI_CHAT_WIDGET_API_BASE_URL
			: self::DEFAULT_BASE_URL;

		$base_url = apply_filters(
			'ai_chat_widget_api_base_url',
			$base_url
		);

		$url = untrailingslashit(
			esc_url_raw($base_url)
		) . '/' . ltrim($path, '/');

		$scheme = wp_parse_url($url, PHP_URL_SCHEME);

		if (! in_array($scheme, array('http', 'https'), true)) {
			return new WP_Error(
				'ai_chat_invalid_api_url',
				__('The chat service URL is invalid.', 'ai-chat-widget')
			);
		}

		/*
		 * Origin goes in header.
		 * Example: https://vulnerbyte.com
		 */
		$home_parts = wp_parse_url(home_url());
		$origin     = '';

		if (is_array($home_parts) && ! empty($home_parts['scheme']) && ! empty($home_parts['host'])) {
			$home_port = isset($home_parts['port']) ? ':' . (int) $home_parts['port'] : '';
			$origin    = $home_parts['scheme'] . '://' . $home_parts['host'] . $home_port;
		}

		$origin = apply_filters('ai_chat_widget_origin', $origin);

		/*
		 * API key goes in JSON body.
		 */
		$request_body = array_merge(
			array(
				'api_key' => trim($api_key),
			),
			$body
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout'     => $timeout,
				'redirection' => 0,
				'headers'     => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json; charset=utf-8',
					'Origin'       => $origin,
					'User-Agent'   => 'AI-Chat-Widget/' . AI_CHAT_WIDGET_VERSION,
				),
				'body' => wp_json_encode($request_body),
			)
		);

		if (is_wp_error($response)) {
			return new WP_Error(
				'ai_chat_service_unavailable',
				$response->get_error_message(),
				array(
					'original_error' => $response->get_error_code(),
				)
			);
		}

		$status = wp_remote_retrieve_response_code($response);
		$data   = json_decode(
			wp_remote_retrieve_body($response),
			true
		);

		if ($status < 200 || $status >= 300 || ! is_array($data)) {
			$message = __(
				'The chat service rejected the request.',
				'ai-chat-widget'
			);

			if (! empty($data['message'])) {
				$message = sanitize_text_field($data['message']);
			} elseif (! empty($data['errors']['detail'])) {
				$message = sanitize_text_field($data['errors']['detail']);
			} elseif (! empty($data['detail'])) {
				$message = sanitize_text_field($data['detail']);
			}

			return new WP_Error(
				'ai_chat_api_error',
				$message,
				array(
					'status' => $status,
				)
			);
		}

		return $data;
	}
}
