<?php

namespace oopress\actions;

class actionHandlerSweetener implements oopress\actions\actionHandler {

	private $dependsOn=array();
	private $runsBefore=array()
	
	public function registerDependencies(baseActionController $actionController) {
		foreach (this->dependsOn as $d) {
			if (is_int($d))
				$actionController->addHandlerOnPriorityDependency($this,$d);
			else if (is_callable($d))
				$actionController->addHandlerOnFunctionDependency($this,$d);
			else if (is_string($d))
				$actionController->addHandlerOnClassDependency($this,$d);
			else if ($d instanceof oopress\actions\actionHandler)
				$actionController->addHandlerOnHandlerDependency($this,$d);
		}
		foreach (this->runsBefore as $d) {
			if (is_int($d))
				$actionController->addPriorityOnHandlerDependency($d,$this);
			else if (is_callable($d))
				$actionController->addFunctionOnHandlerDependency($d,$this);
			else if (is_string($d))
				$actionController->addClassOnHandlerDependency($d,$this);
			else if ($d instanceof oopress\actions\actionHandler)
				$actionController->addHandlerOnHandlerDependency($d,$this);
		}
	}		
	
	protected dependsOnHandler(actionHandler $handler) {
		$this->dependsOn[] = $handler;
	}
	
	protected dependsOnClass(string $className) {
		$this->dependsOn[] = $className;
	}
	
	protected dependsOnFunction(callable $function) {
		$this->dependsOn[] = $function;
	}
	
	protected dependsOnPriority(int $priority) {
		$this->dependsOn[] = $priority;
	}
	
	protected runsBeforeHandler(actionHandler $handler) {
		$this->runsBefore[] = $handler;
	}
	
	protected runsBeforeClass(string $className) {
		$this->runsBefore[] = $className;
	}
	
	protected runsBeforeFunction(callable $function) {
		$this->runsBefore[] = $function;
	}
	
	protected runsBeforePriority(int $priority) {
		$this->runsBefore[] = $priority;
	}	
}
