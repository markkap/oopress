<?php
/**
 * OOPress hookController class and relevant internal utility classes
 *
 * @package OOPress
 * @subpackage HookHandling
 */

namespace oopress\hooks;

/**
 * A place holder class of hookHandler objects representing pririties other
 * handlers can depend on.
 *
 * @internal
 * @since 0.0.1
 */
class dummyPriorityHookHandler implements hookHandler {

	public function __construct() {
	}

	public function registerDependencies(hookController $hookController) {
	}

	function __call( $name , array $arguments ) {
	}
}

/**
 * An helper class to add, handle and remove priority related hooks from the
 * wordpress core
 *
 * This kind of objects is needed to be able to trigger handling of a specific
 * priority at a hookController. On creation it registers the handle method
 * to be triggered at a specific priority of the hook.
 *
 * Requires an external signal to remove the hook because of problems with core
 * hook handling implementation described at @link https://core.trac.wordpress.org/ticket/17817
 *
 * @internal
 * @since 0.0.1
 */
class priorityHelper {

	/**
	 * The controller to be triggenred when the handle method is invoked
	 * @var hookController
	 *
	 * @since 0.0.1
	 */
	private $controller;

	/**
	 * The priority number to pass to the controller when invoked
	 * @var int
	 *
	 * @since 0.0.1
	 */
	private $priority;

	/**
	 * Create an object and register handler for the relevant hook in wordpress core
	 *
	 * @param hookController $controller The controller to be notified when  the handler
	 *                                   is triggered
	 * @param int $priority The priority which wcich the handler should be registers
	 *                      at the wordpress core handlers and which should be reported
	 *                      to the controller when triggered
	 *
	 * @since 0.0.1
	 */
	public function __construct(hookController $controller,$priority) {
		$this->controller = $controller;
		$this->priority = $priority;
		add_action($this->controller->getHookName(),array($this,'handle'),$this->priority,100);
	}

	/**
	 * The hook handler called by the wordpress core hook handling code
	 *
	 * Called by wordpress core handling when it is the turn of the specific priority
	 * with which it was registered to be processed.
	 * Calls the controller to do the actual processing.
	 *
	 * @param  mixed $args Parameters pssed by the wordpress core hook handling
	 * @return mixed 	   Whatever the controller's handler returns
	 *
	 * @since 0.0.1
	 */
	public function handle($args=null) {

        return $this->controller->handlePriority($this->priority,func_get_args());
	}

	/**
	 * Remove the hanlde method from the wordpress core hook handling
	 *
	 * @since 0.0.1
	 */
    public function remove() {
		remove_action($this->controller->getHookName(),array($this,'handle'),$this->priority);
    }
}

/**
 * Objects of this class serve as registry and controller for handlers of a specific hook
 *
 * @since 0.0.1
 */
abstract class hookController {

	/**
	 * Indicates the highest priority that will be used by the controller
	 * for handler that should be envoked EARLIEST
	 *
	 * @since 0.0.1
	 */
	const EARLIEST = 1;

	/**
	 * Indicates the normal priority that will be used by the controller
	 * for handlers with no explicit on implicit prority
	 *
	 * @since 0.0.1
	 */
	const NORMAL = 10;

	/**
	 * Indicates the lowest priority that will be used by the controller
	 * for handler that should be envoked LATEST
	 *
	 * @since 0.0.1
	 */
	const LATEST = 999999;

	/**
	 * Array of arrays of hook handlers grouped by their class names
	 * @var array
	 *
	 * @since 0.0.1
	 */
	protected $classes = array();

	/**
	 * Registered hook handlers
	 * @var hookHandler[] Array of hookHandler object indexed by their hash
	 *
	 * @since 0.0.1
	 */
	protected $objects = array();

	/**
	 * helper array to indicate which handlers were done
	 * @var bool[] Index is the object hash and value is true after the handler was processed
	 *
	 * @since 0.0.1
	 */
	private $handled = array();

	/**
	 * The name of the hook the controller controls
	 * @var string
	 *
	 * @since 0.0.1
	 */
	private $hookName;

	/**
	 * Holds the computed dependency map
	 * @var array Each index is a hash of an handler object and the element is an array
	 *      	  of handlers it depends upon
	 *
	 * @since 0.0.1
	 */
	protected $dependencyMap = array();

