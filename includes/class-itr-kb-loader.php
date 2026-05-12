<?php
/**
 * Registers all actions and filters for the plugin.
 *
 * @package ITR_Knowledgebase
 * @subpackage ITR_Knowledgebase/includes
 */

namespace ITR_Knowledgebase\Includes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ITR_KB_Loader
 *
 * Maintains a list of all hooks (actions & filters) registered throughout
 * the plugin and registers them with the WordPress API.
 */
class ITR_KB_Loader {

	/**
	 * Array of actions registered with WordPress.
	 *
	 * @var array $actions
	 */
	protected $actions = array();

	/**
	 * Array of filters registered with WordPress.
	 *
	 * @var array $filters
	 */
	protected $filters = array();

	/**
	 * Add a new action to the collection.
	 *
	 * @param string $hook          The WordPress action hook name.
	 * @param object $component     The object containing the method.
	 * @param string $callback      The method name to call.
	 * @param int    $priority      Optional. Priority. Default 10.
	 * @param int    $accepted_args Optional. Accepted args. Default 1.
	 * @return void
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection.
	 *
	 * @param string $hook          The WordPress filter hook name.
	 * @param object $component     The object containing the method.
	 * @param string $callback      The method name to call.
	 * @param int    $priority      Optional. Priority. Default 10.
	 * @param int    $accepted_args Optional. Accepted args. Default 1.
	 * @return void
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Utility to add a hook to the collection.
	 *
	 * @param array  $hooks
	 * @param string $hook
	 * @param object $component
	 * @param string $callback
	 * @param int    $priority
	 * @param int    $accepted_args
	 * @return array
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		return $hooks;
	}

	/**
	 * Register all collected actions and filters with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}