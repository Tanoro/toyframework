<?php

namespace Pages;

use \Helper;

class IndexController extends \Controller
{
	var $slug = 'index';

	public function __construct($config, $pathArray)
	{
		parent::__construct($config);

		// Add a JS file for all views
		//$this->smarty->append('embedjs', '/' . $this->config['paths']['js'] . '/JS_FILE.js');

		// Set page info
		$this->pageinfo = array(
			'title' => 'Toy Framework v' . APP_VERSION,
			'slug' => $this->slug,
			'path' => $pathArray
		);

		if (!isset($pathArray[1]))
		{
			$this->view();
			return;
		}

		// Default view
		$this->view();
	}

	// Default view
	public function view()
	{
		$this->display($this->slug . '.tpl');
	}
}

?>