	/**
	 * Two arrays holding lists of priorities and the handlers which are
	 * associated with them. The "min" maps the earliest priority of handlers
	 * while the "max" maps the latest.
	 *
	 * @var array Two arrays of arrays with keys as priority and value is array
	 *      	  of hook handlers
	 *
	 * @since 0.0.1
	 */
	protected $priorityBased = array('min' => array(),'max' => array());

	/**
	 * Array of priorities helpers generated during the processesing. Used to
	 * track them in order to be able to dispose of them when the processing was
	 * finished.
	 *
	 * @var prioritiesHelpers[]
	 *
	 * @since 0.0.1
	 */
	private $prioritiesHelpers = array();

	/**
	 * Array of the current hook parameters being processed
	 * @var array
	 *
	 * @since 0.0.1
	 */
    protected $currentArgs;

	/**
	 * Creat a controller for a specific hook
	 * @param string $hookName The hook name
	 *
	 * @since 0.0.1
	 */
	public function __construct($hookName) {
		$this->hookName = $hookName;
	}

	/**
	 * Invokes the processing of a specific handler
	 *
	 * Should be overridden by concrete implementations to take care especially
	 * on argument passing and return values
	 *
	 * @param  hookHandler $hookHandler The handler to trigger
	 * @param  array       $args        The arguments to supply to the function
	 *                                  doing the handling
	 * @return array                    The arguments to supply to the next
	 *                                  handler that will be triggered
	 *
	 * @since 0.0.1
	 */
	abstract protected function invokeHandler(hookHandler $hookHandler, array $args);

	/**
	 * The value returned to the core priority handler
	 *
	 * The value should be the appropriate result of applying all handlers that
	 * were done since handlePriority was called
	 *
	 * @return mixed
	 *
	 * @since 0.0.1
	 */
    abstract protected function priorityHandlerReturnValue();

	/**
	 * Access to the hook name handled by the controller
	 * @return string the controller's hook name
	 *
	 * @since 0.0.1
	 */
	final public function getHookName() {
        return $this->hookName;
    }

	/**
	 * Resolve handlers dependencies and invoke it
	 *
	 * Recursively scan the dependencies of the handler and invoke all the handlers
	 * it depends on. If all dependencies were done, the handler itself is invoked
	 *
	 * @param  hookHandler $hookHandler The handler to invoke
	 * @return bool                  	True if handler was invoked, false otherwise
	 */
    final protected function resolveDependenciesAndInvokeHandler(hookHandler $hookHandler) {

        $hash = spl_object_hash($hookHandler);

		if ($this->wasHandled($hookHandler)) { // already done
			return true;
		}

		$dependencIssDone = true;
		if (isset($this->dependencyMap[$hash])) {
			foreach ($this->dependencyMap[$hash] as $d) {
				$dependencIssDone &= $this->resolveDependenciesAndInvokeHandler($d);
			}
		}

		if ($dependencIssDone) {
			$this->currentArgs = $this->invokeHandler($hookHandler,$this->currentArgs);
			$this->markAsHandled($hookHandler);
		}

		return $dependenciesDone;

	}

	/**
	 * Remove the priority handlers
	 *
	 * Called from the wordpress core hook handlers at the lowest priority,
	 * i.e. after all other prioriies were handled
	 *
	 * @since 0.0.1
	 */
    final private function priorityHandlerCleanup() {
        foreach ($this->priorityHelpers as $p)
            $p->remove();

        remove_action($this->hookName,array($this,'priorityHandlerCleanup'),self::LATEST+1);
        $this->priorityHelpers = array();
    }

	/**
	 * Start handling the handlers of the hook
	 *
	 * Called by the registry as a result of the triggering of an "all" hook
	 * for the specific hook.
	 *
	 * Processing steps: build the dependency and priority maps, and the relevant
	 * handlers for each calculated priority and let the core handling of the hook
	 * to trigger them. To claan everything up, add a cleaning handler at the
	 * lowest priority
	 *
	 * @since 0.0.1
	 */
	public function hookHandler() {

		$this->buildDependencyMap();
		$this->handled = array();
        $this->priorityHelpers = array();

		foreach ($this->priorityBased['min'] as $priority=>$v) {
			$this->priorityHelpers[] = new priorityHelper($this,$priority);
		}
		foreach ($this->priorityBased['max'] as $priority=>$v) {
            if (!isset($this->priorityBased['min'][$priority]))
                $this->priorityHelpers[] = new priorityHelper($this,$priority);
		}

		// trigger priority helper cleanup after everything else was done
        add_action($this->hookName,array($this,'priorityHandlerCleanup'),self::LATEST+1);
   	}

