<?php
/**
 * OOPress callbackHookHandler class
 *
 * @package OOPress
 * @subpackage HookHandling
 */

namespace oopress\hooks;

/**
 * OOP wrapper for a wordpress core style to hook callback
 *
 * To create an object, a priority and callback function need to be provide
 * Once created the object registers to be invoke for the appropriate priority
 * and once invoked, it executes the callback.
 *
 * As the whole point of it is to allow quick and dirty way to bypass all type checks
 * the callback handling is somewhat hacky.
 *
 * @since 0.0.1
 */
class callbackHookHandler implements hookHandler {

	/**
	 * The callback to execute once the object is triggered
	 *
	 * standard php callable object (clouser, function name or array of (object,method))
	 *
	 * @var callable
	 * @since 0.0.1
	 */
	private $callback;

	/**
	 * The hook name
	 * @var string
	 * @since 0.0.1
	 */
	private $hookNAme;

	/**
	 * Store the callback info and Register the handler with the hook controller to be invoked
	 * for the specific priority
	 *
	 * @param hookController $hookController The hook controller which should invoke the handler
	 * @param int      $priority The priority to assign to the handler
	 * @param callable $callback The callback to invoke when the handler is triggered
	 *
	 * @since 0.0.1
	 */
	public function __construct(hookController $hookController, $priority, callable $callback) {
		$this->callback = $callback;
		$this->hookName = $hookController->getHookName();
		$hookController->addHandlerOnPriorityDependency($this,$priority - 1);
		$hookController->addPriorityOnHandlerDependency($priority + 1,$this);
	}

	/**
	 * Called by the hook controller when the handler is triggered
	 *
	 * This is somewhat of a hack to avoid the handler failing the type checks
	 * done by the controller. In practise any call to any method will triiger the callback
	 *
	 * @param  string $name      the name of the method being called, ignored.
	 * @param  array  $arguments The arguments to pass to the callback
	 * @return mixed             returns whatever the callbal returns
	 *
	 * @since 0.0.1
	 */
	public function __call($name , array $arguments) {
		if ($name == $this->hookName)
		return call_user_func_array($this->callback,$arguments);
	}

	public function registerDependencies(hookController $hookController) {}
}
