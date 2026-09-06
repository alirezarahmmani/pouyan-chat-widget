<?php
/**
 * Hook loader.
 *
 * @package AIChatWidget
 */

namespace AI_Chat_Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers WordPress actions and filters in one place.
 */
final class Loader {
	/** @var array<int,array<string,mixed>> */
	private $actions = array();

	/** @var array<int,array<string,mixed>> */
	private $filters = array();

	/**
	 * Queue an action.
	 *
	 * @param string   $hook          Hook name.
	 * @param object   $component     Callback owner.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Accepted arguments.
	 * @return void
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	/**
	 * Queue a filter.
	 *
	 * @param string   $hook          Hook name.
	 * @param object   $component     Callback owner.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Accepted arguments.
	 * @return void
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	/** Register queued hooks. */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