	/**
	 * Register an execution dependency of one handler on another
	 *
	 * Defines a dependency relationship between handler so that one handler
	 * will not be triggered beor another
	 *
	 * @param hookHandler $handler          The depending handler
	 * @param hookHandler $dependsOnHandler The handler it depends on
	 *
	 * @since 0.0.1
	 */
	final public function addHandlerOnHandlerDependency(hookHandler $handler, hookHandler $dependsOnHandler) {
		if (!isset($this->objects[spl_object_hash($handler)]))
			trigger_error('Source dependency object is not registered in the controller',E_USER_ERROR);

		if (!isset($this->objects[spl_object_hash($dependsOnHandler)]))
			trigger_error('Depended upon object is not registered in the controller',E_USER_ERROR);

		$this->dependencyMap[spl_object_hash($handler)][] = $depends;
	}

	/**
	 * A swittener to access the core hook data for the specific hook handled by the controller
	 *
	 * @return array Items where the index is the priority and the value is an array in which
	 *               the item with 'function' key is the callback
	 *
	 * @internal
	 * @since 0.0.1
	 */
	private function getCoreHokkCallbacks() {

		global $wp_filter;
		if (isset($wp_filter[$this->hookName]))
			return $wp_filter[$this->hookName];
		else {
				return array();
		}
	}

	/**
	 * Register dependency for an execution of an handler on execution of all handlers of a specific class
	 *
	 * Useful to create dependencies with hook handlers registered to the controller or directly with
	 * the wordpress core API.
	 *
	 * For handlers of the class registered with the controller it just creats a dependency relationship,
	 * but for classes of handler set in core it finds the priority of the core handler
	 * and assigns the handler an lower priority (higher priority number)
	 *
	 * @param hookHandler $handler   The handler
	 * @param string      $className The name of the class
	 *
	 * @since 0.0.1
	 */
	final public function addHandlerOnClassDependency(hookHandler $handler, $className) {
		foreach ($this->classes[$className] as $o) {
			$this->addHandlerOnHandlerDependency($handler,$o);
		}

		// take into account the handlers registered via do_action/do_filter
		foreach ($this->getCoreHokkCallbacks() as $priority => $hook) {
			$callback = $hook['function'];
			if (is_array($callback)) {
				if (get_class($callback[0]) == $className) {
					$this->addHandlerOnPriorityDependency($handler,$priority);
				}
			}
		}
	}

	/**
	 * Register dependency for an execution of an handler on a specific function
	 *
	 * Useful to create dependencies with hook handlers registered directly with
	 * the wordpress core API. The "function" can be any php callable object, either a function name
	 * or an array of [$object,method]
	 *
	 * The way this works is by looking at the handler set in core, find the priority
	 * the function has and assign the handler an lower priority (higher priority number)
	 *
	 * @param hookHandler $handler	The handler that should be handled before the function
	 * @param callable $function 	The priority before which the handler may execute
	 *
	 * @since 0.0.1
	 */
	final public function addHandlerOnFunctionDependency(hookHandler $handler, callable $function) {
		// take into account the handlers registered via add_action/add_filter
		foreach ($this->getCoreHokkCallbacks() as $priority => $hook) {
			$callback = $hook['function'];
			if ($callback == $function) {
				$this->addHandlerOnPriorityDependency($handler,$priority);
			}
		}
	}

