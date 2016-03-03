<?php
/**
 * OOPress actionController class
 *
 * @package OOPress
 * @subpackage HookHandling
 */

namespace oopress\hooks;

/**
 * A controller for actions
 *
 * Takes into account the nature of action to not return values
 *
 * @since 0.0.1
 */
class actionController extends hookController {

   /**
    * @param string $actionName The name of the action hook
    * @since 0.0.1
    */
	public function __construct($actionName) {
		parent::__construct($actionName);
	}

	/**
	 * Invoke a specific action handler
	 *
	 * Invoked by the generic handler invocation algorithm supplied by the hookController class when
	 * an handler needs to be triggered.
	 * Checks if the handler actually behaves like an action and do not return any value
	 * like filters do
	 *
	 * @param  hookHandler $handler The handler to trigger
	 * @param  array       $args    The args to pass to the handler
	 * @return array                The args as recieved since they are not mutated by actions
	 *
	 * @since 0.0.1
	 */
	protected function invokeHandler(hookHandler $handler, array $args) {

		$ret = call_user_func_array(array($handler,$this->hookName),$args);
		if (isset($ret)) {
			trigger_error('Action should not return a value. error while handling '.$this->hookName,E_USER_NOTICE);
		}
		return $args;
	}

	/**
	 * The return value the controller should return to wordpress core handling functions
	 *
	 * Invoked by the generic handler invocation algorithm supplied by the hookController class
	 * to get the appropriate return value that should be returned to wordpress hook handling routines
	 * which in the case of actions it is void.
	 *
	 * @since 0.0.1
	 */
    protected function priorityHandlerReturnValue() {}
}
