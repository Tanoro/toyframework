<?php

namespace Models;

class Store
{
	// Basic settings
	public $config = array();
	public $settings = array();

	// Dependencies
	public $DB = null;
	var $record = array();

	// Pass errors
	public $error = null;

	private $tldsArray = array(); // DEPRECATED!

	/**
	* @param	array			The application configs array
	* @param	array			The application settings array
	* @param	object			The application DB connection resource
	**/
	public function __construct(array $config, array $settings, $DB)
	{
		$this->config = $config;
		$this->settings = $settings;

		// Create a new DB object, but use the same DB connection
		$this->DB = $DB;
	}

	// Get a store record
	public function get($id, $return_object=false)
	{
		$this->DB->prepare("SELECT * FROM stores WHERE id = ?");
		$this->DB->bind('i', $id);

		$result = $this->DB->execute();

		if ($result->numrows == 0)
		{
			$this->error = 'The store record selected is missing.';
			return false;
		}

		$row = $this->DB->fetch($result);

		if ($return_object === false)
		{
			return $row;
		}

		$obj = $this->create('https://' . $row['domain']);
		$obj->new = false;

		// Populate the object
		foreach($row AS $k => $v)
		{
			$obj->$k = $v;
		}
		
		return $obj;
	}
	
	// Create a user object
	public function create($url)
	{
		$obj = new \stdClass();
		$URL = new \URL;

		$obj->id = 0;
		$obj->status = 2; // Inactive by default
		$obj->whiteScore = 0;
		$obj->blackScore = 0;
		$obj->statusScore = 0;
		$obj->scrapeDate = 0;
		$obj->domain = null;
		$obj->IP = null;
		$obj->network = null;
		$obj->hostname = null;
		$obj->num_locations = 0;
		$obj->num_brands = 0;
		$obj->num_employees = 0;
		$obj->num_google_hits = 0;
		$obj->result = 0;
		$obj->statusLock = 0;

		// Supplementary properties
		$obj->new = true; // Indicates this is a new record
		$obj->url = $url;
		$obj->sld = null;
		$obj->tld = null;

		// Parse domain
		$parsed_domain = $URL->parse($obj->url);

		if ($parsed_domain !== false)
		{
			$obj->sld = $parsed_domain['sld'];
			$obj->tld = $parsed_domain['tld'];
		}

		// The base domain is only the SLD and TLD
		$domain = $parsed_domain['host'];
		$ip = gethostbyname($domain);

		$obj->hostname = ($ip != $domain ? gethostbyaddr($ip) : null);
		$obj->IP = ($ip == $domain ? null : $ip);
		$obj->domain = $domain;

		if ($obj->hostname !== null)
		{
			$obj->network = substr($ip, 0, strrpos($ip, '.'));
		}
		
		return $obj;
	}

	public function validate($obj)
	{
		// if (empty($obj->url))
		// {
		// 	$this->error = 'The URL to the store website is necessary.';
		// 	return false;
		// }

		if (empty($obj->domain))
		{
			$this->error = 'The domain to the store appears invalid: "' . $obj->domain . '"';
			return false;
		}

		if ($obj->new === true)
		{
			// For new records only, check for duplication
			$this->DB->prepare("SELECT COUNT(*) n FROM stores WHERE domain = ?");
			$this->DB->bind('s', $obj->domain);

			$result = $this->DB->execute();
			$row = $this->DB->fetch($result);

			if ($row['n'] != 0)
			{
				$this->error = 'This domain already exists';
				return false;
			}
		}

		if (empty($obj->sld) === false)
		{
			if (!empty($this->settings['domainBlacklist']))
			{
				$arr = explode('|', $this->settings['domainBlacklist']);

				foreach($arr AS $string)
				{
					if (strpos($obj->sld, $string) !== false)
					{
						$this->error = 'This domain SLD "' . $obj->sld . '" is blacklisted under the term "' . $string . '"';
						return false;
					}
				}
			}
			
			// We ignore domain SLDs that are too short
			if (!empty($this->settings['domainBlacklistLength']) && strlen($obj->sld) <= $this->settings['domainBlacklistLength'])
			{
				$this->error = 'This domain "' . $obj->domain . '" is too short';
				return false;
			}
		}
		else
		{
			// We don't have the SLD of the domain, so just use the full hostname
			// Domain blacklist checks
			if (!empty($this->settings['domainBlacklist']))
			{
				$arr = explode('|', $this->settings['domainBlacklist']);

				foreach($arr AS $string)
				{
					if (strpos($obj->domain, $string) !== false)
					{
						$this->error = 'This domain "' . $obj->domain . '" is blacklisted under the term "' . $string . '"';
						return false;
					}
				}
			}
		}

		return true;
	}

