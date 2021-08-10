<?php

namespace Pages;

class ErrorController extends \Controller
{
	var $slug = 'error';

	public function __construct($config, $pathArray)
	{
		parent::__construct($config);

		// Set page info
		$this->pageinfo = array(
			'title' => 'Error',
			'slug' => $this->slug
		);

		if ($pathArray[1] == '401')
		{
			$this->Error401();
			return;
		}

		if ($pathArray[1] == '403')
		{
			$this->Error403();
			return;
		}

		if ($pathArray[1] == '404')
		{
			$this->Error404();
			return;
		}

		if ($pathArray[1] == '500')
		{
			$this->Error500();
			return;
		}

		if ($pathArray[1] == 'report')
		{
			$this->ErrorReport();
			return;
		}

		// Generic error page
		$this->default();
	}

	// Error 401 - Unauthorized
	public function Error401()
	{
		header('HTTP/1.1 401 Unauthorized');

		$this->smarty->assign('dialogue', [
			'width' => '60%',
			'title' => 'Error 401 - Unauthorized',
			'string' => 'You are not authorized to view this page.'
		]);
		$this->display('error.tpl');
	}

	// Error 403 - Forbidden
	public function Error403()
	{
		header('HTTP/1.1 403 Forbidden');

		$this->smarty->assign('dialogue', [
			'width' => '60%',
			'title' => 'Error 401 - Forbidden',
			'string' => 'You are not authorized to view this page.'
		]);
		$this->display('error.tpl');
	}

	// Error 404 - Not Found
	public function Error404()
	{
		header('HTTP/1.1 404 Not Found');

		$this->smarty->assign('dialogue', [
			'width' => '60%',
			'title' => 'Error 404 - Not Found',
			'string' => 'This page is not found.'
		]);
		$this->display('error.tpl');
	}

	// Error 500 - Internal Server Error
	public function Error500()
	{
		header('HTTP/1.1 500 Internal Server Error', true, 500);

		$this->smarty->assign('dialogue', [
			'width' => '60%',
			'title' => 'Error 500 - Internal Server Error',
			'string' => 'This page engaged an internal server error.'
		]);
		$this->display('error.tpl');
	}

	// Throw an error from a bad report
	public function ErrorReport()
	{
		$this->smarty->assign('dialogue', [
			'width' => '60%',
			'title' => 'Report Error',
			'string' => 'This report returned no content.'
		]);
		$this->display('error.tpl');
	}

	// Generic error
	public function default()
	{
		$this->smarty->assign('dialogue', [
			'width' => '60%',
			'title' => 'Nonspecific Error',
			'string' => 'This is a generic error page.'
		]);
		$this->display('error.tpl');
	}
}

?>
