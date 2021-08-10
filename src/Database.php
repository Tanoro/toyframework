<?php
/*###################################################################################################
#				Majicko 2.3.7 @2008-2014 Bandwise LLC All Rights Reserved,							#
#								http://www.Majicko.com												#
#																									#
#				This file may not be redistributed in whole or significant part.					#
#				---------------- MAJICKO IS NOT FREE SOFTWARE ----------------						#
#									http://www.majicko.com											#
#																									#
###################################################################################################*/

define('MySQL', 'mysql');
define('MySQLi', 'mysqli');

define('ALLOW_ZERO', 'ALLOW_ZERO');
define('ALLOW_EMPTY_STRING', 'ALLOW_EMPTY_STRING');

// sprintf formats for the like method
define('WILDCARDS', '%%%s%%');
define('LEAD_WILDCARD', '%%%s');
define('TRAIL_WILDCARD', '%s%%');

// Connect to the database and adjust the environment to whatever MySQL library is available
class Database
{
	// Basic settings
	var $config = array();
	var $forceLib = false; // mysqli | mysql
	var $env = array();

	// Database connection
	var $conn = null;

	// MySQL transaction products
	var $query = '';
	var $result = array();
	var $stmt = null;
	var $metadata = null;
	var $rows = array();
	var $bind_types = null;
	var $bind_params = array();

	// Query meta data
	var $numrows = 0;
	var $affected_rows = 0;
	var $insert_id = 0;

	// Keep track of this stuff
	var $totalqueries = 0;
	var $dberrno = null;
	var $dberror = null;
	var $dberrorResponse = '';
	var $backtrace = array();

	// Where clause conditionals
	var $clauses;

	// Building where clause
	var $where = array();
	var $where_result;


	/**
	* Hold onto the configs and decide which MySQL lib to use
	*
	* @param	array			The CMS configs array
	* @param	string			An option to force a preferred library
	**/
	public function __construct($config, $forceLib=false)
	{
		$this->config = $config;
		$this->forceLib = $forceLib;

		// Force to use MySQLi
		if ($this->forceLib == 'mysqli')
		{
			if (!defined('DB_LIB'))
			{
				define('DB_LIB', 'mysqli');
			}

			return true;
		}

		// Force to use MySQL functions
		if ($this->forceLib == 'mysql')
		{
			// We are stuck with the crappy lib
			if (!defined('DB_LIB'))
			{
				define('DB_LIB', 'mysql');
			}

			return true;
		}


		// Dynamic lib selection
		if (class_exists('mysqli'))
		{
			// This lib is way better!
			if (!defined('DB_LIB'))
			{
				define('DB_LIB', 'mysqli');
			}

			return true;
		}

		if (function_exists('mysql_connect'))
		{
			// We are stuck with the crappy lib
			if (!defined('DB_LIB'))
			{
				define('DB_LIB', 'mysql');
			}

			return true;
		}

		// No MySQL lib?
		trigger_error('No MySQL library detected.', E_USER_ERROR);
	}


