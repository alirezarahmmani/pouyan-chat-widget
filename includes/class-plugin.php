<?php
/**
 * Plugin orchestrator.
 *
 * @package AIChatWidget
 */

namespace AI_Chat_Widget;

use AI_Chat_Widget\Admin\Admin;
use AI_Chat_Widget\Admin\Settings;
use AI_Chat_Widget\Api\Api_Client;
use AI_Chat_Widget\Api\Api_Validator;
use AI_Chat_Widget\Api\Session_Service;
use AI_Chat_Widget\Rest\Session_Controller;
use AI_Chat_Widget\Widget\Widget;
use AI_Chat_Widget\Widget\Widget_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Plugin bootstrap and dependency wiring. */
final class Plugin {
	/** @var self|null */
	private static $instance;

	/** @var Loader */
	private $loader;

	/** Get singleton. */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/** Wire dependencies. */
	private function __construct() {
		$this->load_dependencies();
		$this->loader = new Loader();

		$api_client      = new Api_Client();
		$widget_settings = new Widget_Settings();
		$session_service = new Session_Service( $api_client );
		$rest_controller = new Session_Controller( $session_service );
		$widget          = new Widget( $widget_settings );

		$this->loader->add_action( 'rest_api_init', $rest_controller, 'register_routes' );
		$this->loader->add_action( 'wp_enqueue_scripts', $widget, 'enqueue_assets' );
		$this->loader->add_action( 'wp_footer', $widget, 'render' );
		$this->loader->add_action( 'init', $this, 'load_textdomain' );

		if ( is_admin() ) {
			$validator = new Api_Validator( $api_client );
			$settings  = new Settings( $validator, $widget_settings );
			$admin     = new Admin( $settings );

			$this->loader->add_action( 'admin_menu', $admin, 'add_menu' );
			$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
			$this->loader->add_action( 'admin_init', $settings, 'register' );
		}
	}

	/** Include implementation classes. */
	private function load_dependencies() {
		$files = array(
			'includes/security/class-security.php',
			'includes/api/class-api-client.php',
			'includes/api/class-api-validator.php',
			'includes/api/class-session-service.php',
			'includes/rest/class-session-controller.php',
			'includes/widget/class-widget-settings.php',
			'includes/widget/class-widget.php',
			'includes/admin/class-settings.php',
			'includes/admin/class-admin.php',
		);

		foreach ( $files as $file ) {
			require_once AI_CHAT_WIDGET_PATH . $file;
		}
	}

	/** Start registered hooks. */
	public function run() {
		$this->loader->run();
	}

	/** Load translations at the standard WordPress lifecycle point. */
	public function load_textdomain() {
		load_plugin_textdomain( 'ai-chat-widget', false, dirname( plugin_basename( AI_CHAT_WIDGET_FILE ) ) . '/languages' );
	}
}
