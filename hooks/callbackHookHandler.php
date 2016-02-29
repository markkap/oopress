<?php

namespace oopress\hooks;

class callbackHookHandler implements hookHandler {

	private $priority;
	private $callback;
	
	public function __construct($priority, callable $callback) {
		$this->priority = $priority;
		$this->callback = $callback;
	}
	
	public function registerDependencies(hookController $hookController) {
		$hookController->addHandlerOnPriorityDependency($this,$this->priority - 1);
		$hookController->addPriorityOnHandlerDependency($this->priority + 1,$this);
	}

	public function __call($name , array $arguments) {
		return call_user_func_array($this->callback,$arguments);
	}
}

