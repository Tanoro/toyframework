<?php

class Helper
{
	// Get generic drop menu
	public function array2select($array, $match=null, $usekey=false)
	{
		$options = '';

		if (sizeof($array) == 0)
		{
			$options = '<optgroup label="-No Options-"></optgroup>';
		}

		foreach($array AS $key => $value)
		{
			if ($usekey)
			{
				$options .= '<option value="' . $key . '"' . ($match == $key ? ' selected' : '') . '>' . $value . '</option>' . PHP_EOL;
			}
			else
			{
				$options .= '<option value="' . $value . '"' . ($match == $value ? ' selected' : '') . '>' . $value . '</option>' . PHP_EOL;
			}
		}

		return $options;
	}
}

?>
