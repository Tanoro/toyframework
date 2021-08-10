<?php

// Prepared statement binding types
define('INTEGER', 'i');
define('STRING', 's');
define('DOUBLE', 'd');
define('BLOB', 'b');
define('HTML', 'html');
define('LITERAL', 'literal'); // A literal string with only a trim

class Controller
{
	// App controller environment
	var $DB = null;
	var $smarty = null;
	var $request = null;

	var $config = array();
	var $pageinfo = array();
	var $settings = array();
	var $profile = array();
	var $system_arrays = array();
	var $system_error = null;
	var $system_warning = null;

	// Time information
	var $UTC;
	var $UTCTime;

	// Ajax environment
	var $response = array();
	var $error = array();
	var $maskErrors = true;


	/**
	* Setup the application environment
	**/
	function __construct($config, $ajax=false)
	{
		/*
		* Prepare the environment
		*************************/

		$config['starttime'] = microtime();

		//error_reporting(E_ALL);

		header( 'Cache-control: no-cache' );
		header( 'Cache-control: no-store' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Load ENV
		$Loader = new josegonzalez\Dotenv\Loader(__ROOT__ . '/config/.env');
		$Loader->parse();
		$Loader->toEnv();

		// Set locale to US English; This will be used to format currency
		setlocale(LC_MONETARY, $config['locale']['monetary']);
		date_default_timezone_set($config['locale']['timezone']);


		/*
		* Time calculations
		*******************/

		$UTC = new DateTimeZone('UTC');
		$UTCTime = new DateTime('now', $UTC);

		define('UTCTIME', $UTCTime->format('Y-m-d H:i:s'));
		define('SYSTIME', time());


		/*
		* Database Preparations
		***********************/

		/*
		* Connect to the database by calling on the Maj_db constructor method. The appropriate database library
		* will be selected automatically, but can be specified in the second argument (i.e. MySQL, MySQLi, PDO).
		*/
		$this->DB = new Database($config);

		// Connect to database
		$this->DB->connect();


		/*
		* Initial environment
		**********************/

		// Pass the config back so the application can use it everywhere
		$this->config = $config;

		// Get system settings
		//$this->fetchSettings();


		/*
		* Select the application structure
		**********************************/

		// We may need some of the local settings when reporting database errors.
		$this->env['settings'] = $this->settings;


		/*
		* Initialize Template Engine
		*****************************/

		$this->smarty = new Smarty;
		$this->smarty->debugging = false;
		$this->smarty->caching = false;
		$this->smarty->cache_lifetime = 120;
		$this->smarty->error_reporting = E_ALL & ~E_NOTICE;

		// Set the cache directory
		$this->smarty->setCompileDir(__ROOT__ . '/src/Template/templates_c');
		$this->smarty->setCacheDir(__ROOT__ . '/src/Template/templates_c');

		// Get template directory
		$this->smarty->addTemplateDir(array(
			__ROOT__ . '/src/Template'
		));

		// Put job profile into Smarty
		$this->smarty->assign('config', $this->config);
		//$this->smarty->assign('settings', $this->settings);

		if ($ajax === false)
		{
			/*
			* Javascript and stylesheets
			*/

			// Import Stylesheets
			$this->smarty->append('embedstyles', '/css/stylesheet.css');
			$this->smarty->append('embedstyles', '/js/dialogues/dialogues.css');

			// Import Javascript files
			$this->smarty->append('embedjs', 'https://code.jquery.com/jquery-1.7.1.min.js');

			// jQuery UI is needed for making anything draggable
			$this->smarty->append('embedstyles', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css');
			$this->smarty->append('embedjs', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js');
			$this->smarty->append('embedjs', '/js/dialogues/dialogues.js');

			// Check Phinx
			//$this->checkPhinx();
		}

		// Get request info
		$this->request = new Request;
	}

	// Redirect to another local page
	function redirect($page)
	{
		header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $page);
	}


	/*#######################
	*	App Environment		*
	*#######################*/


	/**
	* Get the system settings from the settings table
	**/
	function fetchSettings()
	{
		if (sizeof($this->settings) > 0)
		{
			// We already have our settings; Stop here
			return;
		}

		// Make sure we're connected to the database
		if (!$this->DB->conn)
		{
			if ($this->DB->connect() === false)
			{
				return false;
			}
		}

		$result = $this->DB->query("SELECT hook, data FROM settings");

		if ($result->numrows != 0)
		{
			while($row = $this->DB->fetch($result))
			{
				$this->settings[ $row['hook'] ] = $row['data'];
			}
		}
	}

	/**
	* Display a template via Smarty
	**/
	function display($tpl)
	{
		// Auto-detect a JS file for this slug
		if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $this->config['paths']['js'] . '/' . $this->slug . '.js'))
		{
			$this->smarty->append('embedjs', '/' . $this->config['paths']['js'] . '/' . $this->slug . '.js');
		}

		$this->smarty->assign('pageinfo', $this->pageinfo);
		$this->smarty->display($tpl);
	}

	/**
	* Visible user error
	**/
	public function displayError($error)
	{
		$this->system_warning = $error;
		$this->smarty->assign('system_warning', $this->system_warning);
	}
	
	/**
	* Check Phinx migrations
	**/
	function checkPhinx()
	{
		$result = $this->DB->query("SHOW TABLES LIKE 'phinxlog'");

		if ($result->numrows == 0)
		{
			$this->displayError('Database migrations need to be executed.');
			return false;
		}

		// Check migrations are up to date
		$r = $this->DB->fetchRow("SELECT `version` ver FROM phinxlog ORDER BY `version` DESC LIMIT 1", $result);

		if ($result->numrows != 0)
		{
			$files = scandir(__ROOT__ . '/db/migrations', SCANDIR_SORT_DESCENDING);
			$file_version = substr($files[0], 0, 14);

			if ($file_version != $r['ver'])
			{
				$this->displayError('DB migrations are pending. Version: ' . $file_version . '.');
			}
		}
		else
		{
			$this->displayError('DB migrations are pending.');
		}
	}

	/*###################
	*	Ajax Reporting	*
	*##################*/

	/**
	* Walk through an array and convert all characters to a string that is safe to use in HTML attributes.
	*
	* @param	array			The array to process
	**/
	function sanitizeResponse(&$array)
	{
		if (is_array($array))
		{
			if (count($array) == 0)
			{
				// Empty array
				return $array;
			}
			else
			{
				foreach ($array AS $k => $v)
				{
					if (is_array($array[$k]))
					{
						// Another array
						$array[$k] = $this->sanitizeResponse($array[$k]);
					}
					else
					{
						// String
						//$array[$k] = htmlspecialchars($array[$k], ENT_QUOTES, 'UTF-8');
						$array[$k] = preg_replace("/'/", "&apos;", $array[$k]);
					}
				}

				return $array;
			}
		}
		else
		{
			// Is a string
			//return htmlspecialchars($array, ENT_QUOTES, 'UTF-8');
			return preg_replace("/'/", "&apos;", $array);
		}
	}

	/**
	* Return the result of the response array as a json string and terminate the script.
	*
	* @param	mixed			The contents of the output variable at the point of return
	**/
	function ajaxReturn($output)
	{
		$this->response['output'] = $output;
		$this->response = $this->sanitizeResponse($this->response);

		echo json_encode($this->response);

		// Close the database
		if ($this->DB->conn)
		{
			$this->DB->close();
		}

		// Terminate
		exit;
	}
}

?>
