<?php
/**
 * OOPress hookHandlerDependencySweetener class
 *
 * @package OOPress
 * @subpackage HookHandling
 */

namespace oopress\hooks;

/**
 * An implementation of hookHandler with fine grained dependency builduing APIs
 *
 * It is intended to be used to hide the relevant handlerController dependency APIs
 * and make the code more readable by expressing dependencies relative to the hookHandler
 * object.
 *
 * The API can be called at any time building the dependency list internally. Once a hookController
 * requests the dependency the relevant APIs are called with the info in the dependency list.
 *
 * @since 0.0.1
 */
class hookHandlerDependencySweetener implements hookHandler {

	/**
	 * List of handlers/callbacks which the handler have to execute after
	 *
	 * @var array
	 * @since 0.0.1
	 */
	private $dependsOn=array();

	/**
	* List of handlers/callbacks which the handler have to execute before
	*
	* @var array
	* @since 0.0.1
	 */
	private $runsBefore=array()

	/**
	 * Implementation of the relevant hookHandler interface
	 *
	 * Loops over the dependsOn and runsBefore property and call the relevant hookController
	 * API based on their type
	 *
	 * @param  hookController $hookController The controller with which to register the dependencies
	 *
	 * @since 0.0.1
	 */
	public function registerDependencies(hookController $hookController) {
		foreach (this->dependsOn as $d) {
			if (is_int($d))
				$hookController->addHandlerOnPriorityDependency($this,$d);
			else if (is_callable($d))
				$hookController->addHandlerOnFunctionDependency($this,$d);
			else if (is_string($d))
				$hookController->addHandlerOnClassDependency($this,$d);
			else if ($d instanceof hookHandler)
				$hookController->addHandlerOnHandlerDependency($this,$d);
		}
		foreach (this->runsBefore as $d) {
			if (is_int($d))
				$hookController->addPriorityOnHandlerDependency($d,$this);
			else if (is_callable($d))
				$hookController->addFunctionOnHandlerDependency($d,$this);
			else if (is_string($d))
				$hookController->addClassOnHandlerDependency($d,$this);
			else if ($d instanceof hookHandler)
				$hookController->addHandlerOnHandlerDependency($d,$this);
		}
	}

	/**
	 * Register a dependency on an handler which have to be done before current handler
	 *
	 * @param  actionHandler $handler The handler to depend on
	 * @since 0.0.1
	 */
	public function dependsOnHandler(hookHandler $handler) {
		$this->dependsOn[] = $handler;
	}

	/**
	 * Register dependency on a class of handlers which have to be done before current handler
	 *
	 * Useful when you don't know the specific instace of an object the handler
	 * has to depend on, but you know its class. Applies as well to non hookHandler classes
	 * objects registered by other ways into the wordpress hooking system.
	 *
	 * @param  string $className The class of handlers to depend on
	 * @since 0.0.1
	 */
	public function dependsOnClass($className) {
		$this->dependsOn[] = $className;
	}

	/**
	 * Register dependency on a function which has to be done before current handler
	 *
	 * Useful to create dependencies with hook handlers registered directly with
	 * the wordpress core API. The "function" can be any php callable object, either a function name
	 * or an array of []$object,method]
	 *
	 * @param  callable $function The name of a function or array specifiyin an object method
	 *                            which the handler depends on
	 * @since 0.0.1
	 */
	public function dependsOnFunction(callable $function) {
		$this->dependsOn[] = $function;
	}

	/**
	 * Register dependency on a completion of a priority
	 *
	 * Causes the handler to be executed only after all the handlers of a specific priority
	 * or higher were executed. The exact priority at which it will be done may vary.
	 * Used in combination with runsBeforePriority you can set a range of deried priorities.
	 *
	 * @param  int $priority The priority after which the handler may execute
	 * @since 0.0.1
	 */
	public function dependsOnPriority($priority) {
		$this->dependsOn[] = $priority;
	}

	/**
	 * Register a dependency for an handler which have to be done only after the current handler
	 *
	 * @param  actionHandler $handler The handler depending on this
	 * @since 0.0.1
	 */
	public function runsBeforeHandler(hookHandler $handler) {
		$this->runsBefore[] = $handler;
	}

	/**
	 * Register dependency for a class of handlers which have to be done after the current handler
	 *
	 * Useful when you don't know the specific instace of an object that has to run after
	 * this handler, but you know its class. Applies as well to non hookHandler classes
	 * objects registered by other ways into the wordpress hooking system.
	 *
	 * @param  string $className The class of handlers depending on this
	 * @since 0.0.1
	 */
	public function runsBeforeClass($className) {
		$this->runsBefore[] = $className;
	}

	/**
	 * Register dependency for a function which has to be done after the current handler
	 *
	 * Useful to create dependencies with hook handlers registered directly with
	 * the wordpress core API. The "function" can be any php callable object, either a function name
	 * or an array of [object,method]
	 *
	 * @param  callable $function The name of a function or array specifiying an object method
	 *                            depending on this handler
	 * @since 0.0.1
	 */
	public function runsBeforeFunction(callable $function) {
		$this->runsBefore[] = $function;
	}

	/**
	 * Register dependency for an execution of handler of specific priority on this handler
	 *
	 * Causes the handler registered for a priority and lower priorities to be executed only after this handler
	 * The exact priority at which it will be done may vary.
	 * Used in combination with dependsOnPriority you can set a range of deried priorities.
	 *
	 * @param  int $priority The priority before which the handler may execute
	 *
	 * @since 0.0.1
	 */
	public function runsBeforePriority($priority) {
		$this->runsBefore[] = $priority;
	}
}
