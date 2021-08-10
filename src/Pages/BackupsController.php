<?php

namespace Pages;

use Ifsnop\Mysqldump as IMysqldump;

class BackupsController extends \Controller
{
	var $slug = 'backups';

	public function __construct($config, $pathArray)
	{
		parent::__construct($config);

		// Set page info
		$this->pageinfo = array(
			'title' => 'Database Backup',
			'slug' => $this->slug
		);

		if ($this->request->is('POST'))
		{
			$this->post();
			return;
		}

		# /backups/log_file.sql.gz
		if (isset($pathArray[1]))
		{
			$this->download($pathArray[1]);
			return;
		}

		// Default view
		$this->view();
	}

	// Create backup
	public function post()
	{
		$dirPath = __ROOT__ . '/db/backups';
		$newfile = $dirPath . '/' . date('Y-m-d_Hi') . '.sql.gz';

		/*
		* Create a data backup
		* The backup is only of the data and not the database structure. Every backup restore starts with the schema file,
		* which purges the database and the backup made here inserts the data. Complete inserts are necessary or else any new
		* fields added to the structure will throw an error on restore.
		*/
		// $com = "mysqldump ";
		// $com .= "-u " . $this->config['db']['username'] . " ";
		// $com .= "--password='" . $this->config['db']['password'] . "' ";
		// $com .= "--host='" . $this->config['db']['server'] . "' ";
		// $com .= "--complete-insert ";
		// $com .= "--no-create-info ";
		// $com .= "--skip-triggers ";
		// $com .= $this->config['db']['database_name'] . " ";
		// // Zip the resulting file
		// $com .= "| gzip > $newfile";
		// exec($com);

		try {
			$options = [
				'compress' => IMysqldump\Mysqldump::GZIP,
				'complete-insert' => true,
				'no-create-info' => true,
				'skip-triggers' => true
			];

			$dump = new IMysqldump\Mysqldump('mysql:host=' . $this->config['db']['server'] . ';dbname=' . $this->config['db']['database_name'], $this->config['db']['username'], $this->config['db']['password'], $options);
			$dump->start($newfile);
		}
		catch (\Exception $e)
		{
			$this->smarty->assign('dialogue', [
				'width' => '60%',
				'title' => 'Backup Failed',
				'string' => 'MySQLDump returned the error: ' . $e->getMessage()
			]);
			$this->display('error.tpl');
			return false;
		}

		$this->view();
	}

	// Download a backup file
	public function download($file)
	{
		$dirPath = __ROOT__ . '/db/backups';
		$filepath = $dirPath . '/' . $file;

		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false); // required for certain browsers
		header("Content-Type: application/x-gzip");
		header("Content-Disposition: attachment; filename=\"$file\";" );
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . filesize($filepath));

		readfile($filepath);

		echo $filepath;
	}

	// Default list view
	public function view()
	{
		$dirPath = __ROOT__ . '/db/backups';

		$skipfiles = array(
			'.',
			'..',
			'.htaccess',
			'.htpasswds',
			'error_log'
		);

		$rows = array();

		if (is_dir($dirPath))
		{
			if ($handle = opendir($dirPath))
			{
				while (false !== ($file = readdir($handle)))
				{
					if (in_array($file, $skipfiles))
					{
						continue;
					}

					$rows[] = $file;
				}

				rsort($rows);
			}

			closedir($handle);

			if (sizeof($rows) != 0)
			{
				foreach($rows AS $k => $file)
				{
					$this->smarty->append('rows', array(
						'file' => $file,
						'path' => '/backups/' . $file,
						'moddate' => filemtime($dirPath . '/' . $file),
						'filesize' => round(filesize($dirPath . '/' . $file) / 1024, 2) . ' KB'
					));
				}
			}
		}

		// Final variables to be added directly to the template
		$this->display($this->slug . '.tpl');
	}
}

?>
