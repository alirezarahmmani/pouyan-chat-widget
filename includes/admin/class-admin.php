<?php

/** Admin page integration. @package AIChatWidget */

namespace AI_Chat_Widget\Admin;

if (! defined('ABSPATH')) {
	exit;
}

/** Owns the WordPress admin page and its assets. */
final class Admin
{
	/** @var Settings */
	private $settings;
	/** @var string */
	private $page_hook = '';

	public function __construct(Settings $settings)
	{
		$this->settings = $settings;
	}

	/** Add settings page. */
	public function add_menu()
	{
		$this->page_hook = add_menu_page(
			__('ویجت چت هوشمند', 'ai-chat-widget'), // Page title
			__('ویجت چت هوشمند', 'ai-chat-widget'), // Menu title
			'manage_options',                         // Capability
			'ai-chat-widget',                         // Menu slug
			array($this, 'render_page'),            // Callback
			'dashicons-format-chat',                  // Icon
			30                                        // Position
		);
	}

	/** Load admin-only assets on this plugin page. */
	public function enqueue_assets($hook)
	{
		if ($hook !== $this->page_hook) {
			return;
		}
		// The live preview renders the real widget markup, so it needs the frontend styles too.
		wp_enqueue_style('ai-chat-widget', AI_CHAT_WIDGET_URL . 'assets/css/chat-widget.css', array(), AI_CHAT_WIDGET_VERSION);
		wp_enqueue_style('ai-chat-widget-admin', AI_CHAT_WIDGET_URL . 'assets/css/admin.css', array('ai-chat-widget'), AI_CHAT_WIDGET_VERSION);
		wp_enqueue_script('ai-chat-widget-admin', AI_CHAT_WIDGET_URL . 'assets/js/admin.js', array(), AI_CHAT_WIDGET_VERSION, true);
	}

	/** Render settings template. */
	public function render_page()
	{
		if (! current_user_can('manage_options')) {
			return;
		}
		$data = $this->settings->view_data();
		include AI_CHAT_WIDGET_PATH . 'templates/admin/settings.php';
	}
}
