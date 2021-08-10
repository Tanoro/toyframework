<?php

namespace Pages;

class EnvironmentController extends \Controller
{
	var $slug = 'environment';
	var $output = array();

	public function __construct($config, $pathArray)
	{
		// This is an Ajax request
		parent::__construct($config, true);

		// Set page info
		$this->pageinfo = array(
			'slug' => $this->slug
		);

		if ($this->request->is('GET'))
		{
			// Ajax scripts return nothing for GET requests
			header('HTTP/1.0 404 Not Found');
		}

		// Make sure we have a cache directory
		if (!is_dir(__ROOT__ . '/' . $this->config['paths']['logs'] . '/crons'))
		{
			mkdir(__ROOT__ . '/' . $this->config['paths']['logs'] . '/crons', 0777, true);
		}

		if ($this->request->is('POST'))
		{
			$method = $_POST['do'];

			try {
				$this->preload();
				$this->{"$method"}();
				$this->ajaxReturn($this->output);
			}
			catch(Exception $e)
			{
				trigger_error($_POST['do'] . ' not found', E_USER_ERROR);
			}
		}
	}

	// Preload search filters
	public function preload()
	{
		/**
		* DRY Code note!
		* I got tired of the home page filtering being in a dozen places. Now, the global filters are in one place and the
		* following array lists the Ajax actions that need them. Additional filters exclusive to a specific action can be
		* added within that Ajax action's code below. The stores table is aliased as "s".
		**/
		$eventFilterActions = array(
			'setAllActive',
			'setAllInactive',
			'delAll',
			'setAllLocked',
			'setAllUnlocked'
		);

		if (in_array($_POST['do'], $eventFilterActions))
		{
			$this->DB->like('s.domain', $this->request->post('domain'));
			$this->DB->like('s.IP', $this->request->post('IP'), TRAIL_WILDCARD);
			$this->DB->like('s.hostname', $this->request->post('hostname'));
			$this->DB->like('s.content', $this->request->post('content'));
			$this->DB->eq('s.status', $this->request->post('status'));
			$this->DB->eq('s.result', $this->request->post('result'));
			$this->DB->eq('s.statusLock', $this->request->post('statusLock'));
			$this->DB->gt('s.statusScore', $this->request->post('scoreMin'), ALLOW_ZERO);
			$this->DB->lt('s.statusScore', $this->request->post('scoreMax'), ALLOW_ZERO);

			if ($this->DB->notEmpty($this->request->post('state')))
			{
				$this->DB->addClause("EXISTS (SELECT 1 FROM addresses a WHERE s.id = a.storeid AND a.state = '" . $Ajax->escape($_POST['state']) . "')");
			}
			
			if ($this->DB->notEmpty($this->request->post('company_name')))
			{
				$this->DB->addClause("EXISTS (SELECT 1 FROM addresses a WHERE s.id = a.storeid AND a.company_name = '" . $Ajax->escape($_POST['company_name']) . "')");
			}

			if ($this->DB->notEmpty($this->request->post('owner_email')))
			{
				$this->DB->addClause("EXISTS (SELECT 1 FROM addresses a WHERE s.id = a.storeid AND a.owner_email = '" . $Ajax->escape($_POST['owner_email']) . "')");
			}

			if ($this->DB->notEmpty($this->request->post('store_phone')))
			{
				$this->DB->addClause("EXISTS (SELECT 1 FROM addresses a WHERE s.id = a.storeid AND a.store_phone = '" . $Ajax->escape($_POST['store_phone']) . "')");
			}

			if ($this->DB->notEmpty($this->request->post('store_email')))
			{
				$this->DB->addClause("EXISTS (SELECT 1 FROM addresses a WHERE s.id = a.storeid AND a.store_email = '" . $Ajax->escape($_POST['store_email']) . "')");
			}

			if ($this->DB->notEmpty($this->request->post('manager_email')))
			{
				$this->DB->addClause("EXISTS (SELECT 1 FROM addresses a WHERE s.id = a.storeid AND a.manager_email = '" . $Ajax->escape($_POST['manager_email']) . "')");
			}

			if ($this->DB->notEmpty($this->request->post('regexDomain')))
			{
				$this->DB->addClause("s.domain REGEXP '" . $this->request->post('regexDomain') . "'");
			}
		}
	}

	// Set store record status
	public function setStatus()
	{
		$this->DB->prepare("UPDATE stores s SET s.status = ? WHERE s.id = ?", [
			[INTEGER, $_POST['status']],
			[INTEGER, $_POST['id']]
		]);
		$result = $this->DB->execute();
		//$this->ajaxReturn($output);
	}

	// Set all record status to active
	public function setAllActive()
	{
		$this->DB->build();

		$sql = "UPDATE stores s SET s.status = 1";

		if (!empty($this->DB->where_result))
		{
			$sql .= " WHERE " . $this->DB->where_result;
		}

		$this->DB->query($sql);
	}

	// Set all record status to inactive
	public function setAllInactive()
	{
		$this->DB->build();

		$sql = "UPDATE stores s SET s.status = 2";

		if (!empty($this->DB->where_result))
		{
			$sql .= " WHERE " . $this->DB->where_result;
		}

		$this->DB->query($sql);
	}

	// Delete selected store record
	public function delStore()
	{
		if (empty($_POST['id']))
		{
			$this->response['error'] = 'No record ID was selected.';
			$this->ajaxReturn($this->output);
		}

		if (is_numeric($_POST['id']) === false)
		{
			$this->response['error'] = 'No record ID was selected.';
			$this->ajaxReturn($this->output);
		}
		
		// Delete store
		$Store = new \Models\Store($this->config, $this->settings, $this->DB);
		$r = $Store->delete($_POST['id']);
		
		if ($r === false)
		{
			$this->response['error'] = $r->error;
			$this->ajaxReturn($this->output);
		}
	}
	
	// Delete all selected records
	public function delAll()
	{
		$this->DB->eq('s.statusLock', 1); // Unlocked only
		$this->DB->build();

		// Delete stores
		$sql = "SELECT id FROM stores s ";

		if (!empty($this->DB->where_result))
		{
			$sql .= "WHERE " . $this->DB->where_result;
		}

		$result = $this->DB->query($sql);

		if ($result->numrows == 0)
		{
			$this->response['error'] = 'No store records selected.';
			$this->ajaxReturn($this->output);
		}
		else
		{
			$Store = new \Models\Store($this->config, $this->settings, $this->DB);

			while($row = $this->DB->fetch($result))
			{
				$Store->delete($row['id']);
			}
		}
	}

	// Set status lock on single record
	public function setStatusLock()
	{
		$this->DB->prepare("UPDATE stores s SET s.statusLock = ? WHERE s.id = ?", [
			[INTEGER, $_POST['statusLock']],
			[INTEGER, $_POST['id']]
		]);
		$result = $this->DB->execute();
	}

	// Set all status lock
	public function setAllLocked()
	{
		$this->DB->build();

		$sql = "UPDATE stores s SET s.statusLock = 2";

		if (!empty($this->DB->where_result))
		{
			$sql .= " WHERE " . $this->DB->where_result;
		}

		//$this->response['sql'] = $sql;
		$this->DB->query($sql);
	}

	// Set all status to unlocked
	public function setAllUnlocked()
	{
		$this->DB->build();

		$sql = "UPDATE stores s SET s.statusLock = 1";

		if (!empty($this->DB->where_result))
		{
			$sql .= " WHERE " . $this->DB->where_result;
		}

		$this->DB->query($sql);
	}

