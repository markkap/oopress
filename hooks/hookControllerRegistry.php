<?php

namespace oopress\hooks;

class hookControllerRegistry {

	const ACTION = 'oopress\hooks\actionController';
	const FILTER = 'oopress\hooks\filterController';

	private $controllers = array();
	
	public function __construct() {
		add_action('all',array($this,'handleHook'));
	}
	
	public function handleHook() {
		$action = current_filter();
		if (isset($this->controllers[$action]))
			$this->controllers[$action]->hookHandler();
	}
	
	public function registerController(hookController $controller) {
		$name = $controller->getHookName();
		if (isset($this->controllers[$name]))
			trigger_error('controller already exist for: '.$name,E_USER_ERROR);				

		$this->controllers[$name] = $controller;
	}
	
	public function getController($type, $hookName) {

		if (!isset($this->controllers[$hookName])) {
			if (!in_array('oopress\\hooks\\hookController',class_parents($type)))
				trigger_error($type.' do not extend class oopres\\hooks\\hookControls which is a requirement of each hook controller',E_USER_ERROR);
			$this->controllers[$hookName] = new $type($hookName);		
		}

		if (!$this->controllers[$hookName] instanceof $type)
			trigger_error('A hook controller of type '.$type.' was requested for hook '.$hookName.' but a controller of different type '.$type.' exists for it',E_USER_ERROR);
			
		return $this->controllers[$hookName];
	}
}
