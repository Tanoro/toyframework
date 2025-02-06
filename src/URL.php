<?php

class URL
{
	public $tldsArray = array();
	private $tld_url = 'https://publicsuffix.org/list/public_suffix_list.dat';
	private $tld_file = 'temp/public_suffix_list.txt';

	public $error = null;

	/**
	 * Parse URL into parts; extends beyond PHP parse_url
	 *
	 * @param	string			URL to parse
	*/
	public function parse($url)
	{
		$parsed_url = parse_url($url);

		if ($parsed_url === false || empty($parsed_url['host']) === true)
		{
			return false;
		}

		if (sizeof($this->tldsArray) == 0 && $this->getTLDArray() === false)
		{
			return $parsed_url;
		}

		$parsed_url['tld'] = null;
		$parsed_url['sld'] = null;

		// Determine TLD
		foreach($this->tldsArray AS $suffix)
		{
			$len = strlen($suffix);

			if (substr($parsed_url['host'], $len * -1, $len) == $suffix)
			{
				$parsed_url['tld'] = $suffix;
				$parsed_url['sld'] = array_pop(explode('.', substr($parsed_url['host'], 0, ($len*-1)-1)));

				// Get possible subdomain
				$parsed_url['subdomain'] = rtrim(str_replace([$parsed_url['tld'], $parsed_url['sld']], null, $parsed_url['host']), '.');

				if (empty($parsed_url['subdomain']))
				{
					unset($parsed_url['subdomain']);
				}

				break;
			}
		}

		return $parsed_url;
	}

	/**
	* Process TLD list
	**/
	public function getTLDArray()
	{
		if (sizeof($this->tldsArray) == 0)
		{
			$tlds_content = $this->getTLDContent();

			if ($tlds_content === false)
			{
				return false;
			}
		}

		$array = explode(PHP_EOL, trim($tlds_content));

		// Parse the TLD file content, skipping comment lines
		$this->tldsArray = array_filter($array, function($var){
			if (!empty($var) && substr($var, 0, 2) != '//')
			{
				return $var;
			}
		});

		// Sort longest to shortest
		usort($this->tldsArray, function($a, $b){
			return strlen($b)-strlen($a);
		});

		return true;
	}

	/**
	* Get TLD content, caching a file
	**/
	private function getTLDContent()
	{
		$tld_file_path = __ROOT__ . '/' . $this->tld_file;

		if (file_exists($tld_file_path) === false)
		{
			$tlds_content = file_get_contents($this->tld_url);

			if (empty($tlds_content))
			{
				$this->error = 'Unable to reach publicsuffix.org TLD list';
				return false;
			}

			$fp = fopen($tld_file_path, 'w');
			fwrite($fp, $tlds_content);
			fclose($fp);
		}
		else
		{
			$tlds_content = file_get_contents($tld_file_path);

			if (empty($tlds_content))
			{
				// The cache file is empty? Try to recapture it
				unlink($tld_file_path);

				$tlds_content = $this->getTLDContent();
			}
		}

		return $tlds_content;
	}
}

?>
