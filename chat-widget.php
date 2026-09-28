<?php
/**
 * Plugin Name:       AI Chat Widget
 * Plugin URI:        https://example.com/ai-chat-widget
 * Description:       Secure, configurable AI chat widget backed by short-lived WebSocket sessions.
 * Version:           1.0.11
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Your Company
 * Author URI:        https://example.com
 * Text Domain:       ai-chat-widget
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AIChatWidget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AI_CHAT_WIDGET_VERSION', '1.0.11' );
define( 'AI_CHAT_WIDGET_FILE', __FILE__ );
define( 'AI_CHAT_WIDGET_PATH', plugin_dir_path( __FILE__ ) );
define( 'AI_CHAT_WIDGET_URL', plugin_dir_url( __FILE__ ) );

require_once AI_CHAT_WIDGET_PATH . 'includes/class-loader.php';
require_once AI_CHAT_WIDGET_PATH . 'includes/class-plugin.php';

AI_Chat_Widget\Plugin::instance()->run();
