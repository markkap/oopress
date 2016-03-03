<?php

// Register auto loading handler for the classes.
// The core assumption is that classes are in the oopress namespace
// and the directory in which this file resides is called oopress
spl_autoload_register(function ($class) {
	if (strncmp($class,'oopress/',8))
		if (file_exists(dirname (__DIR__).'\\'.$class.'.php'))
			require dirname (__DIR__).'\\'.$class.'.php';
});
