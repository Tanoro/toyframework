<?php

spl_autoload_register(function($class)
{
	// 3rd party scripts will handle their own components
    //if (strpos($class, 'Smarty') === false)
    if (!preg_match('/Smarty|PHPExcel/i', $class))
	{
		include(__ROOT__ . '/src/' . str_replace('\\', '/', $class) . '.php');
	}
});

?>
