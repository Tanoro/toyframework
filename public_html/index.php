<?php

// Define root up one directory
define('__ROOT__', realpath(__DIR__ . '/../'));


/*
* Required Libraries
********************/

require_once(__ROOT__ . '/config/config.inc.php');
require_once(__ROOT__ . '/vendor/autoload.php');

spl_autoload_register(function($class) {
	if (!preg_match('/Smarty|PHPExcel/i', $class)) {
		if (file_exists(__ROOT__ . '/src/' . str_replace('\\', '/', $class) . '.php'))
		{
			include(__ROOT__ . '/src/' . str_replace('\\', '/', $class) . '.php');
		}
	}
});


/*
* Assemble page content
*************************/

$Application = new Application($config);
