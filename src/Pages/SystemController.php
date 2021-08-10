<?php

namespace Pages;

class SystemController extends \Controller
{
	var $slug = 'system';

	public function __construct($config, $pathArray)
	{
		parent::__construct($config);

		// Set page info
		$this->pageinfo = array(
			'title' => 'System Status',
			'slug' => $this->slug,
			'path' => $pathArray
		);

		// Default view
		$this->view();
	}

	public function view()
	{
		$status = array();
		$disabled = explode(',', ini_get('disable_functions'));
		$post_receive = __ROOT__ . '/repo.git/hooks/post-receive';

		if (is_executable($post_receive) === false)
		{
			$status[] = array(
				'alert' => true,
				'text' => '/repo.git/hooks/post-receive is not executable. Check the file permissions.'
			);
		}
		else
		{
			$status[] = array(
				'alert' => false,
				'text' => '/repo.git/hooks/post-receive is executable'
			);
		}

		if (posix_getpwuid(fileowner($post_receive)) == 'root')
		{
			$status[] = array(
				'alert' => true,
				'text' => 'The post-receive hook is owned by root'
			);
		}

		if (in_array('exec', $disabled) === false)
		{
			$status[] = array(
				'alert' => false,
				'text' => 'PHP exec function is enabled'
			);
		}
		else
		{
			$status[] = array(
				'alert' => true,
				'text' => 'PHP exec function is disabled'
			);
		}

		if (function_exists('curl_init'))
		{
			$status[] = array(
				'alert' => false,
				'text' => 'cURL extension is detected'
			);
		}
		else
		{
			$status[] = array(
				'alert' => true,
				'text' => 'cURL extension not detected'
			);
		}

		if (class_exists('PDO'))
		{
			$status[] = array(
				'alert' => false,
				'text' => 'PDO detected'
			);
		}
		else
		{
			$status[] = array(
				'alert' => true,
				'text' => 'PDO not detected! Phinx requires PDO to function.'
			);
		}

		if (class_exists('mysqli'))
		{
			$status[] = array(
				'alert' => false,
				'text' => 'Mysqli detected'
			);
		}
		else
		{
			$status[] = array(
				'alert' => true,
				'text' => 'Mysqli not detected'
			);
		}

		if (method_exists('mysqli_stmt', 'get_result'))
		{
			$status[] = array(
				'alert' => false,
				'text' => 'Mysqli->get_result detected'
			);
		}
		else
		{
			$status[] = array(
				'alert' => true,
				'text' => 'Mysqli->get_result not detected. Check for Mysqlind.'
			);
		}

		if (class_exists('DOMDocument'))
		{
			$status[] = array(
				'alert' => false,
				'text' => 'DOMDocument detected'
			);
		}
		else
		{
			$status[] = array(
				'alert' => true,
				'text' => 'DOMDocument not detected'
			);
		}

		if (class_exists('ZipArchive'))
		{
			$status[] = array(
				'alert' => false,
				'text' => 'ZipArchive detected'
			);
		}
		else
		{
			$status[] = array(
				'alert' => true,
				'text' => 'ZipArchive not detected. PhpExcel may not function.'
			);
		}

		if (function_exists('proc_open'))
		{
			$status[] = array(
				'alert' => false,
				'text' => 'PHP proc_open function detected'
			);
		}
		else
		{
			$status[] = array(
				'alert' => true,
				'text' => 'Some extensions require the proc_open function.'
			);
		}

		if (function_exists('pcntl_signal'))
		{
			$status[] = array(
				'alert' => false,
				'text' => 'PHP pcntl_signal function detected'
			);
		}
		else
		{
			$status[] = array(
				'alert' => true,
				'text' => 'Scrapers need the pcntl_signal function to detect crashing.'
			);
		}

		$this->smarty->assign('status', $status);
		$this->display($this->slug . '.tpl');
	}
}