	/**
	 * Register dependency of an handler on the execution of all handlers in a priority
	 *
	 * Causes the handler to be executed after all handlers with a specific priority
	 * or higher.
	 *
	 * In practical terms, this sets the highest priority in which the handler can be
	 * processed to one lower then $priority.
	 *
	 * @param hookHandler $handler	The handler that should be handled after the priority
	 * @param int $priority 		The priority after which the handler may execute
	 *
	 * @since 0.0.1
	 */
	final public function addHandlerOnPriorityDependency(hookHandler $handler, $priority) {
        $priority += 1;
		$hash = spl_object_hash($handler);
		unset($this->priorityBased['min'][self::NORMAL][$hash]);
		if (!isset($this->priorityBased['min'][$priority])) {
			$this->priorityBased['min'][$priority] = array();
		}
		$this->priorityBased['min'][$priority][$hash] = $handler;
        if (!isset($this->prioritiesHandlers[$priority]))
            $this->prioritiesHandlers[$priority] = new dummyPriorityhookHandler();
		$this->dependencyMap[$hash][] = $this->prioritiesHandlers[$priority];

	}

	/**
	 * Register dependency for an execution of handlers of specific class on a specific handler
	 *
	 * Useful to create dependencies with hook handlers registered to the controller or directly with
	 * the wordpress core API.
	 *
	 * For handlers registered with the controller it just creats a dependency relationship,
	 * but for classes of handler set in core it finds the priority of the core handler
	 * and assigns the handler an higher priority (lower priority number)
	 *
	 * @param string      $className The name of the class
	 * @param hookHandler $handler   The handler
	 *
	 * @since 0.0.1
	 */
	final public function addClassOnHandlerDependency($className, hookHandler $handler) {
		foreach ($this->classes[$className] as $o) {
			$this->addHandlerOnHandlerDependency($o,$handler);
		}

		// take into account the handlers registered via add_action/add_filter
		foreach ($this->getCoreHokkCallbacks() as $priority => $hook) {
			$callback = $hook['function'];
			if (is_array($callback)) {
				if (get_class($callback[0]) == $className) {
					$this->addPriorityOnHandlerDependency($handler,$priority);
				}
			}
		}
	}

	/**
	 * Register dependency for an execution of a function on a specific handler
	 *
	 * Useful to create dependencies with hook handlers registered directly with
	 * the wordpress core API. The "function" can be any php callable object, either a function name
	 * or an array of [$object,method]
	 *
	 * The way this works is by looking at the handler set in core, find the priority
	 * the function has and assign the handler an higher priority (lower priority number)
	 *
	 * @param callable $function 	The priority before which the handler may execute
	 * @param hookHandler $handler	The handler that should be handled before the function
	 *
	 * @since 0.0.1
	 */
	final public function addFunctionOnHandlerDependency(callable $function, hookHandler $handler) {
		// take into account the handlers registered via do_action/do_filter
		foreach ($this->getCoreHokkCallbacks() as $priority => $hook) {
			$callback = $hook['function'];
			if ($callback == $function) {
				$this->addPriorityOnHandlerDependency($priority,$handler);
			}
		}
	}

	/**
	 * Register dependency for an execution of handlers of specific priority on a specific handler
	 *
	 * Causes the handlers registered for a priority and lower priorities to be executed only after
	 * a specific handler was handled.
	 * In practical terms, this sets the lowest priority in which the specific handler can be
	 * processed to one higher then $priority.
	 *
	 * @param int $priority 		The priority before which the handler may execute
	 * @param hookHandler $handler	The handler that should be handled before the priority
	 *
	 * @since 0.0.1
	 */
	final public function addPriorityOnHandlerDependency($priority,hookHandler $handler) {
        $priority -= 1;
		$hash = spl_object_hash($handler);

		// clean default
		unset($this->priorityBased['max'][self::LATEST-1][$hash]);

		if (!isset($this->priorityBased['max'][$priority]))
			$this->priorityBased['max'][$priority] = array();
		$this->priorityBased['max'][$priority][$hash] = $handler;
	}

	/**
	 * Build the dependency map
	 *
	 * For each handler, ask the handler to regiter its dependencies by calling the
	 * add...Handler... Dependency() APIs
	 * By default each handler will be executed as early as NORMAL priority but
	 * not later then LATES-1 priority
	 *
	 * @since 0.0.1
	 */
	final protected function buildDependencyMap() {

		$this->dependencyMap = array();
		$this->prioritiesHandlers = array();
        $p = new dummyPriorityhookHandler();
		$this->prioritiesHandlers[self::NORMAL] = $p;
        $this->priorityBased = array('min' => array(),'max' => array());
		//$this->priorityBased['min'][self::NORMAL][spl_object_hash($p)] = $p;
		foreach ($this->objects as $hash => $handler) {
			$this->priorityBased['min'][self::NORMAL][$hash] = $handler;
			$this->dependencyMap[$hash] = array('priority' => $this->prioritiesHandlers[self::NORMAL]);
			$this->priorityBased['max'][self::LATEST-1][$hash] = $handler;
			$handler->registerDependencies($this);
		}
	}

