<?php

class Request
{
    /**
     * The construct method can accommodate custom request methods
    */
	function __construct()
	{
		// Implenting RESTful method in PHP
		// https://developer.okta.com/blog/2019/03/08/simple-rest-api-php#implement-the-php-rest-api

		// To flip the request method, post it to _method
		if (isset($_POST['_method']))
		{
			if (strtolower($_POST['_method']) == 'put')
			{
				// The PUT method is for editing existing records
				$_SERVER['REQUEST_METHOD'] = 'PUT';
			}

			if (strtolower($_POST['_method']) == 'delete')
			{
				// The DELETE method is for deleting records
				$_SERVER['REQUEST_METHOD'] = 'DELETE';
			}
		}
	}

	// Determine request type
	public function is(string $string)
	{
		if ($_SERVER['REQUEST_METHOD'] === $string)
		{
			return true;
		}

		return false;
	}

	// Return a _GET value
	public function get(string $index)
	{
		if (!isset($_GET[$index]))
		{
			return null;
		}

		return $_GET[$index];
	}

	// Return a _POST value
	public function post(string $index)
	{
		if (!isset($_POST[$index]))
		{
			return null;
		}

		return $_POST[$index];
	}
}

?>
