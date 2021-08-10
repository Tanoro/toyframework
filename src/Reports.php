<?php

// Prepared statement binding types
define('INTEGER', 'i');
define('STRING', 's');
define('DOUBLE', 'd');
define('BLOB', 'b');
define('HTML', 'html');
define('LITERAL', 'literal'); // A literal string with only a trim

use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Reports
{
	// App controller environment
	var $DB = null;
	var $request = null;

	var $config = array();

	// Time information
	var $UTC;
	var $UTCTime;

	var $report = null;
	var $style = array();


	/**
	* Setup the application environment
	**/
	function __construct($config, $ajax=false)
	{
		/*
		* Prepare the environment
		*************************/

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


		// Get report styles
		require_once(__ROOT__ . '/src/Reports/styles.php');

		$this->style = $style;

		/*
		* Select the report structure
		******************************/

		// Create new Spreadsheet object
		$this->report = new Spreadsheet();

		// Get request info
		$this->request = new Request;
	}

	function returnFile($filename)
	{
		// Force download
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		header('Cache-Control: max-age=0');

		$writer = IOFactory::createWriter($this->report, 'Xlsx');
		$writer->save('php://output');
	}

}

?>
