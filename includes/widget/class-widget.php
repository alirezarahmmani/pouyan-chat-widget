<?php

/** Frontend widget integration. @package AIChatWidget */

namespace AI_Chat_Widget\Widget;

use AI_Chat_Widget\Security\Security;

if (! defined('ABSPATH')) {
	exit;
}

/** Enqueues and renders the local widget UI when credentials are valid. */
final class Widget
{
	/** @var Widget_Settings */
	private $settings;

	public function __construct(Widget_Settings $settings)
	{
		$this->settings = $settings;
	}

	/** Enqueue local frontend assets and non-secret configuration. */
	public function enqueue_assets()
	{
		if (! $this->should_render()) {
			return;
		}

		wp_enqueue_style('ai-chat-widget', AI_CHAT_WIDGET_URL . 'assets/css/chat-widget.css', array(), AI_CHAT_WIDGET_VERSION);
		wp_enqueue_script('ai-chat-widget', AI_CHAT_WIDGET_URL . 'assets/js/chat-widget.js', array(), AI_CHAT_WIDGET_VERSION, true);
		wp_localize_script(
			'ai-chat-widget',
			'AIChatWidgetConfig',
			array(
				'restUrl'    => esc_url_raw(rest_url('ai-chat-widget/v1/session')),
				'historyUrl' => esc_url_raw(rest_url('ai-chat-widget/v1/session/history')),
				'nonce'      => wp_create_nonce('wp_rest'),
				'connected'  => $this->is_connected(),
				'storage'    => 'ai_chat_widget_conversation_id',
				'strings' => array(
					'connecting'   => __('Connecting…', 'ai-chat-widget'),
					'connected'    => __('Online', 'ai-chat-widget'),
					'disconnected' => __('Connection lost. Reconnecting…', 'ai-chat-widget'),
					'generating'   => __('Generating response...', 'ai-chat-widget'),
					'error'        => __('Chat is temporarily unavailable. Please try again.', 'ai-chat-widget'),
					'empty'        => __('Write a message first.', 'ai-chat-widget'),
					'offline'      => __('Chat is not available right now.', 'ai-chat-widget'),
				),
			)
		);
	}

	/** Render the small semantic template. */
	public function render()
	{
		if (! $this->should_render()) {
			return;
		}

		$settings = $this->settings->get();
		include AI_CHAT_WIDGET_PATH . 'templates/widget/chat-widget.php';
	}

	/** Each admin toggle independently covers the connected and disconnected states. */
	private function should_render()
	{
		$settings = $this->settings->get();
		return $this->is_connected() ? ! empty($settings['enabled']) : ! empty($settings['show_without_api']);
	}

	/** Check server-only credential status. */
	private function is_connected()
	{
		$status  = get_option('ai_chat_widget_api_key_status', array());
		$has_key = '' !== Security::decrypt(get_option('ai_chat_widget_api_key', ''));
		return ! empty($status['valid']) && $has_key;
	}
}