	// Set the cron to run
	public function setRun()
	{
		// Make sure we have everything we need to run crons
		if (function_exists('exec') === false)
		{
			$this->response['error'] = 'PHP does not have exec permissions.';
			$this->ajaxReturn($this->output);
		}

		$disabled = explode(',', ini_get('disable_functions'));
		
		if (in_array('exec', $disabled) === true)
		{
			$this->response['error'] = 'The exec function is disabled in your php.ini file.';
			$this->ajaxReturn($this->output);
		}

		if (is_executable(__ROOT__ . '/bin/cron') === false)
		{
			$this->response['error'] = '/bin/cron is not executable. Check the file permissions.';
			$this->ajaxReturn($this->output);
		}
		
		// All good! Run the cron
		$runstatus = parse_ini_file(__ROOT__ . '/config/' . $this->config['scrapers']['iniPath']);

		// Do NOT add the element if it is not already in the INI file
		if (isset($runstatus[ $_POST['file'] ]))
		{
			$runstatus[ $_POST['file'] ] = '1|0|0';
			$runstatus['_writtenBy'] = 'environment';
			$runstatus['_writtenOn'] = date('Y-m-d G:i');

			$ini = '';

			foreach($runstatus AS $k => $v)
			{
				$ini .= $k . ' = "' . $v . '"' . PHP_EOL;
			}

			$fp = fopen(__ROOT__ . '/config/' . $this->config['scrapers']['iniPath'], 'w');
			fwrite($fp, trim($ini) . PHP_EOL);
			fclose($fp);

			exec(__ROOT__ . '/bin/cron ' . $_POST['file'] . ' > /dev/null &');
			//$this->response['cmd'] = __ROOT__ . '/bin/cron ' . $_POST['file'] . ' > /dev/null &';
		}
	}

	// Set the cron to stop
	public function setStop()
	{
		$runstatus = parse_ini_file(__ROOT__ . '/config/' . $this->config['scrapers']['iniPath']);

		// Do NOT add the element if it is not already in the INI file
		if (isset($runstatus[ $_POST['file'] ]))
		{
			$runstatus[ $_POST['file'] ] = '0|0|0';
			$runstatus['_writtenBy'] = 'environment';
			$runstatus['_writtenOn'] = date('Y-m-d G:i');

			$ini = '';

			foreach($runstatus AS $k => $v)
			{
				$ini .= $k . ' = "' . $v . '"' . PHP_EOL;
			}

			$fp = fopen(__ROOT__ . '/config/' . $this->config['scrapers']['iniPath'], 'w');
			fwrite($fp, trim($ini) . PHP_EOL);
			fclose($fp);
		}
	}