	/**
	 * Verify that the handler implements the required methods to handle the HookHandler
	 *
	 * By defulat handlers need to implement a method with the name of the hook,
	 * but this can be ovveridden by extending classes.
	 *
	 * If the test fails, a php error is triggered
	 *
	 * @param  HookHandler $handler The handler to verify
	 *
	 * @since 0.0.1
	 */
	protected function verifyHandlerType(HookHandler $handler) {
		if (!is_callable(array($handler,$this->hookName))) {
			trigger_error('Could not locate a method named: '.$this->hookName.' or a generic __call method on the handler. Handler methods has to be the same as the hook name or provide a _call method',E_USER_ERROR);
		}
	}

	/**
	 * Mark an handler as handled
	 * @param  hookHandler $handler The handler to mark
	 *
	 * @since 0.0.1
	 */
	final private function markAsHandled(hookHandler $handler) {
		$this->handled[spl_object_hash($handler)] = true;
	}

	/**
	 * Check if an handler is marked ad handeled
	 * @param  hookHandler $handler The handler to check
	 *
	 * @since 0.0.1
	 */
	final private function wasHandled(hookHandler $handler) {
		return isset($this->handled[spl_object_hash($handler)]);
	}

	/**
	 * Invokes handlers that can and should be handled in specific priority
	 *
	 * Triggerd by the priorityHelper when its handler is called by wordpress core
	 * and processes all the handlers that can be run at this priority.
	 *
	 * For processing it first marks the dummy priority handler as handled then
	 * tries to process all the handler for which it is the highest priority
	 * in which they might be called, and then do the same for the handlers for
	 * which it is the lowest priority in which they can be called
	 *
	 * @param  int $priority    The priority
	 * @param  array  $args     The parameters that should be passed to the handlers
	 * @return mixed            Whatever the last handler returned
	 */
	final public function handlePriority($priority, array $args) {
        $this->currentArgs = $args;

		if (isset($this->priorityBased['min'][$priority])) {
			// mark the priority dependency "handler" as handled
			$this->markAsHandled($this->prioritiesHandlers[$priority]);

			// now do the real handlers
			foreach ($this->priorityBased['min'][$priority] as $handler) {
				$this->resolveDependenciesAndInvokeHandler($handler);
			}
		}

		if (isset($this->priorityBased['max'][$priority])) {
			$allDone = true;
			foreach ($this->priorityBased['max'][$priority] as $handler) {
				$allDone &= $this->resolveDependenciesAndInvokeHandler($handler);
			}

			// verify there are no "stuck" handlers
			if (!$allDone) {
				foreach ($this->priorityBased['max'][$priority] as $handler) {
					if (!$this->wasHhandled($handler))  // wasn't processed
						trigger_error('Could handle n handler in the required permission range',E_USER_ERROR);
                    $this->markAsHandled($$handler);
				}
			}
		}

        return $this->priorityHandlerReturnValue();
	}

	/**
	 * Add an handler to be invoked by the controller
	 *
	 * Handlers added have to implement a method with the controller's hook name
	 * The compliance checking can be changed by ovverriding the verifyHandlerType
	 * method
	 *
	 * @param hookHandler $hookHandler The handler to add
	 *
	 * @since 0.0.1
	 */
	final public function addHandler(hookHandler $hookHandler) {

		$this->verifyHandlerType($hookHandler);

		$objectHash = spl_object_hash($hookHandler);
		$this->classes[get_class($hookHandler)][$objectHash] = $hookHandler;
		$this->objects[$objectHash] = $hookHandler;
	}

	/**
	 * Removes an handler from the controller
	 *
	 * @param  hookHandler $hookHandler The handler to remove
	 *
	 * @since 0.0.1
	 */
	final public function removeHandler(hookHandler $hookHandler) {
		$objectHash = spl_object_hash($hookHandler);
		unset($this->classes[get_class($hookHandler)][$objectHash]);
		unset($this->objects[$objectHash]);
	}
}