	public function insert(&$obj)
	{
		
		$sql = "INSERT INTO stores (status, whiteScore, blackScore, statusScore, scrapeDate, domain, IP, network, hostname, num_locations, num_brands, num_employees, num_google_hits, result, statusLock) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		$this->DB->prepare($sql);

		$this->DB->bind('i', $obj->status);
		$this->DB->bind('i', $obj->whiteScore);
		$this->DB->bind('i', $obj->blackScore);
		$this->DB->bind('i', $obj->statusScore);
		$this->DB->bind('i', $obj->scrapeDate);
		$this->DB->bind('s', $obj->domain);
		$this->DB->bind('s', $obj->IP);
		$this->DB->bind('s', $obj->network);
		$this->DB->bind('s', $obj->hostname);
		$this->DB->bind('i', $obj->num_locations);
		$this->DB->bind('i', $obj->num_brands);
		$this->DB->bind('i', $obj->num_employees);
		$this->DB->bind('i', $obj->num_google_hits);
		$this->DB->bind('i', $obj->result);
		$this->DB->bind('i', $obj->statusLock);

		$result = $this->DB->execute();

		if ($result->insert_id == 0)
		{
			$this->error = 'An error occurred while inserting the store record.';
			return false;
		}

		return $result->insert_id;
	}

	public function edit(&$obj)
	{
		$sql = "UPDATE stores ";
		$sql .= "SET status = ?, whiteScore = ?, blackScore = ?, statusScore = ?, scrapeDate = ?, domain = ?, IP = ?, network = ?, hostname = ?, ";
		$sql .= "num_locations = ?, num_brands = ?, num_employees = ?, num_google_hits = ?, result = ?, statusLock = ? ";
		$sql .= "WHERE id = ?";
		$this->DB->prepare($sql);

		$this->DB->bind('i', $obj->status);
		$this->DB->bind('i', $obj->whiteScore);
		$this->DB->bind('i', $obj->blackScore);
		$this->DB->bind('i', $obj->statusScore);
		$this->DB->bind('i', $obj->scrapeDate);
		$this->DB->bind('s', $obj->domain);
		$this->DB->bind('s', $obj->IP);
		$this->DB->bind('s', $obj->network);
		$this->DB->bind('s', $obj->hostname);
		$this->DB->bind('i', $obj->num_locations);
		$this->DB->bind('i', $obj->num_brands);
		$this->DB->bind('i', $obj->num_employees);
		$this->DB->bind('i', $obj->num_google_hits);
		$this->DB->bind('i', $obj->result);
		$this->DB->bind('i', $obj->statusLock);
		$this->DB->bind('i', $obj->id);

		$result = $this->DB->execute();

		if ($result->affected_rows == 0)
		{
			$this->error = 'The selected record (' . $obj->id . ') could not be updated.';
			return false;
		}

		return true;
	}

	// Delete store record
	public function delete($id)
	{
		$this->DB->prepare("DELETE FROM stores WHERE id = ?");
		$this->DB->bind('i', $id);
		$result = $this->DB->execute();

		if ($result->affected_rows == 0)
		{
			$this->error = 'The selected record appears to be missing.';
			return false;
		}

		// Delete addresses
		$this->DB->prepare("DELETE FROM addresses WHERE storeid = ?");
		$this->DB->bind('i', $id);
		$this->DB->execute();
		
		// Delete store content
		$this->DB->prepare("DELETE FROM storesContent WHERE storeid = ?");
		$this->DB->bind('i', $id);
		$this->DB->execute();

		// Delete store brands
		$this->DB->prepare("DELETE FROM storeBrands WHERE storeid = ?");
		$this->DB->bind('i', $id);
		$this->DB->execute();

		return true;
	}

	// Lock the store record
	public function lockStore($id)
	{
		if (empty($id))
		{
			$this->error = 'No record ID selected.';
			return false;
		}
		
		$this->DB->prepare("UPDATE stores SET statusLock = 2 WHERE id = ?", [
			[INTEGER, $id]
		]);
		$result = $this->DB->execute();

		if ($result->affected_rows == 0)
		{
			$this->error = 'The selected store record failed to lock.';
			return false;
		}

		return true;
	}

	/**
	* Get a list of valid top level domains from IANA
	**/
	// private function get_tlds()
	// {
	// 	$tlds_content = file_get_contents('https://data.iana.org/TLD/tlds-alpha-by-domain.txt');
		
	// 	if (empty($tlds_content))
	// 	{
	// 		$this->error = 'Warning: Unable to download TLD list.';
	// 		return false;
	// 	}
		
	// 	$array = explode(PHP_EOL, trim($tlds_content));
		
	// 	// The top line contains a version number and revision date that we can shave off
	// 	array_shift($array);
		
	// 	// Reduce the results to lowercase
	// 	$this->tldsArray = array_map('strtolower', $array);
	// }
}

?>
