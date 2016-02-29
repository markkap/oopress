<?php

namespace oopress\hooks;

class dummyPriorityHookHandler implements hookHandler {

	public function __construct($priority,$depends='') {
		$this->priority = $priority;
	}
					
	public function registerDependencies(hookController $hookController) {
	}
	
	function __call( $name , array $arguments ) {
		return;
	}
}

class priorityHelper {
	private $controller;
	private $priority;
	
	public function __construct($controller,$priority) {
		$this->controller = $controller;
		$this->priority = $priority;
		add_action($this->controller->getHookName(),array($this,'handle'),$this->priority,100);
	}
	
	public function handle($args='') {

        $this->controller->handlePriority($this->priority,func_get_args());
	}
    
    public function remove() {
		remove_action($this->controller->getHookName(),array($this,'handle'),$this->priority);
    }
}

abstract class hookController {

	const EARLIEST = 1;
	const NORMAL = 10;
	const LATEST = 999999;
	
	protected $classes = array();
	protected $objects = array();
	protected $handled = array(); // helper array to indicate which handlers were done
	protected $hookName;
	protected $dependencyMap = array();
	protected $priorityBased = array('min' => array(),'max' => array());
	private $prioritiesHandlers = array();
	private $prioritiesHelpers = array();    
    protected $currentArgs;

	public function __construct($hookName) {
		$this->hookName = $hookName;
	}
	
	abstract protected function invokeHandler(hookHandler $hookHandler);
    
    abstract protected function priorityHandlerReturnValue();
    
    final public function getHookName() {
        return $this->hookName;
    }
    
    final protected function resolveDependenciesAndInvokeHandler(hookHandler $hookHandler) {

        $hash = spl_object_hash($hookHandler);
		
		if ($this->wasHandled($hookHandler)) { // already done
			return true;
		}
		
		$dependenciesDone = true;
		if (isset($this->dependencyMap[$hash])) {
			foreach ($this->dependencyMap[$hash] as $d) {
				$dependenciesDone &= $this->resolveDependenciesAndInvokeHandler($d);
			}
		}
		
		if ($dependenciesDone) {
			$this->invokeHandler($hookHandler);
			$this->markAsHandled($hookHandler);
		}
		
		return $dependenciesDone;
		
	}

    final private function handlerCleanup() {
        foreach ($this->priorityHelpers as $p) 
            $p->remove();
            
        remove_action($this->hookName,array($this,'handlerCleanup'),self::LATEST+1);
        $this->priorityHelpers = array();
    }
    
	final public function hookHandler() {
				
		$this->buildDependencyMap();
		$this->handled = array();
        $this->priorityHelpers = array();
        
		foreach ($this->priorityBased['min'] as $priority=>$v) {
			$this->priorityHelpers[] = new priorityHelper($this,$priority);  // the helper handles registration,handling and removal of the handler
		}		
		foreach ($this->priorityBased['max'] as $priority=>$v) {
            if (!isset($this->priorityBased['min'][$priority]))
                $this->priorityHelpers[] = new priorityHelper($this,$priority);  // the helper handles registration,handling and removal of the handler
		}
        
        add_action($this->hookName,array($this,'handlerCleanup'),self::LATEST+1);
   	}	
	
	final public function addHandlerOnHandlerDependency(hookHandler $handler, hookHandler $dependsOnHandler) {
		if (!isset($this->objects[spl_object_hash($handler)]))
			trigger_error('Source dependency object is not registered in the controller',E_USER_ERROR);

		if (!isset($this->objects[spl_object_hash($dependsOnHandler)]))
			trigger_error('Depended upon object is not registered in the controller',E_USER_ERROR);
		
		$this->dependencyMap[spl_object_hash($handler)][] = $depends;
	}
	
	final public function addHandlerOnClassDependency(hookHandler $handler, $className) {
		foreach ($this->classes[$className] as $o) {
			$this->addHandlerOnHandlerDependency($handler,$o);
		}
		
		// take into account the handlers registered via do_hook/do_filter
		global $wp_filter;
		if (isset($wp_filter[$this->hookName])) {
			foreach ($wp_filter[$this->hookName] as $priority => $hook) {
				$callback = $hook['function'];
				if (is_array($callback)) {
					if (get_class($callback[0]) == $className) {
						$this->addHandlerOnPriorityDependency($handler,$priority);
					}
				}
			}
		}
	}
	