	// Send query to API.ai
	public function sendAPI()
	{
		$data = array(
			'v' => '20150821',
			'query' => $_POST['q'],
			'lang' => 'EN',
			'sessionId' => time()
		);
		$data_string = json_encode($data);

		$ch = curl_init('https://api.api.ai/v1/query');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $this->config['api.ai']['clientAccessToken']
			)
		);

		$result = curl_exec($ch);
		$resultArr = json_decode($result, true);
		$this->output['intent'] = $resultArr['result']['parameters'];
		$this->output['raw'] = print_r($resultArr, true);
	}

	public function getContent()
	{
		$this->fetchSettings();
		$this->getProfile();

		$this->DB->prepare("SELECT filename FROM storesContent WHERE storeid = ?", [
			[INTEGER, $_POST['id']]
		]);
		$result = $this->DB->execute();

		if ($result->numrows == 0)
		{
			$this->response['error'] = 'Record not found.';
			$this->ajaxReturn($this->output);
		}

		$pages = array();

		while($row = $this->DB->fetch($result))
		{
			$pages[] = htmlentities(file_get_contents(__ROOT__ . '/' . $row['filename']));
		}

		$this->output['content'] = implode(PHP_EOL . PHP_EOL . '|||' . PHP_EOL . PHP_EOL, $pages);
	}

	// Save search state
	public function saveSearch()
	{
		$this->DB->get('g.percent', $_POST['brandScoreMin']);
		$this->DB->let('g.percent', $_POST['brandScoreMax']);

		if (!empty($_POST['excludeCampaigns']))
		{
			$this->DB->addClause("s.id NOT IN(SELECT a1.storeid FROM campaignIndex i, addresses a1 WHERE i.aid = a1.id AND i.campaignid IN(" . $_POST['excludeCampaigns'] . ") GROUP BY a1.storeid)");
		}

		$this->DB->join('s.id', 'g.id');

		$this->DB->build();

		$sql = "SELECT s.id ";
		$sql .= "FROM stores s, (";
		$sql .= "	SELECT s.id, s.IP, s.hostname, s.domain, ROUND(COALESCE((t.n/g.n) * 100, 0)) percent ";
		$sql .= "	FROM stores s ";
		$sql .= "	LEFT JOIN (SELECT storeid, COUNT(id) n FROM storeBrands GROUP BY storeid) g ON (s.id = g.storeid) ";
		$sql .= "	LEFT JOIN (SELECT sb.storeid, COUNT(sb.id) n FROM storeBrands sb, brands b WHERE sb.brandid = b.id AND b.isImported = 1 GROUP BY sb.storeid) t ON (s.id = t.storeid)";
		$sql .= ") g ";

		if (!empty($this->DB->where_result))
		{
			$sql .= "WHERE " . $this->DB->where_result . " ";
		}

		$sql .= "ORDER BY s.id ASC";

		$result = $this->DB->query($sql);

		if ($result->numrows != 0)
		{
			$array = array();

			while($row = $this->DB->fetch($result))
			{
				$array[] = $row['id'];
			}

			$this->output['n'] = count($array);

			$fp = fopen(__ROOT__ . '/' . $this->config['savesearch'], 'w');
			fwrite($fp, json_encode($array));
			fclose($fp);
		}
	}


	/*
	* Add/Edit Addresses
	*/

	public function getAddress()
	{
		$this->DB->prepare("SELECT company_name, address1, address2, city, state, zip, store_phone, store_fax_num, contact_name, store_email, owner_email, manager_email FROM addresses WHERE id = ?", [
			[INTEGER, $_POST['id']]
		]);
		$result = $this->DB->execute();

		if ($result->numrows == 0)
		{
			$this->response['error'] = 'Address not found.';
			$this->ajaxReturn($this->output);
		}

		$this->output['row'] = $this->DB->fetch($result);
	}

	// Save new address
	public function addAddress()
	{
		$UTC = new DateTimeZone('UTC');
		$UTCTime = new DateTime('now', $UTC);
		
		$sql = "INSERT INTO addresses (storeid, company_name, address1, city, state, zip, standardAddress, store_phone, store_fax_num, contact_name, store_email, owner_email, manager_email, dateadded, source) ";
		$sql .= "VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		$this->DB->prepare($sql, [
			[INTEGER, $_POST['storeid']],
			[STRING, $_POST['company_name']],
			[STRING, $_POST['address1']],
			[STRING, $_POST['city']],
			[STRING, $_POST['state']],
			[STRING, $_POST['zip']],
			[STRING, $this->standardizeAddress($_POST['address1'])],
			[STRING, $_POST['store_phone']],
			[STRING, $_POST['store_fax_num']],
			[STRING, $_POST['contact_name']],
			[STRING, $_POST['store_email']],
			[STRING, $_POST['owner_email']],
			[STRING, $_POST['manager_email']],
			[STRING, $UTCTime->format('Y-m-d H:i:s')],
			[STRING, 'Manual']
		]);
		$r = $this->DB->execute();

		if (empty($r->insert_id))
		{
			$this->response['error'] = 'An error occurred while inserting the address record.';
			$this->ajaxReturn($this->output);
		}

		$this->output['insert_id'] = $r->insert_id;
		$this->output['dateStr'] = date('M j, Y');
	}

	// Save address for editing
	public function saveAddress()
	{
		$this->DB->prepare("UPDATE addresses SET company_name = ?, address1 = ?, city = ?, state = ?, zip = ?, standardAddress = ?, store_phone = ?, store_fax_num = ?, contact_name = ?, store_email = ?, owner_email = ?, manager_email = ? WHERE id = ?", [
			[STRING, $this->request->post('company_name')],
			[STRING, $this->request->post('address1')],
			[STRING, $this->request->post('city')],
			[STRING, $this->request->post('state')],
			[STRING, $this->request->post('zip')],
			[STRING, $this->standardizeAddress($this->request->post('address1'))],
			[STRING, $this->request->post('store_phone')],
			[STRING, $this->request->post('store_fax_num')],
			[STRING, $this->request->post('contact_name')],
			[STRING, $this->request->post('store_email')],
			[STRING, $this->request->post('owner_email')],
			[STRING, $this->request->post('manager_email')],
			[INTEGER, $this->request->post('id')]
		]);
		$this->DB->execute();
	}

	// Save a single field of an address
	public function saveField()
	{
		$field = $this->request->post('field');

		$this->DB->prepare("UPDATE addresses SET $field = ? WHERE id = ?", [
			[STRING, $this->request->post('value')],
			[INTEGER, $this->request->post('id')]
		]);
		$this->DB->execute();
	}

	// Delete address record
	public function delAddress($id=null)
	{
		$id = ($id != null ? $id : $_POST['id']);

		$this->DB->prepare("DELETE FROM addresses WHERE id = ?", [
			[INTEGER, $id]
		]);
		$this->DB->execute();
	}

	// Merge address records from a source ID into a destination ID; delete the source record
	public function mergeAddresses()
	{
		// Get source record
		$this->DB->prepare("SELECT company_name, address1, address2, city, state, zip, store_phone, store_phone_num, store_fax_num, contact_name, store_email, owner_email, manager_email FROM addresses WHERE id = ?", [
			[INTEGER, $this->request->post('sourceID')]
		]);
		$source = $this->DB->fetchRow(false, $result);

		if ($result->numrows == 0)
		{
			$this->response['error'] = 'Source address record not found.';
			$this->ajaxReturn($this->output);
		}

		// Delete empty array elements from source
		$source = array_filter($source);

		// Get destination record
		$this->DB->prepare("SELECT company_name, address1, address2, city, state, zip, store_phone, store_phone_num, store_fax_num, contact_name, store_email, owner_email, manager_email FROM addresses WHERE id = ?", [
			[INTEGER, $this->request->post('destID')]
		]);
		$dest = $this->DB->fetchRow(false, $result);

		if ($result->numrows == 0)
		{
			$this->response['error'] = 'Destination address record not found.';
			$this->ajaxReturn($this->output);
		}

		// Merge arrays; Source array overwrites destination in cases of conflict
		$resultArray = array_merge($dest, $source);

		$this->DB->prepare("UPDATE addresses SET company_name = ?, address1 = ?, city = ?, state = ?, zip = ?, standardAddress = ?, store_phone = ?, store_fax_num = ?, contact_name = ?, store_email = ?, owner_email = ?, manager_email = ? WHERE id = ?", [
			[STRING, $resultArray['company_name']],
			[STRING, $resultArray['address1']],
			[STRING, $resultArray['city']],
			[STRING, $resultArray['state']],
			[STRING, $resultArray['zip']],
			[STRING, $this->standardizeAddress($resultArray['address1'])],
			[STRING, $resultArray['store_phone']],
			[STRING, $resultArray['store_fax_num']],
			[STRING, $resultArray['contact_name']],
			[STRING, $resultArray['store_email']],
			[STRING, $resultArray['owner_email']],
			[STRING, $resultArray['manager_email']],
			[INTEGER, $_POST['destID']]
		]);
		$this->DB->execute();

		// Delete source record
		$this->delAddress($_POST['sourceID']);

		// Format the phone number for display
		$strippedPhone = preg_replace('/[^0-9]/', '', $resultArray['store_phone']);
		$resultArray['store_phone'] = sprintf('(%s) %s-%s', substr($strippedPhone, 0, 3), substr($strippedPhone, 3, 3), substr($strippedPhone, 6, 4));

		$this->output['result'] = $resultArray;
	}

	/*
	* Add/Edit Providers
	*/

	public function getProvider()
	{
		$this->DB->prepare("SELECT id, ipaddress, name, url, notes FROM providers WHERE id = ?", [
			[INTEGER, $this->request->post('id')]
		]);
		$result = $this->DB->execute();

		if ($result->numrows == 0)
		{
			$this->response['error'] = 'Provider not found.';
			$this->ajaxReturn($this->output);
		}

		$this->output['row'] = $this->DB->fetch($result);
	}

	// Save record for editing
	public function saveProvider()
	{
		$this->DB->prepare("UPDATE providers SET ipaddress = ?, name = ?, url = ?, notes = ? WHERE id = ?", [
			[STRING, $this->request->post('ipaddress')],
			[STRING, $this->request->post('name')],
			[STRING, $this->request->post('url')],
			[STRING, $this->request->post('notes')],
			[INTEGER, $this->request->post('id')]
		]);
		$this->DB->execute();
	}

	// Delete provider record
	public function delProvider()
	{
		$this->DB->prepare("DELETE FROM providers WHERE id = ?", [
			[INTEGER, $this->request->post('id')]
		]);
		$this->DB->execute();
	}

	/*
	* System settings
	******************/

	// Add setting
	public function addSetting()
	{
		$this->DB->prepare("INSERT INTO settings (setting, descrip, hook, data) VALUES (?,?,?,?)", [
			[STRING, $this->request->post('setting')],
			[STRING, $this->request->post('descrip')],
			[STRING, $this->request->post('hook')],
			[STRING, $this->request->post('data')]
		]);
		$this->DB->execute();
	}

	// Update a setting
	public function saveSettings()
	{
		$this->DB->prepare("UPDATE settings SET data = ? WHERE hook = ?", [
			[STRING, $this->request->post('data')],
			[STRING, $this->request->post('hook')]
		]);
		$this->DB->execute();
	}


	/*
	* Database backups
	*******************/

	public function restoreBackup()
	{
		$this->fetchSettings();
		$this->getProfile();

		$schema = __ROOT__ . '/db/schema.sql';
		$file = __ROOT__ . '/db/' . $this->profile['hook'] . '/' . $_POST['file'];
		$ext = strtolower(substr($_POST['file'], strrpos($_POST['file'], '.') + 1, strlen($_POST['file'])));

		// Load schema file
		exec("mysql -u " . $this->config['db']['username'] . " --password='" . $this->config['db']['password'] . "' --host='" . $this->config['db']['server'] . "' " . $this->config['db']['database_name'] . " < " . $schema);

		// Load data
		if ($ext == 'gz')
		{
			// Restore a compressed backup file
			$command = "gunzip < " . $file . " | mysql -u " . $this->config['db']['username'] . " --password='" . $this->config['db']['password'] . "' --host='" . $this->config['db']['server'] . "' " . $this->config['db']['database_name'];
		}
		else
		{
			// Restore an uncompressed SQL file
			$command = "mysql -u " . $this->config['db']['username'] . " --password='" . $this->config['db']['password'] . "' --host='" . $this->config['db']['server'] . "' " . $this->config['db']['database_name'] . " < " . $file;
		}

		exec($command);

		// Switch backup file for the active profile
		$this->profile['backup'] = basename($_POST['file']);

		$ini = '';

		foreach($this->profile AS $k => $v)
		{
			if (is_numeric($v))
			{
				$ini .= $k . ' = ' . $v . PHP_EOL;
			}
			else
			{
				$ini .= $k . ' = "' . $v . '"' . PHP_EOL;
			}
		}

		$fp = fopen(__ROOT__ . '/' . $this->config['paths']['profiles'] . '/' . $this->settings['activeProfile'], 'w');
		fwrite($fp, trim($ini));
		fclose($fp);
	}

	// Delete backup
	public function delBackup()
	{
		$this->fetchSettings();
		$this->getProfile();

		unlink(__ROOT__ . '/db/' . $this->profile['hook'] . '/' . $_POST['file']);
	}


	/*
	* Cron Logs
	************/

	public function delCronlog()
	{
		$this->getProfile();

		unlink(__ROOT__ . '/logs/' . $this->profile['hook'] . '/' . $_POST['file']);
	}

	// Return the log file that reports fatal errors related to cron activity
	// The cron command must include the following: 2> /home/<path>/DOCUMENT_ROOT/crons/cronErrors.txt
	public function getCronError()
	{
		if (!file_exists(__ROOT__ . '/crons/cronErrors.txt'))
		{
			$this->output['content'] = null;
			return false;
		}
		
		$this->output['content'] = file_get_contents(__ROOT__ . '/crons/cronErrors.txt');
	}


	/*
	* Manual Scraping
	******************/

	// Manually scrape a domain by store table record ID
	public function scrapeSite()
	{
		$UTC = new DateTimeZone('UTC');
		$UTCTime = new DateTime('now', $UTC);
		
		$this->fetchSettings();

		$this->DB->prepare("SELECT id, domain, status FROM stores WHERE id = ?", [
			[INTEGER, $_POST['id']]
		]);
		$row = $this->DB->fetchRow(false, $result);

		if ($result->numrows == 0)
		{
			$this->response['error'] = 'The site you selected cannot be scraped.';
			$this->ajaxReturn($this->output);
		}
		else
		{
			// Set a pagehit counter to be returned later
			$this->output['pagesNum'] = 0;

			$additionalPages = array();

			// Set content checks
			$navTargets = explode('|', $this->settings['navTargets']);
			$whiteChecks = explode('|', $this->settings['status_white_checks']);
			$blackChecks = explode('|', $this->settings['status_black_checks']);

			// Counters
			$whitePoints = 0;
			$blackPoints = 0;

			// Default status is inactive
			$status = 2;

			// Initiate the DOM lib
			$dom = new \DOMDocument();

			/*
			* Get Content
			**************/

			$url = 'https://' . $row['domain'] . '/';

			$Scraper = new \Scraper($this->config, __FILE__);
			$Scraper->pagehitSleep = 3; // This is a manual, so keep the sleep short
			$content = $Scraper->scrapePage($url);

			//$http_code = $Scraper->curlinfo['http_code'];

			if ($content === false)
			{
				// An error happened while hitting Google search
				$this->response['error'] = $Scraper->error;

				// Fail this domain
				$this->DB->prepare("UPDATE stores SET status = 2, statusScore = 0, result = 2 WHERE id = ?", [
					[INTEGER, $row['id']]
				]);
				$this->DB->execute();

				$this->ajaxReturn($this->output);
			}

			$this->output['pagesNum'] = 1;

			// Add store content for this page hit
			$Scraper->updateStoreContent($row['id'], $url, $content);
			
			// Tally the white/black checks
			$Scraper->checkCounter($whiteChecks, $content, $whitePoints);
			$Scraper->checkCounter($blackChecks, $content, $blackPoints);

			libxml_use_internal_errors(false);
			libxml_use_internal_errors(true);
			$dom->loadHTML($content);
			libxml_clear_errors();

			$xpath = new \DOMXpath($dom);

			// Begin navigating the site
			foreach($navTargets AS $target)
			{
				$addLinks = $xpath->query("//a[contains(text(),'" . $target . "')]/@href");
				
				if ($addLinks->length != 0)
				{
					$addPages = array();

					foreach($addLinks AS $links)
					{
						// Check domain
						$host = parse_url($links->nodeValue, PHP_URL_HOST);

						if (!empty($host) && $host != $row['domain'])
						{
							// Link is not on this domain
							continue;
						}

						$addPages[] = 'https://' . $row['domain'] . '/' . basename($links->nodeValue);
					}

					// We only want the first two unique items in this array for later
					$addPages = array_slice(array_unique($addPages), 0, 2);
					$additionalPages = array_merge($additionalPages, $addPages);
				}
			}

			// Count the number of pages hit
			$this->output['pagesNum'] += sizeof($additionalPages);

			if (sizeof($additionalPages) != 0)
			{
				foreach($additionalPages AS $k => $page)
				{
					$pageContent = $Scraper->scrapePage($page);

					if ($pageContent)
					{
						// Add store content for this page hit
						$Scraper->updateStoreContent($row['id'], $page, $pageContent);
						
						// Calculated score from content
						$Scraper->checkCounter($whiteChecks, $pageContent, $whitePoints);
						$Scraper->checkCounter($blackChecks, $pageContent, $blackPoints);
					}
				}
			}

			// Update status
			if (($whitePoints - $blackPoints) > 0)
			{
				$status = 1;
			}

			if ($row['statusLock'] == 2)
			{
				// The status is locked
				$status = $row['status'];
			}

			// Update status
			if ($this->settings['force_scrape'] == 1)
			{
				$this->DB->prepare("UPDATE stores SET status = ?, whiteScore = ?, blackScore = ?, statusScore = ?, scrapeDate = ?, result = 1 WHERE id = ?", [
					[INTEGER, $status],
					[INTEGER, $whitePoints],
					[INTEGER, $blackPoints],
					[INTEGER, ($whitePoints - $blackPoints)],
					[STRING, $UTCTime->format('Y-m-d H:i:s')],
					[INTEGER, $row['id']]
				]);
				$this->DB->execute();
			}
			else
			{
				// We did not scrape; rescore only
				$this->DB->prepare("UPDATE stores SET status = ?, whiteScore = ?, blackScore = ?, statusScore = ? WHERE id = ?", [
					[INTEGER, $status],
					[INTEGER, $whitePoints],
					[INTEGER, $blackPoints],
					[INTEGER, ($whitePoints - $blackPoints)],
					[INTEGER, $row['id']]
				]);
				$this->DB->execute();
			}

			$this->output['whiteScore'] = $whitePoints;
			$this->output['blackScore'] = $blackPoints;
			$this->output['statusScore'] = ($whitePoints - $blackPoints);
		}
	}

	// Get the domain from the store table to be put in an iframe
	public function captchaSite()
	{
		$this->fetchSettings();

		$this->DB->prepare("SELECT id, domain, status FROM stores WHERE id = ?", [
			[INTEGER, $_POST['id']]
		]);
		$row = $this->DB->fetchRow(false, $result);

		if ($result->numrows == 0)
		{
			$this->response['error'] = 'The site you selected cannot be scraped.';
			$this->ajaxReturn($this->output);
		}
		else
		{
			$parts = parse_url('http://' . $row['domain']);
			$domain = str_replace('www.', '', $parts['host']);
			$this->output['url'] = 'https://www.' . $domain . '/';
		}
	}

	public function addressPhoneScrape()
	{
		// Load cURL options
		$options = array(
			CURLOPT_RETURNTRANSFER	=> true,     // return web page
			CURLOPT_HEADER			=> false,    // don't return headers
			CURLOPT_FOLLOWLOCATION	=> true,     // follow redirects
			CURLOPT_ENCODING		=> '',       // handle all encodings
			CURLOPT_AUTOREFERER		=> true,     // set referer on redirect
			CURLOPT_CONNECTTIMEOUT	=> 120,      // timeout on connect
			CURLOPT_TIMEOUT			=> 120,      // timeout on response
			CURLOPT_MAXREDIRS		=> 10,       // stop after 10 redirects
			CURLOPT_COOKIEFILE		=> __ROOT__ . '/temp/cookie.txt',
			CURLOPT_COOKIEJAR		=> __ROOT__ . '/temp/cookie.txt',
			CURLOPT_USERAGENT		=> 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3',
			CURLOPT_REFERER			=> 'http://www.google.com/'//,
			//CURLOPT_VERBOSE			=> true
		);

		$ch = curl_init($_POST['url']);
		curl_setopt_array($ch, $options);

		$content = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($http_code != 200)
		{
			// An error happened while hitting Google search
			$this->response['error'] = 'cURL return HTTP ' . $http_code;
			$this->ajaxReturn($this->output);
		}

		$phoneNumbers = array();
		$emailAddresses = array();
		$companyName = array();
		$mailingAddresses = array();
		$dupeControl = array();
		$insertCount = 0;

		// Extract content
		$this->getTelephoneEmail($content, $phoneNumbers, $emailAddresses, $companyName, $mailingAddresses, $dupeControl);

		if (count($phoneNumbers) > 0 || count($emailAddresses) > 0)
		{
			// Get the phone numbers and e-mail addresses we already have for this store so we can compare against duplicates
			$this->DB->prepare("SELECT store_phone_num, store_email FROM addresses WHERE storeid = ?", [
				[INTEGER, $_POST['id']]
			]);
			$i = $this->DB->execute();

			$existingPhones = array();
			$existingEmails = array();

			if ($i->numrows != 0)
			{
				while($r = $this->DB->fetch($i))
				{
					$existingPhones[] = $r['store_phone_num'];
					$existingEmails[] = $r['store_email'];
				}
			}

			// Merge together the new data arrays
			$inserts = array();

			if (count($emailAddresses) > count($phoneNumbers))
			{
				foreach($emailAddresses AS $k => $v)
				{
					$inserts[$k] = array(
						'phone' => $phoneNumbers[$k],
						'email' => $emailAddresses[$k],
						'name' => $companyName[$k]
					);
				}
			}
			else
			{
				foreach($phoneNumbers AS $k => $v)
				{
					$inserts[$k] = array(
						'phone' => $phoneNumbers[$k],
						'email' => $emailAddresses[$k],
						'name' => $companyName[$k]
					);
				}
			}

			// Insert the new rows
			foreach($inserts AS $v)
			{
				$strippedPhone = substr(preg_replace('/[^0-9]/', '', $v['phone']), -10);

				if (in_array($strippedPhone, $existingPhones))
				{
					// We have this phone number already
					continue;
				}

				if (in_array($v['email'], $existingEmails))
				{
					$v['email'] = '';
				}

				if (empty($v['phone']) && empty($v['email']))
				{
					// We don't want any empty records that may be in the array
					continue;
				}

				$this->DB->prepare("INSERT IGNORE INTO addresses (storeid, company_name, store_phone, store_email, dateadded, source) VALUES (?,?,?,?,?,?)", [
					[INTEGER, $_POST['id']],
					[STRING, $v['name']],
					[STRING, $v['phone']],
					[STRING, $v['email']],
					[INTEGER, time()],
					[STRING, basename(__FILE__)]
				]);
				$this->DB->execute();

				$insertCount++;

				if (!empty($v['phone']))
				{
					$existingPhones[] = $strippedPhone;
				}

				if (!empty($v['email']))
				{
					$existingEmails[] = $v['email'];
				}
			}
		}

		// Iterate mailing addresses and insert them
		if (count($mailingAddresses) != 0)
		{
			foreach($mailingAddresses as $a)
			{
				$standard = $this->standardizeAddress($a['address1']);

				$this->DB->prepare("INSERT INTO addresses (storeid, address1, address2, city, state, zip, standardAddress, dateadded, source) VALUES (?,?,?,?,?,?,?,?,?)", [
					[INTEGER, $_POST['id']],
					[STRING, $a['address1']],
					[STRING, $a['address2']],
					[STRING, $a['city']],
					[STRING, $a['state']],
					[STRING, $a['zip']],
					[STRING, $standard],
					[INTEGER, time()],
					[STRING, basename(__FILE__)]
				]);
				$this->DB->execute();

				$insertCount++;
			}
		}

		$this->output['insertCount'] = $insertCount;
	}


	/*
	* Data Management
	******************/

	// Dedupe script
	public function runDedupe()
	{
		if ($_POST['backup'])
		{
			$newfile = __ROOT__ . '/db/' . date('Y-m-d_Hi') . '.sql.gz';

			$command = "mysqldump ";
			$command .= "-u " . $this->config['db']['username'] . " ";
			$command .= "--password='" . $this->config['db']['password'] . "' ";
			$command .= "--host='" . $this->config['db']['server'] . "' ";
			$command .= "--routines ";
			$command .= "--triggers ";
			$command .= $this->config['db']['database_name'] . " | gzip > $newfile";
			exec($command);
		}

		$file = __ROOT__ . '/temp/dedupe_1.sql';

		$command = "mysql ";
		$command .= "-u " . $this->config['db']['username'] . " ";
		$command .= "--password='" . $this->config['db']['password'] . "' ";
		$command .= "--host='" . $this->config['db']['server'] . "' ";
		$command .= $this->config['db']['database_name'] . " < $file";
		exec($command, $this->output);
	}

	// Standardize addresses
	public function standardizeAddr()
	{
		$result = $this->DB->query("SELECT id, address1 FROM addresses WHERE address1 != '' AND standardAddress = '' ORDER BY id ASC");

		if ($result->numrows != 0)
		{
			while($row = $this->DB->fetch($result))
			{
				$stripped = $this->standardizeAddress($row['address1']);

				$this->DB->prepare("UPDATE addresses SET standardAddress = ? WHERE id = ?", [
					[STRING, $stripped],
					[INTEGER, $row['id']]
				]);
				$this->DB->execute();
			}
		}
	}

	// Remove the empty
	public function removeEmptyRows()
	{
		$sql = "DELETE a ";
		$sql .= "FROM addresses a, (";
		$sql .= "	SELECT a.id, a.storeid, a.address1 ";
		$sql .= "	FROM addresses a ";
		$sql .= "	WHERE a.standardAddress = '' ";
		$sql .= "	AND a.company_name = '' ";
		$sql .= "	AND a.store_phone_num = 0 ";
		$sql .= "	AND a.contact_name = '' ";
		$sql .= "	ORDER BY a.storeid ASC ";
		$sql .= "	LIMIT 2000";
		$sql .= ") b ";
		$sql .= "WHERE a.id = b.id";
		$result = $this->DB->query($sql);

		$this->response['affected_rows'] = $result->affected_rows;
	}

	// Remove the duplicate address records
	public function removeDupeAddresses()
	{
		$sql = "DELETE a ";
		$sql .= "FROM addresses a, (";
		$sql .= "	SELECT a.id, a.storeid, a.address1 ";
		$sql .= "	FROM addresses a, addresses b ";
		$sql .= "	WHERE a.storeid = b.storeid ";
		$sql .= "	AND a.standardAddress = b.standardAddress ";
		$sql .= "	AND a.standardAddress != '' ";
		$sql .= "	AND (a.company_name = '' OR a.company_name = b.company_name) ";
		$sql .= "	AND (a.store_phone_num = 0 OR a.store_phone_num = b.store_phone_num) ";
		$sql .= "	AND (a.contact_name = '' OR a.contact_name = b.contact_name) ";
		$sql .= "	AND a.id > b.id ";
		$sql .= "	ORDER BY a.storeid ASC ";
		$sql .= "	LIMIT 2000";
		$sql .= ") b ";
		$sql .= "WHERE a.id = b.id";
		$result = $this->DB->query($sql);

		$this->response['affected_rows'] = $result->affected_rows;
	}

	// Delete the duplicate phone numbers
	public function removeDupePhone()
	{
		$sql = "DELETE a ";
		$sql .= "FROM addresses a, (";
		$sql .= "	SELECT a.id, a.storeid, a.address1 ";
		$sql .= "	FROM addresses a, addresses b ";
		$sql .= "	WHERE a.storeid = b.storeid ";
		$sql .= "	AND a.store_phone_num = b.store_phone_num ";
		$sql .= "	AND a.store_phone_num != 0 ";
		$sql .= "	AND (a.standardAddress != '' OR a.standardAddress = b.standardAddress) ";
		$sql .= "	AND (a.company_name = '' OR a.company_name = b.company_name) ";
		$sql .= "	AND (a.contact_name = '' OR a.contact_name = b.contact_name) ";
		$sql .= "	AND a.id > b.id ";
		$sql .= "	ORDER BY a.storeid ASC ";
		$sql .= "	LIMIT 2000";
		$sql .= ") b ";
		$sql .= "WHERE a.id = b.id";
		$result = $this->DB->query($sql);

		$this->response['affected_rows'] = $result->affected_rows;
	}


	// Switch to another profile
	public function switchProfile()
	{
		$this->fetchSettings();

		// Get profile information
		$schema = __ROOT__ . '/db/schema.sql';
		$profile = parse_ini_file(__ROOT__ . '/' . $this->config['paths']['profiles'] . '/' . $_POST['profile']);

		// This profile has no backup
		if (empty($profile['backup']))
		{
			$this->response['error'] = 'This job has no recent database backup.';
			$this->ajaxReturn($this->output);
		}

		// The database backup is missing
		if (!is_file(__ROOT__ . '/db/' . $profile['hook'] . '/' . $profile['backup']))
		{
			$this->response['error'] = 'This database backup could not be found.';
			$this->ajaxReturn($this->output);
		}

		// Restore most recent backup associated with the selected profile
		$file = __ROOT__ . '/db/' . $profile['hook'] . '/' . $profile['backup'];
		$ext = strtolower(substr($profile['backup'], strrpos($profile['backup'], '.') + 1, strlen($profile['backup'])));

		// Load schema file
		exec("mysql -u " . $this->config['db']['username'] . " --password='" . $this->config['db']['password'] . "' --host='" . $this->config['db']['server'] . "' " . $this->config['db']['database_name'] . " < " . $schema);

		// Load data backup
		if ($ext == 'gz')
		{
			// Restore a compressed backup file
			$command = "gunzip < " . $file . " | mysql -u " . $this->config['db']['username'] . " --password='" . $this->config['db']['password'] . "' --host='" . $this->config['db']['server'] . "' " . $this->config['db']['database_name'];

			$this->response['com'] = $command;
		}
		else
		{
			// Restore an uncompressed SQL file
			$command = "mysql -u " . $this->config['db']['username'] . " --password='" . $this->config['db']['password'] . "' --host='" . $this->config['db']['server'] . "' " . $this->config['db']['database_name'] . " < " . $file;
		}

		exec($command);
	}

	// Delete a profile
	public function delProfile()
	{
		$archiveDir = __ROOT__ . '/' . $this->config['paths']['archives'] . '/' . $_POST['hook'];
		$backupDir = __ROOT__ . '/' . $this->config['paths']['backups'] . '/' . $_POST['hook'];
		$logsDir = __ROOT__ . '/' . $this->config['paths']['logs'] . '/' . $_POST['hook'];
		$iniFile = __ROOT__ . '/' . $this->config['paths']['profiles'] . '/' . $_POST['hook'] . '.ini';

		unlink($iniFile);

		exec("rm -rf " . $archiveDir);
		exec("rm -rf " . $backupDir);
		exec("rm -rf " . $logsDir);
	}


	/*
	* Scrape Tool
	**************/

	// Execute a scrape
	public function scrapeUrl()
	{
		// This scraper will not be logging
		$this->config['logging'] = false;

		// Load Scraper object
		$Scraper = new \Scraper($this->config, __FILE__);
		$Scraper->pagehitSleep = 3;

		// The scraper opened a database connection we won't need; close it
		$Scraper->DB->close();

		// Scrape
		$content = $Scraper->scrapePage($_POST['url']);

		// Read cURL info
		if (count($Scraper->curlinfo) != 0)
		{
			$this->output['curlinfo'] .= nl2br($Scraper->curlinfo['request_header']);
			/*
			foreach($Scraper->curlinfo AS $k => $v)
			{
				if ($k == 'request_header')
				{
					$this->output['curlinfo'] .= '[' . $k . '] => ' . nl2br($v) . '<br>';
					continue;
				}

				$this->output['curlinfo'] .= '[' . $k . '] => ' . $v . '<br>';
			}
			*/
		}

		// Handle errors
		if (count($Scraper->error) != 0)
		{
			$this->response['error'] = $Scraper->error;
			$this->ajaxReturn($this->output);
		}

		$this->output['cacheFile'] = $Scraper->cacheFile;

		$content = nl2br(htmlentities($content));
		$this->output['source'] = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $content);
	}

	// Highlight an Xpath query
	public function readXpath()
	{
		if (!isset($_POST['cacheFile']) || empty($_POST['cacheFile']))
		{
			$this->response['error'] = 'The cache file is missing.';
			$this->ajaxReturn($this->output);
		}

		if (!isset($_POST['xpath']) || empty($_POST['xpath']))
		{
			$this->response['error'] = 'You must enter an Xpath query.';
			$this->ajaxReturn($this->output);
		}

		// Load the DOM
		$dom = new \DOMDocument('1.0', 'UTF-8');
		//$dom->formatOutput=true;
		//$dom->preserveWhiteSpace=true;


		//libxml_use_internal_errors(true);
		$dom->loadHTMLFile($_POST['cacheFile'], LIBXML_HTML_NODEFDTD);
		//libxml_clear_errors();

		$xpath = new \DOMXpath($dom);
		$tags = $xpath->query($_POST['xpath']);

		if ($tags->length == 0)
		{
			// Return the content as is
			$content = file_get_contents($_POST['cacheFile']);
			$this->output['failed'] = 1;
			$content = nl2br(htmlentities($content));
			$this->output['source'] = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $content);
		}
		else
		{
			// Iterate through the located tags and insert a marker before and after them
			foreach($tags AS $tag)
			{
				$start = $dom->createTextNode('__STARTTAG__');
				$end = $dom->createTextNode('__ENDTAG__');
				$tag->parentNode->insertBefore($start, $tag);
				$tag->parentNode->insertBefore($end, $tag->nextSibling);
			}

			// Convent the HTML entities
			$content = nl2br(htmlentities($dom->saveHTML()));

			// Replace the tags with bold
			$search = ['__STARTTAG__','__ENDTAG__',"\t"];
			$replace = ['<strong>','</strong>','&nbsp;&nbsp;&nbsp;&nbsp;'];
			$this->output['source'] = str_replace($search, $replace, $content);
		}
	}


	/*
	* Brands
	*********/

	// Add brand
	public function addBrand()
	{
		if (empty($this->request->post('addBrandTxt')))
		{
			$this->response['error'] = 'You must enter a brand name.';
			$this->ajaxReturn($this->output);
		}
		
		$this->DB->prepare("INSERT INTO brands (name, isImported) VALUES (?,?)", [
			[STRING, $this->request->post('addBrandTxt')],
			[INTEGER, 0]
		]);
		$this->DB->execute();
	}

	// Get brand record
	public function getBrand()
	{
		$this->DB->prepare("SELECT name FROM brands WHERE id = ?", [
			[INTEGER, $this->request->post('id')]
		]);
		$this->output = $this->DB->fetchRow(false, $result);
	}

	// Edit brand
	public function editBrand()
	{
		$this->DB->prepare("UPDATE brands SET name = ? WHERE id = ?", [
			[STRING, $this->request->post('name')],
			[INTEGER, $this->request->post('id')]
		]);
		$this->DB->execute();
	}

	// Delete brand
	public function delBrand()
	{
		$this->DB->prepare("DELETE FROM brands WHERE id = ?", [
			[INTEGER, $this->request->post('id')]
		]);
		$this->DB->execute();
	}

	// Toggle brand import status
	public function toggleImport()
	{
		$this->DB->prepare("SELECT isImported FROM brands WHERE id = ?", [
			[INTEGER, $this->request->post('id')]
		]);
		$row = $this->DB->fetchRow(false, $result);

		if ($row['isImported'] == 1)
		{
			// Set brand to not imported
			$this->DB->prepare("UPDATE brands SET isImported = 0 WHERE id = ?", [
				[INTEGER, $this->request->post('id')]
			]);
			$this->DB->execute();

			$this->output['src'] = '/images/import-grey.png';
		}
		else
		{
			// Set brand to imported
			$this->DB->prepare("UPDATE brands SET isImported = 1 WHERE id = ?", [
				[INTEGER, $this->request->post('id')]
			]);
			$this->DB->execute();

			$this->output['src'] = '/images/import.png';
		}
	}


	/*
	* Geolocation
	**************/

	// Get the 10 closest stores to selected coordinates
	public function getStores()
	{
		$this->DB->join('s.id', 'a.storeid');
		$this->DB->addClause("a.geolat IS NOT NULL");

		if ($_POST['brand'])
		{
			$this->DB->addClause("EXISTS (SELECT 1 FROM storeBrands sb WHERE s.id = sb.storeid AND sb.brandid = " . $_POST['brand'] . ")");
		}

		$this->DB->build();

		$sql = "SELECT s.domain, a.id, a.dateadded, a.storeid, a.company_name, a.address1, a.city, a.state, a.zip, ";
		$sql .= "a.geolat lat, a.geolong lng, VINCENTY(a.geolat, a.geolong, ?, ?) vincenty ";
		$sql .= "FROM addresses a, stores s ";
		$sql .= "WHERE " . $this->DB->where_result . " ";
		$sql .= "ORDER BY vincenty ASC ";
		$sql .= "LIMIT 20";
		$this->DB->prepare($sql, [
			[DOUBLE, $this->request->post('lat')],
			[DOUBLE, $this->request->post('lon')]
		]);
		$result = $this->DB->execute();

		if ($result->numrows != 0)
		{
			while($row = $this->DB->fetch($result))
			{
				$row['fdate'] = date('M j, Y', $row['dateadded']);
				$this->output['stores'][] = $row;
			}
		}
	}

	/*
	* Functions for page processing and keyword extraction
	*******************************************************/

	// Strip tags, replacing with white space of a character of choice
	function replace_tags(&$html, $replace=' ')
	{
		if (get_class($html) == 'DOMDocument')
		{
			$string = $html->saveHTML();
		}

		if (get_class($html) == 'DOMElement')
		{
			$string = $html->C14N();
		}

		if (empty($string))
		{
			// HTML is probably just a string
			$string = $html;
		}

		$event =
			"onafterprint|onbeforeprint|onbeforeunload|onerror|onhaschange|onload|onmessage|".
			"onoffline|ononline|onpagehide|onpageshow|onpopstate|onresize|onstorage|onunload|".
			"onblur|onchange|oncontextmenu|onfocus|oninput|oninvalid|onreset|onselect|onsubmit|".
			"onkeydown|onkeypress|onkeyup|onclick|ondblclick|ondrag|ondragend|ondragenter|".
			"ondragleave|ondragover|ondragstart|ondrop|onmousedown|onmouseenter|onmousemove|".
			"onmouseleave|onmouseout|onmouseover|onmouseup|onscroll|onabort|oncanplay|".
			"oncanplaythrough|oncuechange|ondurationchange|onemptied|onended|onerror|".
			"onloadeddata|onloadedmetadata|onloadstart|onpause|onplay|onplaying|onprogress|".
			"onratechange|onseeked|onseeking|onstalled|onsuspend|ontimeupdate|onvolumechange|".
			"onwaiting|data-[^=]+";

		// Strip problematic tag attributes
		$string = preg_replace("/<([^>]+)(" . $event . ")=(\"|')(?:(?!\\3).)+\\3/", "<$1", $string);

		// Return stripped content
		return trim(preg_replace("/<[^>]*>/", $replace, $string));
	}

	/**
	* Extract telephone numbers and e-mail addresses from HTML content
	*
	* @param    string		The HTML content
	* @param    array		The phoneNumbers array
	* @param    array		The emailAddresses array
	* @param    array		The companyName array
	* @param	array		The parent array of mailing addresses already found
	* @param	array		The array of standard addresses for duplication control
	**/
	function getTelephoneEmail($html, &$phoneNumbers, &$emailAddresses, &$companyName, &$mailingAddresses, &$dupeControl)
	{
		// Initiate the DOM lib
		$dom = new \DOMDocument();

		// This function extracts data via three methods. The first success sets $do to false so the followup attempts are skipped
		$do = true;

		// Process content
		libxml_use_internal_errors(false);
		libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_clear_errors();

		$xpath = new \DOMXpath($dom);

		// Get phone numbers
		$schemaTags = $xpath->query("//span[@itemprop='telephone']/text()");

		// Do we have schema
		if ($schemaTags->length != 0)
		{
			// If the schema tags are there, we'll commit to them
			$do = false;

			// Iterate script tags
			foreach($schemaTags AS $phone)
			{
				// Insert phone numbers
				if (!in_array($phone->nodeValue, $phoneNumbers))
				{
					$phoneNumbers[] = $phone->nodeValue;
				}
			}

			$emailTags = $xpath->query("//span[@itemprop='email']/text()");

			if ($emailTags->length != 0)
			{
				foreach($emailTags AS $email)
				{
					if (!in_array($email->nodeValue, $emailAddresses))
					{
						$emailAddresses[]['email'] = $email->nodeValue;
					}
				}
			}

			// The schema is the only approach that can potentially extract the company name
			$nameTags = $xpath->query("//span[@itemprop='name']/text()");

			if ($nameTags->length != 0)
			{
				foreach($nameTags AS $name)
				{
					if (!in_array($name->nodeValue, $companyName))
					{
						$companyName[] = $name->nodeValue;
					}
				}
			}

			// Check for mailing address in schema
			$addressTags = $xpath->query("//*[@itemprop='address']");

			if ($addressTags->length != 0)
			{
				foreach($addressTags AS $tag)
				{
					$array = array();
					$array['address1'] = $xpath->query(".//*[@itemprop='streetAddress']/text()", $tag)[0]->nodeValue;
					$array['city'] = $xpath->query(".//*[@itemprop='addressLocality']/text()", $tag)[0]->nodeValue;
					$array['state'] = $xpath->query(".//*[@itemprop='addressRegion']/text()", $tag)[0]->nodeValue;
					$array['zip'] = $xpath->query(".//*[@itemprop='postalCode']/text()", $tag)[0]->nodeValue;

					$this->response['addresses'][] = $array['address1'];

					$standardAddress = $this->standardizeAddress($array['address1']);

					// Duplication check!
					if (!in_array($standardAddress, $dupeControl))
					{
						$dupeControl[] = $standardAddress;
						$mailingAddresses[] = $array;
					}
				}
			}
		}

		// Fallback #1: If the schema failed, check for tel and mailto
		if ($do)
		{
			// Fallback #1: Get tel/mailto tags
			$telLinks = $xpath->query("//a[contains(@href,'tel:')]/@href");

			if ($telLinks->length != 0)
			{
				// If the tel tags are there, we'll commit to them
				$do = false;

				foreach ($telLinks AS $phone)
				{
					if (!in_array(substr($phone->nodeValue, 4), $phoneNumbers))
					{
						// Substring so we don't capture the "tel:" in the tag
						$phoneNumbers[] = substr($phone->nodeValue, 4);
					}
				}
			}

			// Try to get e-mail addresses
			$mailtoLinks = $xpath->query("//a[contains(@href,'mailto:')]/@href");

			if ($mailtoLinks->length != 0)
			{
				foreach ($mailtoLinks AS $email)
				{
					$parsedAddress = parse_url($email->nodeValue);

					if (!in_array($parsedAddress['path'], $emailAddresses))
					{
						$emailAddresses[] = $parsedAddress['path'];
					}
				}
			}

			// Scrape for mailing address
			$this->extractAddresses($html, $mailingAddresses, $dupeControl);
		}

		// Fallback #2: Resort to Regex
		if ($do)
		{
			// Get phone numbers
			//preg_match_all('/[0-9]?[\s\-\.]?\(?[0-9]{3}\)?[\s\-\.]{1}[0-9]{3}[\s\-\.]{1}[0-9]{4}/', $html, $matches);
			preg_match_all('/([0-9][\s\-\.])?\(?[0-9]{3}\)?[\s\-\.]?[0-9]{3}[\s\-\.]{1}[0-9]{4}/', $html, $matches);

			if (count($matches[0]) != 0)
			{
				foreach($matches[0] AS $v)
				{
					if (!in_array($v, $phoneNumbers))
					{
						$phoneNumbers[] = trim($v);
					}
				}
			}

			// Get e-mails
			preg_match_all('/([a-zA-Z0-9\._\-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/', $html, $matches);

			if (count($matches[0]) != 0)
			{
				foreach($matches[0] AS $v)
				{
					if (!in_array($v, $emailAddresses))
					{
						$emailAddresses[] = trim($v);
					}
				}
			}

			// Scrape for mailing address
			$this->extractAddresses($html, $mailingAddresses, $dupeControl);
		}
	}

	/**
	* Standardize a U.S. mailing address
	*
	* @param	string			The U.S. mailing address
	*
	* @return	string			The standardized version of the address
	**/
	function standardizeAddress($str)
	{
		if (empty($str))
		{
			return $str;
		}

		// Address replacements
		$search = array(
			'/\s+/',
			// Cardinal directions
			'/\-n\.?(\-|\z)/',
			'/\-s\.?(\-|\z)/',
			'/\-e\.?(\-|\z)/',
			'/\-w\.?(\-|\z)/',
			'/\-nw\.?(\-|\z)/',
			'/\-ne\.?(\-|\z)/',
			'/\-sw\.?(\-|\z)/',
			'/\-se\.?(\-|\z)/',
			// Street types
			'/\-hwy\.?(\-|\z)/',
			'/\-aly\.?(\-|\z)/',
			'/\-ave\.?(\-|\z)/',
			'/\-blvd\.?(\-|\z)/',
			'/\-cswy\.?(\-|\z)/',
			'/\-cir\.?(\-|\z)/',
			'/\-ct\.?(\-|\z)/',
			'/\-cv\.?(\-|\z)/',
			'/\-dr\.?(\-|\z)/',
			'/\-hwy\.?(\-|\z)/',
			'/\-ln\.?(\-|\z)/',
			'/\-pkwy\.?(\-|\z)/',
			'/\-pl\.?(\-|\z)/',
			'/\-rd\.?(\-|\z)/',
			'/\-sq\.?(\-|\z)/',
			'/\-st\.?(\-|\z)/',
			'/\-ter\.?(\-|\z)/',
			'/\-way\.?(\-|\z)/',
			// Non-alphanumeric characters
			'/[^A-Za-z0-9\-]/'
		);
		$replace = array(
			'-',
			// Cardinal directions
			'-north$1',
			'-south$1',
			'-east$1',
			'-west$1',
			'-northwest$1',
			'-northeast$1',
			'-southwest$1',
			'-southeast$1',
			// Street types
			'-highway$1',
			'-alley$1',
			'-avenue$1',
			'-boulevard$1',
			'-causeway$1',
			'-circle$1',
			'-court$1',
			'-cove$1',
			'-drive$1',
			'-highway$1',
			'-lane$1',
			'-parkway$1',
			'-place$1',
			'-road$1',
			'-square$1',
			'-street$1',
			'-terrace$1',
			'-ways$1',
			// Non-alphanumeric characters
			''
		);

		return preg_replace($search, $replace, strtolower(trim($str)));
	}

	/**
	* Extract mailing addresses from a DOM object
	*
	* @param	string			The HTML content string
	* @param	array			The parent array of mailing addresses already found
	* @param	array			The array of standard addresses for duplication control
	*
	* @return	mixed			The accumulated mailing addresses
	**/
	function extractAddresses($content, &$mailingAddresses, &$dupeControl)
	{
		$regexCityStateZip = "/([A-Z][a-zA-Z '\-]+),\s+([A-Z]{2})\s+([0-9]{5})/";
		// This pattern may include a trailing space
		$regexMailingAddress = "/[0-9]+[A-Z\-]* +(?=.*[a-zA-Z0-9]+)[a-zA-Z0-9\. ]+(?=\W\s*)/";
		$regexPOBox = "/[Pp]\.?[Oo]\.? +[Bb][Oo][Xx] +[0-9]+(?=\W\s*)/";
		$regexLine2 = "/(apt|lot|room|suite|unit)? +#?[0-9]+/i";

		// Initiate the DOM lib
		$dom = new \DOMDocument();

		// Test content
		libxml_use_internal_errors(false);
		libxml_use_internal_errors(true);
		$dom->loadHTML($content);
		libxml_clear_errors();

		// Strip content we don't want
		$this->domStripTags($dom, 'script');
		$this->domStripTags($dom, 'style');

		$xpath = new \DOMXpath($dom);

		$path = "//*[text()[";
		$path .= "contains(.,', AL') or ";
		$path .= "contains(.,', AK') or ";
		$path .= "contains(.,', AZ') or ";
		$path .= "contains(.,', AR') or ";
		$path .= "contains(.,', CA') or ";
		$path .= "contains(.,', CO') or ";
		$path .= "contains(.,', CT') or ";
		$path .= "contains(.,', DE') or ";
		$path .= "contains(.,', FL') or ";
		$path .= "contains(.,', GA') or ";
		$path .= "contains(.,', HI') or ";
		$path .= "contains(.,', ID') or ";
		$path .= "contains(.,', IL') or ";
		$path .= "contains(.,', IN') or ";
		$path .= "contains(.,', IA') or ";
		$path .= "contains(.,', KS') or ";
		$path .= "contains(.,', KY') or ";
		$path .= "contains(.,', LA') or ";
		$path .= "contains(.,', ME') or ";
		$path .= "contains(.,', MD') or ";
		$path .= "contains(.,', MA') or ";
		$path .= "contains(.,', MI') or ";
		$path .= "contains(.,', MN') or ";
		$path .= "contains(.,', MS') or ";
		$path .= "contains(.,', MO') or ";
		$path .= "contains(.,', MT') or ";
		$path .= "contains(.,', NE') or ";
		$path .= "contains(.,', NV') or ";
		$path .= "contains(.,', NH') or ";
		$path .= "contains(.,', NJ') or ";
		$path .= "contains(.,', NM') or ";
		$path .= "contains(.,', NY') or ";
		$path .= "contains(.,', NC') or ";
		$path .= "contains(.,', ND') or ";
		$path .= "contains(.,', OH') or ";
		$path .= "contains(.,', OK') or ";
		$path .= "contains(.,', OR') or ";
		$path .= "contains(.,', PA') or ";
		$path .= "contains(.,', RI') or ";
		$path .= "contains(.,', SC') or ";
		$path .= "contains(.,', SD') or ";
		$path .= "contains(.,', TN') or ";
		$path .= "contains(.,', TX') or ";
		$path .= "contains(.,', UT') or ";
		$path .= "contains(.,', VT') or ";
		$path .= "contains(.,', VA') or ";
		$path .= "contains(.,', WA') or ";
		$path .= "contains(.,', WV') or ";
		$path .= "contains(.,', WI') or ";
		$path .= "contains(.,', WY')";
		$path .= "]]/..";
		# //*[text()[contains(.,', LA')]/parent::*]
		$stateContainer = $xpath->query($path);

		$this->response['addresses'] = $stateContainer->length;

		if ($stateContainer->length == 0)
		{
			// No addresses detected
			return;
		}
		else
		{
			foreach($stateContainer as $text)
			{
				// Strip the parent container, leaving only the string content
				$container = html_entity_decode($this->replace_tags($text, "\n")) . PHP_EOL;
				$array = array();

				$this->response['stateContainer'][] = $container;

				// Get city, state, zip
				preg_match($regexCityStateZip, $container, $cszArray, PREG_OFFSET_CAPTURE);

				if ($cszArray)
				{
					$array['city'] = trim($cszArray[1][0]);
					$array['state'] = trim($cszArray[2][0]);
					$array['zip'] = trim($cszArray[3][0]);

					// ##### We need to verify the city before we go further! ##### //
					$this->DB->prepare("SELECT CASE WHEN acceptable_cities = '' THEN LCASE(primary_city) ELSE LCASE(CONCAT(primary_city, ',', acceptable_cities)) END cities FROM zip_codes WHERE zip = ?", [
						[STRING, $array['zip']]
					]);
					$citiesArr = $this->DB->fetchRow(false, $result);

					if ($result->numrows == 0)
					{
						// This zip code is not in our database! Unable to verify city, so skip this address
						continue;
					}
					else
					{
						// Is the city in the array?
						$cities = explode(',', $citiesArr['cities']);

						if (!in_array(strtolower($array['city']), $cities))
						{
							// The city is not in our database
							continue;
						}
					}

					// All good! Rip matching string from the sample string. Cut off after the zip code
					$container = substr_replace(substr($container, 0, $cszArray[3][1] + strlen($cszArray[3][0])), '', $cszArray[0][1], strlen($cszArray[0][0]));
				}

				// Get mailing address
				$r = preg_match($regexMailingAddress, $container, $address1Arr, PREG_OFFSET_CAPTURE);

				if (!$r)
				{
					// No mailing adderss? Maybe a P.O. box
					preg_match($regexPOBox, $container, $address1Arr, PREG_OFFSET_CAPTURE);
				}

				// Get mailing address
				if ($address1Arr)
				{
					$array['address1'] = trim($address1Arr[0][0]);
					$standardAddress = $this->standardizeAddress($array['address1']);

					// Duplication check!
					if (!in_array($standardAddress, $dupeControl))
					{
						$dupeControl[] = $standardAddress;
					}
					else
					{
						// Duplicate address!
						continue;
					}

					// Rip matching string from the sample
					$container = substr_replace($container, '', $address1Arr[0][1], strlen($address1Arr[0][0]));
				}
				else
				{
					// We found no mailing address of any kind
					continue;
				}

				// If we detected a standard address, try to detect line 2
				if ($r)
				{
					// Address line 2 will always be at or after the location address1 was before we stripped it
					preg_match($regexLine2, $container, $line2Array, PREG_OFFSET_CAPTURE, $address1Arr[0][1]);

					if ($line2Array)
					{
						$array['address2'] = trim($line2Array[0][0]);
					}
				}

				// Piece it all together
				if (sizeof($array))
				{
					array_walk($array, 'trim');
					ksort($array);

					$mailingAddresses[] = $array;
				}
			}
		}
	}

	/**
	* Strip all instances of a tag from a DOMDocument object
	*
	* @param	object			The DOMDocument object
	* @param	string			The HTML tag to be stripped from the DOM
	**/
	function domStripTags(&$dom, $tag)
	{
		$list = $dom->getElementsByTagName($tag);

		while ($list->length > 0)
		{
			$p = $list->item(0);
			$p->parentNode->removeChild($p);
		}
	}

	/*
	* Execute Phinx migrations
	* Reference: https://github.com/cakephp/phinx/issues/548#issuecomment-436885830
	*/
	function phinxMigrate()
	{
		$app = new \Phinx\Console\PhinxApplication();
		$wrap = new \Phinx\Wrapper\TextWrapper($app);

		$wrap->setOption('configuration', __ROOT__ . '/phinx.yml');
		$wrap->getMigrate(__ENVIRONMENT__);

		return $wrap->getExitCode() === 0;
	}
}

?>
