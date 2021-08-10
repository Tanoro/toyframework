<?php

namespace Pages;

class SettingsController extends \Controller
{
	var $slug = 'settings';

	public function __construct($config, $pathArray)
	{
		parent::__construct($config);

		// Set page info
		$this->pageinfo = array(
			'title' => 'Settings',
			'slug' => $this->slug
		);

		$this->view();
	}

	// View settings form
	public function view()
	{
		$result = $this->DB->query("SELECT id, setting, descrip, hook, data FROM settings ORDER BY setting ASC");

		if ($result->numrows != 0)
		{
			while($row = $this->DB->fetch($result))
			{
				$this->smarty->append('rows', $row);
			}
		}

		$this->display($this->slug . '.tpl');
	}
}

?>