	final public function addHandlerOnFunctionDependency(hookHandler $handler, callable $function) {
		// take into account the handlers registered via do_hook/do_filter
		global $wp_filter;
		if (isset($wp_filter[$this->hookName])) {
			foreach ($wp_filter[$this->hookName] as $priority => $hook) {
				$callback = $hook['function'];
				if ($callback == $function) {
					$this->addHandlerOnPriorityDependency($handler,$priority);
				}
			}
		}
	}
		
	final public function addHandlerOnPriorityDependency(hookHandler $handler, $priority) {
        $priority += 1;
		$hash = spl_object_hash($handler);
		unset($this->priorityBased['min'][self::NORMAL][$hash]);
		if (!isset($this->priorityBased['min'][$priority])) {
			$this->priorityBased['min'][$priority] = array();
		}
		$this->priorityBased['min'][$priority][$hash] = $handler;
        if (!isset($this->prioritiesHandlers[$priority]))
            $this->prioritiesHandlers[$priority] = new dummyPriorityhookHandler($priority);
		$this->dependencyMap[$hash][] = $this->prioritiesHandlers[$priority];

	}

	final public function addClassOnHandlerDependency($className, hookHandler $handler) {
		foreach ($this->classes[$className] as $o) {
			$this->addHandlerOnHandlerDependency($o,$handler);
		}
		
		// take into account the handlers registered via do_hook/do_filter
		global $wp_filter;
		if (isset($wp_filter[$this->hookName])) {
			foreach ($wp_filter[$this->hookName] as $priority => $hook) {
				$callback = $hook['function'];
				if (is_array($callback)) {
					if (get_class($callback[0]) == $className) {
						$this->addPriorityOnHandlerDependency($handler,$priority);
					}
				}
			}
		}
	}
	
	final public function addFunctionOnHandlerDependency(callable $function, hookHandler $handler) {
		// take into account the handlers registered via do_hook/do_filter
		global $wp_filter;
		if (isset($wp_filter[$this->hookName])) {
			foreach ($wp_filter[$this->hookName] as $priority => $hook) {
				$callback = $hook['function'];
				if ($callback == $function) {
					$this->addPriorityOnHandlerDependency($priority,$handler);
				}
			}
		}
	}
		
	final public function addPriorityOnHandlerDependency($priority,hookHandler $handler) {
        $priority -= 1;
		$hash = spl_object_hash($handler);
		unset($this->priorityBased['max'][self::LATEST-1][$hash]);
		if (!isset($this->priorityBased['max'][$priority]))
			$this->priorityBased['max'][$priority] = array();
		$this->priorityBased['max'][$priority][$hash] = $handler;
	}
			
	final protected function buildDependencyMap() {
	
		$this->dependencyMap = array();
		$this->prioritiesHandlers = array();
        $p = new dummyPriorityhookHandler(self::NORMAL);
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
	
	protected function verifyHandlerType($handler) {
		if (!is_callable(array($handler,$this->hookName))) {
			trigger_error('Could not locate a method named: '.$this->hookName.' or a generic __call method on the handler. Handler methods has to be the same as the hook name or provide a _call method',E_USER_ERROR);				
		}		
	}

	final private function markAsHandled($handler) {
		$this->handled[spl_object_hash($handler)] = true;
	}
	
	final private function wasHandled($handler) {
		return isset($this->handled[spl_object_hash($handler)]);
	}
	
	final public function handlePriority($priority, array $args) {
        $this->currentArgs = $args;
        
		if (isset($this->priorityBased['min'][$priority])) {
			$this->markAsHandled($this->prioritiesHandlers[$priority]);  // mark the priority dependency "handler" as handled
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
	
	final public function addHandler(hookHandler $hookHandler) {
							
		$this->verifyHandlerType($hookHandler);
		
		$objectHash = spl_object_hash($hookHandler);
		$this->classes[get_class($hookHandler)][$objectHash] = $hookHandler;
		$this->objects[$objectHash] = $hookHandler;
	}

	final public function removeHandler(hookHandler $hookHandler) {
		$objectHash = spl_object_hash($hookHandler);
		unset($this->classes[get_class($hookHandler)][$objectHash]);
		unset($this->objects[$objectHash]);
	}	
}