	/**
	* Connect to MySQL
	**/
	public function connect()
	{
		// If we are already connected, just do nothing.
		if ($this->conn)
		{
			return;
		}

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			// Connect to the database and test each step
			$this->conn = new mysqli($this->config['db']['server'], $this->config['db']['username'], $this->config['db']['password'], $this->config['db']['database_name']);

            if ($this->conn->connect_errno !== 0)
			{
				$this->dberrno = $this->conn->connect_errno;
				$this->dberror = '('. $this->conn->connect_errno .') '  . $this->conn->connect_error;

				// Mask any errors that may have happened
				if ($this->config['db']['maskErrors'])
				{
					$this->dberror = 'Unable to connect to database.';
				}

				trigger_error($this->dberror, E_USER_ERROR);
			}

			// Set encoding
			$this->conn->set_charset('utf8');
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			$this->conn = @mysql_connect($this->config['db']['server'], $this->config['db']['username'], $this->config['db']['password']);

			if (!$this->conn)
			{
				$this->dberror = mysql_error();

				// Mask any errors that may have happened
				if ($this->config['db']['maskErrors'])
				{
					$this->dberror = 'Unable to connect to database.';
				}

				trigger_error('Cannot connect to database.', E_USER_ERROR);
			}

			$select = @mysql_select_db($this->config['db']['database_name'], $this->conn);

			if (!$select)
			{
				$this->dberror = mysql_error();

				// Mask any errors that may have happened
				if ($this->config['db']['maskErrors'])
				{
					$this->dberror = 'Unable to select database.';
				}

				trigger_error('Cannot select database.', E_USER_ERROR);
			}
		}
	}


	/**
	* Execute a query
	*
	* @param	string			The MySQL query
	**/
	public function query($sql)
	{
		// Make sure we are connected
		if (!$this->conn)
		{
			trigger_error('No MySQL connection detected.', E_USER_ERROR);
		}

		$this->dberrno = null;
		$this->dberror = null;

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			// Execute query and return a mysqli_result object
			$this->result = $this->conn->query($sql);

			if ($this->conn->errno !== 0)
			{
				$this->dberrno = $this->conn->errno;
				$this->dberror = '('. $this->conn->connect_errno .') '  . $this->conn->error;
				$this->backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

				// When in dev mode, show the error on-screen
				if (defined('__TESTMODE__'))
				{
					echo '<pre>' . print_r($this->backtrace, true) . '</pre>';
					trigger_error('MySQL Error (' . $this->conn->errno . '): ' . $this->conn->error, E_USER_ERROR);
				}

				// E-mail an error report
				$this->dbreport($sql);

				// Mask any errors that may have happened
				if ($this->config['db']['maskErrors'])
				{
					$this->dberror = 'An error has occurred. MySQL reports: ' . $this->dberror;
				}

				return false;
			}

			$this->totalqueries++;

			// MySQLi query returns boolean unless there is data to return.
			if (gettype($this->result) == 'object')
			{
				$this->result->{"numrows"} = $this->result->num_rows;
				$this->result->{"affected_rows"} = $this->conn->affected_rows;
				$this->result->{"insert_id"} = $this->conn->insert_id;
				$this->result->{"iteration"} = 0;
			}
			elseif ($this->result === true)
			{
				// Delete and insert statements return a true boolean
				$this->result = new stdObject(array(
					'numrows' => 0,
					'affected_rows' => $this->conn->affected_rows,
					'insert_id' => $this->conn->insert_id,
					'iteration' => 0
				));
			}
			else
			{
				// Query returned a boolean, but no errors, that means all other stats are trivial or empty.
				$this->result = new stdObject(array(
					'numrows' => 0,
					'affected_rows' => 0,
					'insert_id' => 0,
					'iteration' => 0
				));
			}

			// Return the result just in case someone wants to reuse it later in the script
			return $this->result;
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			$this->result = @mysql_query($sql, $this->conn);

			if ($this->dberror = @mysql_error())
			{
				$this->backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

				// When in dev mode, show the error on-screen
				if (defined('__TESTMODE__'))
				{
					trigger_error('MySQL Error (0): ' . $this->dberror, E_USER_ERROR);
				}

				// Submit error
				$this->dbreport($sql);

				// Mask any errors that may have happened
				if ($this->config['db']['maskErrors'])
				{
					$this->dberror = 'An error has occurred. MySQL reports: ' . $this->dberror;
				}

				return false;
			}

			$this->numrows = @mysql_num_rows($this->result);
			$this->affected_rows = @mysql_affected_rows($this->conn);
			$this->insert_id = @mysql_insert_id($this->conn);

			$this->totalqueries++;

			// Return the result just in case someone wants to reuse it later in the script
			return $this->result;
		}
	}


	/**
	* Turn autocommit on and off
	*
	* @param	boolean			Set true to turn autocommit on and false to turn it off
	**/
	public function autocommit($boolean)
	{
		if (!$this->conn)
		{
			trigger_error('No database connection detected.', E_USER_ERROR);
			return;
		}

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			// Connect to the database and test each step
			$this->conn->autocommit($boolean);
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			trigger_error('The legacy MySQL API for PHP is deprecated and does not support autocommit or rollbacks.', E_USER_ERROR);
		}
	}


	/**
	* Commit changes in the transaction buffer
	**/
	public function commit()
	{
		if (!$this->conn)
		{
			trigger_error('No database connection detected.', E_USER_ERROR);
			return;
		}

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			// Connect to the database and test each step
			$this->conn->commit();
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			trigger_error('The legacy MySQL API for PHP is deprecated and does not support autocommit or rollbacks.', E_USER_ERROR);
		}
	}


	/*
	* Prepared statements and bindings
	****************************************************************************************************************

	/*
	* Issue a prepared statement. Use ? to indicates wild strings. Use the local bind method to populate those values.
	*
	* @param	string		The query to be executed
	*/
	public function prepare($sql, $bindings=null)
	{
		// Make sure we are connected
		if (!$this->conn)
		{
			trigger_error('No MySQL connection detected.', E_USER_ERROR);
		}

		// Make sure we have clear values
		$this->dberrno = null;
		$this->dberror = null;
		$this->stmt = null;
		$this->result = null;
		$this->bind_types = null;
		$this->bind_params = array();

		// Deprecated
		$this->numrows = null;
		$this->affected_rows = null;
		$this->insert_id = null;

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			if (!method_exists('mysqli_stmt', 'get_result'))
			{
				trigger_error('The mysqli_stmt::get_result is not available.', E_USER_ERROR);
				return false;
			}

			// The result will be a mysqli_stmt object
			$this->stmt = $this->conn->prepare($sql);

			if ($this->conn->errno !== 0)
			{
				$this->stmt = null;
				$this->dberrno = $this->conn->errno;
				$this->dberror = '('. $this->conn->connect_errno .')' . $this->conn->error;
				$this->backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

				// When in dev mode, show the error on-screen
				if (defined('__TESTMODE__'))
				{
					echo '<pre>' . print_r($this->backtrace, true) . '</pre>';
					trigger_error('MySQL Error (' . $this->conn->errno . '): ' . $this->conn->error, E_USER_ERROR);
				}
				else
				{
					trigger_error('MySQL Error (' . $this->conn->errno . '): ' . $this->conn->error . PHP_EOL . $this->backtrace, E_USER_ERROR);
				}

				// Submit error
				//$this->dbreport($sql);

				// Mask any errors that may have happened
				if ($this->config['db']['maskErrors'])
				{
					$this->dberror = 'An error has occurred while processing this request. Please, contact techical support.';
				}

				return false;
			}

			// Did the statement return false
			if ($this->stmt === false)
			{
				$this->stmt = null;
				$this->dberror = 'MySQLi::prepare returned false with the following query: ' . $sql;
				$this->backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				//$this->dbreport($sql);

				// When in dev mode, show the error on-screen
				if (defined('__TESTMODE__'))
				{
					trigger_error('MySQLi::prepare returned false with the following query: ' . $sql, E_USER_ERROR);
				}
				else
				{
					trigger_error('MySQL Error (' . $this->conn->errno . '): ' . $this->conn->error . PHP_EOL . $this->backtrace, E_USER_ERROR);
				}

				// Mask any errors that may have happened
				if ($this->config['db']['maskErrors'])
				{
					$this->dberror = 'An error has occurred while processing this request. Please, contact techical support.';
				}

				return false;
			}

			// Auto bind
			if ($bindings !== null && sizeof($bindings) > 0)
			{
				foreach($bindings AS $v)
				{
					$this->bind($v[0], $v[1]);
				}
			}

			return $this->stmt;
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			trigger_error('The legacy MySQL API for PHP is deprecated and does not support prepared statements.', E_USER_ERROR);
			return false;
		}
	}

	/**
	* Bind a prepared statement parameter.
	*
	* @param	string		A string that contains one or more characters which specify the types for the corresponding bind variables:
	*							i : integer
	*							d : double
	*							s : string
	*							b : blob
	* @param	string		The value being bound to the prepared statement
	**/
	public function bind($type, $param)
	{
		// Make sure we are connected
		if (!$this->conn)
		{
			trigger_error('No MySQL connection detected.', E_USER_ERROR);
		}

		if (!preg_match('/[idsb]+/', $type))
		{
			trigger_error('"' . $type . '" is not a valid statement binding type.', E_USER_ERROR);
		}

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			//$this->stmt->bind_param($type, $var1);
			$this->bind_types .= $type;
			$this->bind_params[] = utf8_encode($param);
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			trigger_error('The legacy MySQL plugin for PHP is deprecated and does not support prepared statements.', E_USER_ERROR);
		}
	}

	/**
	* Add a binding by reference.
	*
	* @param	string		A string that contains one or more characters which specify the types for the corresponding bind variables:
	*							i : integer
	*							d : double
	*							s : string
	*							b : blob
	* @param	array		The values being bound to the prepared statement. Multiple arguments are permitted here.
	**/
	public function bindRef($type, &$param)
	{
		if (!$this->conn)
		{
			trigger_error('No MySQL connection detected.', E_USER_ERROR);
		}

		if (!preg_match('/[idsb]+/', $type))
		{
			trigger_error('"' . $type . '" is not a valid statement binding type.', E_USER_ERROR);
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			trigger_error('The legacy MySQL plugin for PHP is deprecated and does not support prepared statements.', E_USER_ERROR);
		}

		$this->bind_types .= $type;
		$this->bind_params[] = &$param;
	}

	/**
	* Execute the current bindings.
	**/
	public function mbind_param_do()
	{
		// The params array will becoming the arguments in a Mysqli::bind_param call, so the bind types need to be
		// first in the array as it is the first argument.
		$params = $this->bind_params;
		array_unshift($params, $this->bind_types);

		// This line executes Mysqli::bind_param using the above params as the arguments
		return @call_user_func_array(array($this->stmt, 'bind_param'), $this->makeValuesReferenced($params));
	}

	/**
	* Convert the stored bindings into values by reference so the bind_parameter method in MySQLi will not complain.
	**/
	private function makeValuesReferenced($arr)
	{
		$refs = array();

		foreach($arr as $key => $value)
		{
			$refs[$key] = $value;
		}

		return $refs;
	}

	/**
	* Execute a prepared statement and return the result
	**/
	public function execute()
	{
		// Make sure we are connected
		if (!$this->conn)
		{
			trigger_error('No MySQL connection detected.', E_USER_ERROR);
		}

		// Did an error happen above?
		if ($this->dberror)
		{
			// We have an error
			return false;
		}

		// Make sure we have a statement to execute
		if (!$this->stmt)
		{
			trigger_error('Cannot execute a null statement.', E_USER_ERROR);
		}

		if (gettype($this->stmt) != 'object')
		{
			trigger_error('MySQLi execute expect a mysqli_stmt object. ' . gettype($this->stmt) . ' given.', E_USER_ERROR);
		}

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			if ($this->bind_types)
			{
				$this->mbind_param_do();
			}

			// Count this query
			$this->totalqueries++;

			if ($this->stmt->execute())
			{
				// Success
				$this->affected_rows = $this->stmt->affected_rows;
				$this->insert_id = $this->stmt->insert_id;

				// Set the mysqli_result object since we are done with bindings
				$this->result = $this->stmt->get_result();

				if ($this->result)
				{
					/**
					 * DEPRECATED!
					 **/
					$this->numrows = $this->result->num_rows;

					$this->result->{'numrows'} = $this->result->num_rows;
					$this->result->{'affected_rows'} = $this->affected_rows;
					$this->result->{'insert_id'} = $this->insert_id;
					$this->result->{'iteration'} = 0;

					// Iteraction tracking
					if ($this->result->num_rows == 0)
					{
						$this->result->{'last'} = true;
					}
				}
				else
				{
					// Query returned a boolean, but no errors, that means all other stats are trivial or empty.
					$this->result = new stdObject(array(
						'numrows' => 0,
						'affected_rows' => $this->affected_rows,
						'insert_id' => $this->insert_id,
						'foundrows' => 0,
						'iteration' => 0,
						'first' => true,
						'last' => false
					));
				}

				return $this->result;
			}
			else
			{
				// Execution failed; look for errors
				$this->dberrno = $this->stmt->errno;
				$this->dberror = '('. $this->stmt->errno .')'  . $this->stmt->error;
				$this->backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

				// When in dev mode, show the error on-screen
				if (defined('__TESTMODE__'))
				{
					echo '<pre>' . print_r($this->backtrace, true) . '</pre>' . PHP_EOL;
					trigger_error('MySQL Error (' . $this->conn->errno . '): ' . $this->conn->error, E_USER_ERROR);
				}
				else
				{
					trigger_error('MySQL Error (' . $this->conn->errno . '): ' . $this->conn->error . PHP_EOL . $this->backtrace, E_USER_ERROR);
				}

				// Submit error
				//$this->dbreport('[Prepared Statement]');

				// Mask any errors that may have happened
				if ($this->config['db']['maskErrors'])
				{
					$this->dberror = 'The query failed to execute.';
				}

				return false;
			}
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			trigger_error('The legacy MySQL plugin for PHP is deprecated and does not support prepared statements.', E_USER_ERROR);
		}
	}


	/**
	* Execute a loop reset. This may be needed if doing a bulk transaction. Between loop iterations, a reset of the
	* binding arrays is needed.
	**/
	public function loopReset()
	{
		$this->bind_types = null;
		$this->bind_params = array();
	}


	/**
	* Clear a statement and make sure it is closed. DEPRECATED!
	**/
	public function clear()
	{
		if ($this->stmt)
		{
			$this->stmt->close();
			$this->stmt = null;
		}

		$this->bind_types = null;
		$this->bind_params = array();
		$this->clauses = array();

		$this->result = null;
		$this->query = '';
	}


	/*
	* Handling returns and rows
	****************************************************************************************************************/

	/**
	* Iterate a fetch rows so any while loops taking place can work with either lib
	*
	* @param	resource		If this is not set, the most recent query is iterated. Otherwise, pass
	*							a resource from a previous query.
	* @param	string			Select a return type: ASSOC, NUM, or BOTH. (MySQL and MySQLi only!)
	*
	* @return	array			Return the next row in an array
	**/
	public function fetch(&$result='', $result_type='ASSOC')
	{
		// Make sure we are connected
		if (!$this->conn)
		{
			trigger_error('No MySQL connection detected.', E_USER_ERROR);
		}

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			if (empty($result))
			{
				// If the result argument is empty, use the internal result from the most recent query
				$result =& $this->result;
			}

			// If the result is not an object, something went wrong!
			if (gettype($result) != 'object')
			{
				return false;
			}

			// Check numrows
			if ($result->numrows == 0)
			{
				return array();
			}

			/*
			* MySQLi will do different things depending on the type of result we get
			*/

			$row = $result->fetch_array(constant('MYSQLI_' . strtoupper($result_type)));

			if ($row === false)
			{
				// If the fetch result is false, there are no more rows to return
				return false;
			}

			// Let's keep the iteration indicators in order
			$result->first = ($result->iteration == 0 ? true : false);

			$result->iteration++;

			if ($result->iteration == $result->numrows)
			{
				// We are on the last row
				$result->last = true;
			}

			// Return the fields
			return $row;
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			if ($result)
			{
				return @mysql_fetch_array($result, constant('MYSQLI_' . strtoupper($result_type)));
			}
			else
			{
				return @mysql_fetch_array($this->result, constant('MYSQLI_' . strtoupper($result_type)));
			}
		}
	}


	/**
	* This is a quick select function. Its purpose is to grab one row of data from the database
	* without having to invoke the fetch property in the parent script. It quickly dumps the
	* garbage.
	*
	* @param	string			The SQL query
	* @param	string			The object containing the results (i.e. numrows, affected_rows, insert_id)
	*
	* @return	array			Return the first row in an array
	**/
	public function fetchRow($sql="", &$result=null)
	{
		if (!empty($sql))
		{
			// Make sure the garbage is empty
			$this->clear();

			// Typical query
			$result = $this->query($sql);

			return $this->fetch();
		}

		// If we don't have a typical query, we're running a prepared statement from memory
		if ($this->stmt)
		{
			// The fetchRow method can be used before execute is called, so we will call it now.
			$this->result = $this->execute();

			// We may need this outside the object
			$row = array();
			$result = $this->result;

			if ($this->result->numrows > 0)
			{
				$row = $this->fetch($this->result);
			}

			// Empty everything
			$this->clear();

			return $row;
		}
	}


	/**
	* Set the result pointer to a selected row
	*
	* @param	resource		If this is not set, the most recent query is iterated. Otherwise, pass
	*							a resource from a previous query.
	* @param	integer			The zero-based position in the result to which to set the pointer
	*
	* @return	array			Return the next row in an array
	**/
	public function seek(&$result, $n)
	{
		// Make sure we are connected
		if (!$this->conn)
		{
			trigger_error('No MySQL connection detected.', E_USER_ERROR);
		}

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			if (!empty($result))
			{
				if (gettype($result) != 'object')
				{
					/*
					* MySQLi->query() Returns FALSE on failure. For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli_query() will return a
					* mysqli_result object. For other successful queries mysqli_query() will return TRUE.
					trigger_error('Cannot fetch a non-result object in MySQLi. "' . gettype($result) . '" provided".', E_USER_ERROR);
					*/
					return false;
				}

				return $result->data_seek($n);
			}
			else
			{
				return $this->result->data_seek($n);
			}
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			if ($result)
			{
				return @mysql_data_seek($result, $n);
			}
			else
			{
				return @mysql_data_seek($this->result, $n);
			}
		}
	}


	/**
	* Escape a string. This should be avoided if possible in favor of prepared statements which never need to be escaped.
	**/
	public function escape($string)
	{
		// If we are not already connected, just forget it.
		if (!$this->conn)
		{
			return;
		}

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			return $this->conn->real_escape_string($string);
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			return @mysql_real_escape_string($string, $this->conn);
		}
	}


	/**
	* Close the MySQL connection
	**/
	public function close()
	{
		// If we are not already connected, just forget it.
		if (!$this->conn)
		{
			return false;
		}

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			$this->conn->close();
			return false;
		}

		// Use the crappy default MySQL lib that comes with old versions of PHP
		if (DB_LIB == 'mysql')
		{
			@mysql_close($this->conn);
			return false;
		}
	}


	/**
	* Where clause conditionals
	******************************************************************************************************/

	/**
	* Simply add something to the where clause. No conditions or bindings are taking place here
	*
	* @param	string			The string to be added to the WHERE clause
	**/
	function addClause($string)
	{
		//$this->clauses[] = $string;
		$this->where[] = $string;
	}

	/**
	* Do a conditional binding onto a prepared statement
	*
	* @param	string			A string that contains one or more characters which specify the types for the corresponding bind variables:
	*								i : integer
	*								d : double
	*								s : string
	*								b : blob
	* @param	string			The value being bound to the prepared statement
	* @param	string			The bindings as it should appear in the WHERE clause
	* @param	boolean			Select true if empty values are to be ignored
	**/
	function bindClause($type, $value, $binding, $validate=true)
	{
		// Make sure we are connected
		if (!$this->conn)
		{
			trigger_error('No MySQL connection detected.', E_USER_ERROR);
		}

		if (!preg_match('/[idsb]+/', $type))
		{
			trigger_error('"' . $type . '" is not a valid statement binding type.', E_USER_ERROR);
		}

		if ($validate && (empty($value) || $value == '%' || $value == '%%'))
		{
			// No empty values
			return;
		}

		// Use the MySQLi lib
		if (DB_LIB == 'mysqli')
		{
			// Add the binding to the prepared statement
			$this->bind($type, $value);

			// Add the value to the where clause for later
			//$this->clauses[] = $binding;
			$this->where[] = $binding;
		}
	}

	/**
	* Add a field to field join to the where clause
	*
	* @param	string			The first field in the database
	* @param	string			The second field in the database
	**/
	function join($var, $value)
	{
		if (!empty($value) && !in_array($var . ' = ' . $value, $this->where))
		{
			$this->where[] = $var . ' = ' . $value;
		}
	}

	/**
	* Add an equal comparison to the where clause
	*
	* @param	string			The field in the database
	* @param	string			The value to be compared with the field value
	* @param	boolean			Select true if empty values are to be ignored
	**/
	function eq($var, $value, $option='')
	{
		$comparison = $var . ' = ' . (is_numeric($value) ? $value : "'" . $value . "'");

		if ($this->notEmpty($value, $option) && !in_array($comparison, $this->where))
		{
			$this->where[] = $comparison;
		}
	}

	/**
	* Add a not equal comparison to the where clause
	*
	* @param	string			The field in the database
	* @param	string			The value to be compared with the field value
	* @param	boolean			Select true if empty values are to be ignored
	**/
	function ne($var, $value, $option='')
	{
		if ($this->notEmpty($value, $option))
		{
			$this->where[] = $var . ' != ' . (is_numeric($value) ? $value : "'" . $value . "'");
		}
	}

	/**
	* Add a less than comparison to the where clause
	*
	* @param	string			The field in the database
	* @param	string			The value to be compared with the field value
	* @param	boolean			Select true if empty values are to be ignored
	**/
	function lt($var, $value, $option='')
	{
		$comparison = $var . ' < ' . $value;

		if ($this->notEmpty($value, $option) && !in_array($comparison, $this->where))
		{
			$this->where[] = $comparison;
		}
	}

	/**
	* Add a greater than comparison to the where clause
	*
	* @param	string			The field in the database
	* @param	string			The value to be compared with the field value
	* @param	boolean			Select true if empty values are to be ignored
	**/
	function gt($var, $value, $option='')
	{
		$comparison = $var . ' > ' . $value;

		if ($this->notEmpty($value, $option) && !in_array($comparison, $this->where))
		{
			$this->where[] = $comparison;
		}
	}

	/**
	* Add a greater than or equal to comparison to the where clause
	*
	* @param	string			The field in the database
	* @param	string			The value to be compared with the field value
	* @param	boolean			Select true if empty values are to be ignored
	**/
	function get($var, $value, $option='')
	{
		$comparison = $var . ' >= ' . $value;

		if ($this->notEmpty($value, $option) && !in_array($comparison, $this->where))
		{
			$this->where[] = $comparison;
		}
	}

	// Less than or equal to
	function let($var, $value, $option='')
	{
		$comparison = $var . ' <= ' . $value;

		if ($this->notEmpty($value, $option) && !in_array($comparison, $this->where))
		{
			$this->where[] = $comparison;
		}
	}

	/**
	* Add a like comparison to the where clause
	*
	* @param	string			The field in the database
	* @param	string			The value to be compared with the field value
	* @param	boolean			Select true if empty values are to be ignored
	**/
	function like($var, $value, $format=WILDCARDS, $option='')
	{
		if (!$this->notEmpty($value, $option))
		{
			return;
		}

		$comparison = sprintf("%s LIKE '$format'", $var, $value);

		if (!in_array($comparison, $this->where))
		{
			$this->where[] = $comparison;
		}
	}

	/**
	* Add an IN comparison to the where clause
	*
	* @param	string			The field in the database
	* @param	string			The value to be compared with the field value
	* @param	boolean			Select true if empty values are to be ignored
	**/
	function in($var, $value)
	{
		if (is_array($value))
		{
			$comparison = $var . ' IN' . "(" . implode(',', $value) . ")";
		}
		else
		{
			$comparison = $var . ' IN' . "(" . $value . ")";
		}

		if ((!empty($value) && !in_array($comparison, $this->where)))
		{
			$this->where[] = $comparison;
		}
	}

	// Empty the buffer
	function clause_clear()
	{
		$this->where = array();
		$this->where_result = '';
	}

	/**
	* Test for non-empty value with options
	*
	* @param	string			The string to test
	* @param	constant		Set behavior of nonEmpty method
	*
	* @return	boolean			True if non-empty; false otherwise
	**/
	public function notEmpty($str, $option='')
	{
		if (!empty($str))
		{
			// All standard non-empty values return true as normal
			return true;
		}

		##### Configurable exceptions #####

		// Allow empty strings
		if ($option == 'ALLOW_EMPTY_STRING' && $str === '')
		{
			// Empty strings are allowed if permitted
			return true;
		}

		// Allow zero
		if ($option == 'ALLOW_ZERO' && ($str === 0 || $str === '0'))
		{
			// Zero values need to be treated as non-empty
			return true;
		}

		// Null is always empty
		if ($str === null || $str === '')
		{
			return false;
		}

		return false;
	}

	// Build the where clause and prepared statement
	function build($delimit=' AND ')
	{
		if (sizeof($this->where) > 0)
		{
			$string = implode($delimit, $this->where);
			$this->where_result = $string;
		}
		else
		{
			$this->where_result = '';
		}

		if (!empty($smarty))
		{
			$smarty->assign('where', $this);
		}
	}


	// Populate a select menu with the provided query's results
	public function dynamic_selection($sql, $match='0')
	{
		global $App;

		$output = '';
		$result = $this->query($sql);

		if ($result->numrows == 0)
		{
			$output .= '<optgroup label=":: No Options ::"></optgroup>';
		}
		else
		{
			while($row = $this->fetch($result, 'NUM'))
			{
				if (is_array($match))
				{
					$output .= '<option value="' . $row[0] . '"' . (in_array($row[0], $match) ? ' selected' : '') . '>' . $row[1] . '</option>';
				}
				else
				{
					$output .= '<option value="' . $row[0] . '"' . ($row[0] == $match ? ' selected' : '') . '>' . $row[1] . '</option>';
				}
			}
		}

		return $output;
	}


	/**
	* Examine the server environment and print a report of what database functionality we have available.
	**/
	public function getDbEnvironment()
	{
		// Dynamic lib selection
		if (class_exists('mysqli'))
		{
			trigger_error('The MySQLi library is available.', E_USER_NOTICE);

			if (!method_exists('mysqli_stmt', 'get_result'))
			{
				trigger_error('Warning! The mysqli_stmt::get_result is not available.', E_USER_NOTICE);
			}
		}

		if (class_exists('PDO'))
		{
			trigger_error('The PDO library is available, but not yet supported by Maj_db.', E_USER_NOTICE);
		}

		if (function_exists('mysql_connect'))
		{
			// We are stuck with the crappy lib
			trigger_error('The MySQL API is available, but deprecated as of PHP 5.5.0 and removed in PHP 7.0', E_USER_NOTICE);
		}
	}

	/**
	* Report database errors to developers
	*
	* @param	string			The SQL query
	**/
	public function dbreport($sql)
	{
		if ($this->config['dberror']['report'] === false)
		{
			// We are not reporting errors to Bandwise
			return;
		}

		$arr = array(
			'sitename' => $_SERVER['HTTP_HOST'],
			'myname' => $this->config['dberror']['myname'],
			'myemail' => $this->config['dberror']['myemail'],
			'uri' => $_SERVER['REQUEST_URI'],
			'page' => $_SERVER['PHP_SELF'],
			'ip' => $_SERVER['REMOTE_ADDR'],
			'dberror' => $this->dberror,
			'sql' => trim($sql),
			'backtrace' => $this->backtrace
		);

		// We do not want to just post this content. Encode it.
		$post = array(
			'do' => 'dbError',
			'content' => base64_encode(json_encode($arr))
		);


		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->config['dberror']['url']);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// Execute
		$this->dberrorResponse = curl_exec($ch);

		if($this->dberrorResponse === false)
		{
			$this->dberrorResponse = curl_error($ch);
		}

		curl_close($ch);
	}
}


/*
* Create a standard function with properties.
*/
class stdObject
{
	public function __construct(array $arguments = array())
	{
		if (!empty($arguments))
		{
			foreach ($arguments as $property => $argument)
			{
				if ($argument instanceOf Closure)
				{
					$this->{$property} = $argument;
				}
				else
				{
					$this->{$property} = $argument;
				}
			}
		}
	}

	public function __call($method, $arguments)
	{
		if (isset($this->{$method}) && is_callable($this->{$method}))
		{
			return call_user_func_array($this->{$method}, $arguments);
		}
		else
		{
			throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
		}
	}
}


?>
