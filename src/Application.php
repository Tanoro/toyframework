<?php

class Application
{
	/**
	* Route the URL to a page
	**/
	function __construct($config)
	{
		// Determine route
		$parts = $this->route();

		$basename = $parts[0];
		$controller = 'Pages\\' . ucfirst($basename) . 'Controller';

		if (class_exists($controller))
		{
			$page = new $controller($config, $parts);
		}
		else
		{
			// Thow a 404 not found
			$page = new Pages\ErrorController($config, [1 => 404]);
		}

		$page->DB->close();
	}

	// Split the URL and determine the route
	function route()
	{
		if ($_SERVER['REQUEST_URI'] === '/' || empty($_SERVER['REQUEST_URI']))
		{
			// Default to index page when no controller is selected
			return ['index'];
		}

		$uri = $_SERVER['REQUEST_URI'];

		if (substr($uri, 0, 1) == '/')
		{
			// Remove the opening slash if there is one
			$uri = substr($uri, 1, strlen($uri));
		}

		if (substr($uri, -1, 1) == '/')
		{
			// Remove the trailing slash if there is one
			$uri = substr($uri, 0, strlen($uri) - 1);
		}

		if (strlen($uri) > 0)
		{
			// Process Data
			$array = explode('/', $uri); // Explode the URI using '/'
			$num = sizeof($array); // How many items in the array?
			$url_array = array(); // Init our new array

			for ($i = 0; $i < $num; $i++)
			{
				$url_array[$i] = $array[$i];
			}
		}

		return $url_array;
	}
}

?>
