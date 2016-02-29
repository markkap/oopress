<?php

namespace oopress\hooks;

class filterController extends hookController {

	public function __construct($hookName) {
		parent::__construct($hookName);
	}
	
	final protected function invokeHandler(hookHandler $hookHandler) {
	
		$ret = call_user_func_array(array($handler,$this->hookName),$this->currentArgs);
        $this->currentArgs[0] = $ret;
	}		
    
    protected function priorityHandlerReturnValue() {
        return $this->currentArgs[0];
    }
}

