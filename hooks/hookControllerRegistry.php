<?php
/**
 * OOPress hookControllerRegistry class
 *
 * @package OOPress
 * @subpackage HookHandling
 */

namespace oopress\hooks;

/**
 * A registry and factory of hook controllers
 *
 * Provides a central repository of hook controllers, and as central point for
 * starting the hook handling at the controllers.
 *
 * This is not a singleton, and you can have as many registries as you like working together
 *
 * @since 0.0.1
 */
class hookControllerRegistry {

	/**
	 * The class name of the generic action controller
	 *
	 * @since 0.0.1
	 */
	const ACTION = 'oopress\hooks\actionController';

	/**
	 * The class name of the generic filter controller
	 *
	 * @since 0.0.1
	 */
	const FILTER = 'oopress\hooks\filterController';

	/**
	 * Map of hook names to the associated controllers
	 *
	 * @var hookController[]
	 *
	 * @since 0.0.1
	 */
	private $controllers = array();

	/**
	 * Registers an handler for the "all" hook at the wordpress core API
	 * to be able to handle all hook executions
	 */
	public function __construct() {
		add_action('all',array($this,'handleHook'));
	}

	/**
	 * Handles the "all" hook from wordpress core
	 *
	 * Called by the wordpress core before processing any hook.
	 * Once called, calls the relevant hhok handler to start processing the hook
	 *
	 * @since 0.0.1
	 */
	public function handleHook() {
		$action = current_filter();
		if (isset($this->controllers[$action]))
			$this->controllers[$action]->hookHandler();
	}

	/**
	 * Register a controller in the registry
	 *
	 * @param  hookController $controller The controller to register
	 *
	 * @since 0.0.1
	 */
	public function registerController(hookController $controller) {
		$name = $controller->getHookName();
		if (isset($this->controllers[$name]))
			trigger_error('controller already exist for: '.$name,E_USER_ERROR);

		$this->controllers[$name] = $controller;
	}

	/**
	 * Retrieves, and creates if needed, the hook controller from the registry
	 *
	 * @param  string $hookName The name of the hook to register the controller to
	 * @return hookController|null   The registered controller for the hookName
	 *                               or null if there is none
	 */
	public function getController($hookName) {

		if (!isset($this->controller[$hookName]))
			return null;
		else
			return $this->controllers[$hookName];
	}
}
