<?php

namespace oopress\hooks;

class actionController extends hookController {

	private $args;
	
	public function __construct($actionName) {
		parent::__construct($actionName);
	}

	protected function invokeHandler(hookHandler $handler) {
	
		$ret = call_user_func_array(array($handler,$this->hookName),$this->currentArgs);
		if (!empty($ret)) {
			trigger_error('hook should not return a value. error while handling '.$this->hookName,E_USER_ERROR);
		}			
	}
    
    protected function priorityHandlerReturnValue() {
        return null;
    }
}
