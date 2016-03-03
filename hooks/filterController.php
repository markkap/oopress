<?php
/**
 * OOPress filterController class
 *
 * @package OOPress
 * @subpackage HookHandling
 */

namespace oopress\hooks;

/**
 * A controller for filters
 *
 * Takes into account the nature of filters to  pass the returned
 * value by one handler as a first argument of the next
 *
 * @since 0.0.1
 */
class filterController extends hookController {

	/**
     * @param string $filterName The name of the filter hook
     * @since 0.0.1
     */
	public function __construct($filterName) {
		parent::__construct($filterName);
	}

	/**
	 * Invoke a specific filter handler
	 *
	 * Invoked by the generic handler invocation algorithm supplied by the hookController class when
	 * an handler needs to be triggered.
	 * Checks if the handler actually behaves like an filter and returns a value
	 *
	 * @param  hookHandler $handler The handler to trigger
	 * @param  array       $args    The args to pass to the handler
	 * @return array                The args as recieved with the first element replaced
	 *                              by the value returned by the handler
	 *
	 * @since 0.0.1
	 */
	protected function invokeHandler(hookHandler $hookHandler, array $args) {

		$ret = call_user_func_array(array($handler,$this->hookName),$args);
		if (!isset($ret)) {
			trigger_error('Filters have to return a value. Error while handling '.$this->hookName,E_USER_ERROR);
		}
        $args[0] = $ret;
		return $args;
	}

	/**
	 * The return value the controller should return to wordpress core handling functions
	 *
	 * Invoked by the generic handler invocation algorithm supplied by the hookController class
	 * to get the appropriate return value that should be returned to wordpress hook handling routines
	 * which in the case of filters is the last value returned by an handler
	 *
	 * @since 0.0.1
	 */
    protected function priorityHandlerReturnValue() {
        return $this->currentArgs[0];
    }
}
