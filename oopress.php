<?php
/*
Plugin Name: Object Oriented Wrapper
Plugin URL: 
Description: Adds an OOP wrapper above and around wordpress APIs
Version: 1.0
Author: Mark Kaplun
Contributors: mark-k
*/

// Register auto loading handler for the classes. 
// The core assumption is that classes are in the oopress namespace 
// and the directory in which this file resides is called oopress
spl_autoload_register(function ($class) {
	if (strncmp($class,'oopress/',8))
		require dirname (__DIR__).'\\'.$class.'.php';
});

function oopress_get_hook_controller_registry() {
	static $registry;
	
	if (!isset($registry))
		$registry = new oopress\hooks\hookControllerRegistry();
	return $registry;
}
