<?php

/*###################################################################################################
#				Majicko 2.2.5 @2008-2013 Bandwise LLC All Rights Reserved,							#
#								http://www.Majicko.com												#
#																									#
#				This file may not be redistributed in whole or significant part.					#
#				---------------- MAJICKO IS NOT FREE SOFTWARE ----------------						#
#									http://www.majicko.com											#
#																									#
###################################################################################################*/

/*
Example Mailer

$mail = new Mail();

$mail->addFrom('mailserver@example.com', 'John Doe');
$mail->addTo('test@example.com', 'Christopher Gray');

$mail->subject = 'Test E-mail';
$mail->body = $body;
$mail->contentType = $type;

$mail->send();
*/

// Send e-mail via PHP mail or SMTP
class Mailer
{
	// Basic settings
		// Sendmail type: mail for PHP mail function, smtp for SMTP login
	var $type = 'mail';

	// SMTP Settings
	var $smtpServer = 'localhost';
	var $port = 25;
	var $timeout = 10;
	var $username = 'root@localhost';
	var $password = '';
	var $auth = true; // Do we authenticate?
	var $localhost = "localhost";
	var $newLine = "\r\n";
	var $contentType = 'text/plain';


	/**
	* When test mode is set to true, the mail class will only assemble the e-mail
	* components and return true as if it had sent the e-mail successfully. It doesn't
	* actually send any e-mail.
	**/
	var $testmode = false; // Do not actually send e-mail; Just simulate it

	// E-mail settings
	var $fromName = '';
	var $fromAddress = '';
	var $replyTo = null;
	var $to = array();
	var $cc = array();
	var $bcc = array();
	var $formattedRecips = '';

	var $headers = '';
	var $subject = '';
	var $body = '';
	var $mimebody = '';
	var $attachment = array();
	var $boundary = array();

	// Stored values
	var $smtpConnect;
	var $smtpResponse;
	var $error = array();
	var $failedRecips = array();
	var $logArray = array();
	var $commands = array();
	var $autoQuit = true;
	var $useDefaults = true;

	// Deprecated Properties
	var $toName = '';
	var $toAddress = '';

	/**
	* Configure the mailer
	*
	* @param	boolean			Use the CMS defaults to configure the mailer
	*/
	function __construct($defaults=true)
	{
		if ($defaults)
		{
			global $Majicko;

			if (sizeof($Majicko->settings) > 0)
			{
				$this->type = $Majicko->settings['mail_type']; // Sendmail type: mail, smtp

				// SMTP Settings
				$this->smtpServer = $Majicko->settings['smtp_host'];
				$this->port = $Majicko->settings['smtp_port'];
				$this->username = $Majicko->settings['smtp_username'];
				$this->password = $Majicko->settings['smtp_password'];
				$this->auth = ($Majicko->settings['smtp_auth'] == 1 ? true : false);
			}
		}

		// Put into test mode if the test mode constant is detected
		if (defined('__TESTMODE__'))
		{
			$this->testmode = true;
		}
	}

	/**
	* Reset the SMTP settings from a provided array
	*
	* @param	array			An array of SMTP settings
	*/
	function loadSettings($settings)
	{
		$this->type = $settings['mail_type']; // Sendmail type: mail, smtp

		// SMTP Settings
		$this->smtpServer = $settings['smtp_host'];
		$this->port = $settings['smtp_port'];
		$this->username = $settings['smtp_username'];
		$this->password = $settings['smtp_password'];
		$this->auth = ($settings['smtp_auth'] == 1 ? true : false);
	}


	/**
	* Set a boundary marker for the e-mail header
	*/
	function getBoundary()
	{
		$semi_rand = md5(time());
		$this->boundary = 'boundary_x' . $semi_rand . 'x';
	}


	/**
	* Connect to mail server
	*/
	function smtpConnect()
	{
		if (!$this->smtpConnect)
		{
			$this->smtpConnect = @fsockopen($this->smtpServer, $this->port, $errno, $errstr, $this->timeout);

			// Record connection attempt
			$this->commands[] = 'Connect to [' . $this->smtpServer . '] on port [' . $this->port . ']';

			if ($this->smtpConnect === false)
			{
				$this->error['connection'] = "Unable to connect to SMTP server. Please check your host server and port.";
				return false;
			}

			$this->smtpResponse = @fgets($this->smtpConnect, 515);

			if (substr($this->smtpResponse, 3, 1) == '-')
			{
				// This server talks too much
				while ($str = fgets($this->smtpConnect, 515))
				{
					$this->smtpResponse .= $str;

					// We're done here; break out
					if(substr($str, 3, 1) == ' ')
					{
						break;
					}
				}
			}
		}

		if ($this->smtpConnect)
		{
			$this->logArray['connection'] = '[Connected] ' . $this->smtpResponse;
			return true;
		}
		else
		{
			$this->error['connection'] = 'Unable to connect to SMTP server.';
			return false;
		}
	}


	/**
	* Send request to server
	*
	* @param	string			The command to be sent to the SMTP server
	* @param	string			Use this key to record this action in the log array
	*
	* @return	string			The response from the SMTP server
	*/
	function sendRequest($string, $log)
	{
		if (!$this->smtpConnect)
		{
			$this->error['connection'] = "Request sent to unready server.";
			$this->close();
			return false;
		}

		fputs($this->smtpConnect, $string . $this->newLine);
		$this->commands[] = $string . $this->newLine;

		// Get response from server
		$this->smtpResponse = fgets($this->smtpConnect, 515);

		// Check response
		if (!$this->smtpResponse)
		{
			$this->error['noresponse'] = 'The server did not respond to request.';
			$this->close();
			return false;
		}

		// Do we have additional lines?
		if (substr($this->smtpResponse, 3, 1) == '-')
		{
			while ($str = fgets($this->smtpConnect, 515))
			{
				$this->smtpResponse .= $str;

				// We're done here; break out
				if(substr($str, 3, 1) == ' ')
				{
					break;
				}
			}
		}

		$this->logArray[$log] = '[' . $log . '] ' . $this->smtpResponse;
		return $this->smtpResponse;
	}


	/**
	* String together e-mail headers
	*/
	function getHeaders()
	{
		$uniqid = md5(uniqid(time()));

		$headers = '';
		$headers .= "Date: ".date('r') . $this->newLine;
		$headers .= "Return-Path: " . $this->fromAddress . $this->newLine;

		if ($this->type != 'mail')
		{
			$this->formattedRecips = $this->formatRecipients($this->to);
			$headers .= "To: " . $this->formattedRecips . $this->newLine;
		}

		$headers .= "From: " . $this->formatRecipients(array(array($this->fromAddress, $this->fromName))) . $this->newLine;
		$headers .= "Reply-To: " . ($this->replyTo ? $this->replyTo : $this->fromAddress) . $this->newLine;

		if (sizeof($this->cc) > 0)
		{
			$this->formattedRecips .= ', ' . $this->formatRecipients($this->cc);
			$headers .= "Cc: " . $this->formatRecipients($this->cc) . $this->newLine;
		}

		if (sizeof($this->bcc) > 0)
		{
			$this->formattedRecips .= ', ' . $this->formatRecipients($this->bcc);
			$headers .= "Bcc: " . $this->formatRecipients($this->bcc) . $this->newLine;
		}

		if ($this->type != 'mail')
		{
			$headers .= "Subject: " . $this->subject . $this->newLine;
		}

		$headers .= "Message-ID: <" . $uniqid . "@" . $_SERVER['SERVER_NAME'] . ">" . $this->newLine;
		$headers .= "X-Priority: 3" . $this->newLine;
		$headers .= "X-Mailer: Majicko Mail via PHP" . $this->newLine;
		$headers .= "MIME-Version: 1.0" . $this->newLine;

		if (sizeof($this->attachment) != 0)
		{
			$this->getBoundary();

			$headers .= "Content-Type: multipart/mixed;" . $this->newLine;
			$headers .= "\t" . "boundary=\"" . $this->boundary . "\"" . $this->newLine;
		}
		else
		{
			$headers .= "Content-Transfer-Encoding: 8bit" . $this->newLine;
			$headers .= "Content-Type: " . $this->contentType . "; charset=\"iso-8859-1\"" . $this->newLine;
		}

		$this->headers = $headers;
	}


	/**
	* Send the e-mail
	*
	* @return	boolean			Return true if the SMTP exchange goes well
	*/
	function send()
	{
		// Mailer update catch
		$this->updateCatch();

		// Make sure we have some recipients
		if (sizeof($this->to) == 0)
		{
			$this->error['no_recipients'] = "No recipients detected. Ensure your recipients' addresses are valid.";
			return false;
		}

		// Get headers ready
		$this->getHeaders();

		if ($this->testmode)
		{
			// We're just simulating an e-mail; Return here
			return $this->simulate();
		}

		// Send e-mail via SMTP
		if ($this->type=='smtp')
		{
			$this->mimebody = '';

			// Connect to mail server
			if ($this->smtpConnect())
			{
				// Say hello to mail server
				$response = $this->sendRequest("EHLO " . $this->localhost, 'ehloresponse');

				if ( substr($response, 0, 3) != 250 )
				{
					// The server didn't like an extended hello; try HELO instead (RFC 2821)
					$response = $this->sendRequest("HELO " . $this->localhost, 'heloresponse');

					if ( substr($response, 0, 3) != 250 )
					{
						$this->error['no_heloresponse'] = "Improper response to HELO/EHLO:<br>" . $response;
						$this->close();
						return false;
					}
				}

				if ($this->auth)
				{
					// Authorize request
					$response = $this->sendRequest('AUTH LOGIN', 'authrequest');

					if ( substr($response, 0, 3) != 334 )
					{
						$this->error['no_authrequest'] = "Improper response to AUTH LOGIN:<br>" . $response;
						$this->close();
						return false;
					}

					$response = $this->sendRequest(base64_encode($this->username), 'authusername');

					if ( substr($response, 0, 3) != 334 )
					{
						$this->error['no_authusername'] = "Improper response to username:<br>" . $response;
						$this->close();
						return false;
					}

					$response = $this->sendRequest(base64_encode($this->password), 'authpassword');

					if ( substr($response, 0, 3) != 235 )
					{
						$this->error['no_authpassword'] = "Improper response to password:<br>" . $response;
						$this->close();
						return false;
					}
				}

				$response = $this->sendRequest("MAIL FROM: " . $this->fromAddress, 'mailfromresponse');

				if ( substr($response, 0, 3) != 250 )
				{
					$this->error['no_mailfromresponse'] = "Improper response to MAIL FROM:<br>" . $response;
					$this->close();
					return false;
				}

				// Add primary recipients
				$errorstop = true;

				if (sizeof($this->to) > 0)
				{
					foreach($this->to AS $key => $value)
					{
						$response = $this->sendRequest("RCPT TO: " . $value[0], 'mailto' . $key . 'response');

						if ( substr($response, 0, 3) != 250 )
						{
							$this->failedRecips[] = ($value[1] ? $value[1] . ', ' : false) . $value[0];
							$this->error['no_mailto' . $key . 'response'] = "Improper response to RCPT TO:<br>" . $response;
						}
						else
						{
							// It is safe to send what we can
							$errorstop = false;
						}
					}
				}

				if ($errorstop)
				{
					// All of the primary recipients failed! We can't continued
					$this->error['errorstop'] = "The mail server rejected all primary recipients. Cannot continue.";
					$this->close();
					return false;
				}

				// Add CC recipients
				if (sizeof($this->cc) > 0)
				{
					foreach($this->cc AS $key => $value)
					{
						$response = $this->sendRequest("RCPT TO: " . $value[0], 'mailtocc' . $key . 'response');

						if ( substr($response, 0, 3) != 250 )
						{
							$this->failedRecips[] = ($value[1] ? $value[1] . ', ' : false) . $value[0];
							$this->error['no_mailtocc' . $key . 'response'] = "Improper response to RCPT TO:<br>" . $response;
						}
					}
				}

				// Add BCC recipients
				if (sizeof($this->bcc) > 0)
				{
					foreach($this->bcc AS $key => $value)
					{
						$response = $this->sendRequest("RCPT TO: " . $value[0], 'mailtobcc' . $key . 'response');
						if ( substr($response, 0, 3) != 250 )
						{
							$this->failedRecips[] = ($value[1] ? $value[1] . ', ' : false) . $value[0];
							$this->error['no_mailtobcc' . $key . 'response'] = "Improper response to RCPT TO:<br>" . $response;
						}
					}
				}

				$response = $this->sendRequest("DATA", 'data1response');

				if ( substr($response, 0, 3) != 354 )
				{
					$this->error['no_data1response'] = "Improper response to DATA1:<br>" . $response;
					$this->close();
					return false;
				}

				// Enforce proper CRLF
				$search = array('/\r/', '/\n/');
				$replace = array('', $this->newLine);

				// Get data part of the e-mail
				if (sizeof($this->attachment) == 0)
				{
					// No attachments
					//$this->mimebody .= "Content-Transfer-Encoding: 8bit" . $this->newLine;
					//$this->mimebody .= "Content-Type: " . $this->contentType . "; charset=\"iso-8859-1\"" . $this->newLine;
					$this->mimebody .= preg_replace($search, $replace, $this->body);
				}
				else
				{
					// Adjust message to prepare for attachment
					$this->mimebody .= $this->newLine . "--" . $this->boundary . $this->newLine;
					$this->mimebody .= "Content-Type: " . $this->contentType . "; charset = \"iso-8859-1\"" . $this->newLine;
					$this->mimebody .= "Content-Transfer-Encoding: 8bit" . $this->newLine . $this->newLine;

					// Append e-mail body
					$this->mimebody .= preg_replace($search, $replace, $this->body) . $this->newLine;

					// Append attachment data with boundaries
					foreach($this->attachment AS $k => $file)
					{
						$content = chunk_split(base64_encode(file_get_contents($file)));

						// Attach file to e-mail
						$this->mimebody .= "--" . $this->boundary . $this->newLine;
						$this->mimebody .= "Content-Type: application/octet-stream; name=\"" . basename($file) . "\"" . $this->newLine;
						$this->mimebody .= "Content-Transfer-Encoding: base64" . $this->newLine;
						$this->mimebody .= "Content-Disposition: attachment; name=\"" . basename($file) . "\"" . $this->newLine . $this->newLine;
						$this->mimebody .= $content . $this->newLine . $this->newLine;
					}

					// Close boundary
					$this->mimebody .= "--" . $this->boundary . "--";
				}

				// Final addition to data
				$this->mimebody .= $this->newLine . $this->newLine . $this->newLine;
				$this->mimebody .= ".";



				// Append the MIME body to data
				$data = $this->headers . $this->newLine . $this->mimebody;

				$response = $this->sendRequest($data, 'data2response');

				if ( substr($response, 0, 3) != 250 )
				{
					$this->error['no_data2response'] = "Improper response to DATA2:<br>" . $response;
					$this->close();
					return false;
				}

				// Close connection to mail server
				$this->sendRequest("QUIT", 'quitresponse');

				if (!empty($this->smtpConnect))
				{
					fclose($this->smtpConnect);
					$this->smtpConnect = 0;
				}

				/*
					//$this->resetRecipients();
					We cannot reset here or the e-mail log will lose its recipient data. If the e-mail send is
					being executed in a loop, the resetRecipients method should be called in the loop.
				*/

				return true;
			}
			else
			{
				$this->close();
				return false;
			}
		}

		// Send e-mail via PHP mail function
		if ($this->type == 'mail')
		{
			$this->mimebody = '';

			// Check for attachments
			if (sizeof($this->attachment) == 0)
			{
				// No attachments
				$this->mimebody .= "Content-Transfer-Encoding: 8bit" . $this->newLine;
				$this->mimebody .= "Content-Type: " . $this->contentType . "; charset=\"iso-8859-1\"" . $this->newLine;
				$this->mimebody .= $this->body;
			}
			else
			{
				// Adjust message to prepare for attachment
				$this->mimebody .= $this->newLine . "--" . $this->boundary . $this->newLine;
				$this->mimebody .= "Content-Type: " . $this->contentType . "; charset = \"iso-8859-1\"" . $this->newLine;
				$this->mimebody .= "Content-Transfer-Encoding: 8bit" . $this->newLine . $this->newLine;

				// Append e-mail body
				$this->mimebody .= $this->body . $this->newLine;

				// Append attachment data with boundaries
				foreach($this->attachment AS $k => $file)
				{
					$content = chunk_split(base64_encode(file_get_contents($file)));

					// Attach file to e-mail
					$this->mimebody .= "--" . $this->boundary . $this->newLine;
					$this->mimebody .= "Content-Type: application/octet-stream; name=\"" . basename($file) . "\"" . $this->newLine;
					$this->mimebody .= "Content-Transfer-Encoding: base64" . $this->newLine;
					$this->mimebody .= "Content-Disposition: attachment; name=\"" . basename($file) . "\"" . $this->newLine . $this->newLine;
					$this->mimebody .= $content . $this->newLine . $this->newLine;
				}

				// Close boundary
				$this->mimebody .= "--" . $this->boundary . "--";
			}

			$this->mimebody .= $this->newLine;

			// Format list of recipients
			$recips = array_merge($this->to, $this->cc, $this->bcc);
			$this->formattedRecips = $this->formatRecipients($recips);

			if (mail($this->formattedRecips, $this->subject, $this->mimebody, $this->headers))
			{
				$this->logArray['phpmail'] = "PHP successfully sent e-mail.";
				$this->resetRecipients();
				return true;
			}
			else
			{
				//logemail($this->toAddress, $this->fromAddress, $this->subject, $this->body, $this->headers, false);
				$this->error['sendmailfail'] = "PHP Mail failed to send. Sendmail may not be functioning.";
				return false;
			}
		}

		$this->error['nosend'] = 'Invalid send type selected.';
	}


	/**
	* Set the sender information
	*
	* @param	string			The e-mail address of the sender
	* @param	string			The name of the sender
	*/
	function addFrom($address, $name='')
	{
		if (!empty($address) AND $this->validateAddress($address))
		{
			$this->fromName = $name;
			$this->fromAddress = $address;
		}
	}


	/**
	* Add a recipient to the primary recipients array.
	*	These will be visible to all recipients.
	*
	* @param	string			The e-mail address of the recipient
	* @param	string			The name of the recipient
	*/
	function addTo($address, $name='')
	{
		if (!empty($address) AND $this->validateAddress($address))
		{
			$this->to[] = array($address, $name);
		}
	}


	/**
	* Add a recipient to the CC recipients array
	*	These will be visible to all recipients.
	*
	* @param	string			The e-mail address of the recipient
	* @param	string			The name of the recipient
	*/
	function addCC($address, $name='')
	{
		if (!empty($address) AND $this->validateAddress($address))
		{
			$this->cc[] = array($address, $name);
		}
	}


	/**
	* Add a recipient to the BCC recipients array
	*	These will not be visible to recipients.
	*
	* @param	string			The e-mail address of the recipient
	* @param	string			The name of the recipient
	*/
	function addBCC($address, $name='')
	{
		if (!empty($address) AND $this->validateAddress($address))
		{
			$this->bcc[] = array($address, $name);
		}
	}


	/**
	* Attach a file to the e-mail.
	*
	* @param	string			The e-mail address of the recipient
	*/
	function addAttachment($file)
	{
		if (file_exists($file))
		{
			$this->attachment[] = $file;
		}
		else
		{
			$this->close();
			trigger_error('Cannot attach file "' . $file . '" to an e-mail. File does not exist.', E_USER_ERROR);
		}
	}


	/**
	* Format recipients array into a delimited string
	*	"Name" <e-mail address>, "Name" <e-mail address>, "Name" <e-mail address>
	*	RFC 2822
	*
	* @param	array			The array to be formatted
	*
	* @return	string			Return the formatted string ready for use in the header
	*/
	function formatRecipients($array)
	{
		$rows = array();

		if (sizeof($array) > 0)
		{
			foreach($array AS $key => $value)
			{
				$string = '';

				if (!empty($value[1]))
				{
					$string .= '"' . $value[1] . '" ';
				}

				$string .= '<' . $value[0] . '>';

				$rows[] = $string;
			}

			return implode(', ', $rows);
		}
	}

	/**
	* Initiate a quick disconnect
	*
	* @return	string			Return false if failed
	*/
	function close()
	{
		if (!$this->smtpConnect)
		{
			$this->error['quickdisconnect'] = "Attempting to disconnect from unready server.";
			return false;
		}

		fputs($this->smtpConnect, "QUIT" . $this->newLine);
		$this->smtpResponse = fgets($this->smtpConnect, 515);

		if (!empty($this->smtpConnect))
		{
			fclose($this->smtpConnect);
			$this->smtpConnect = 0;
		}

		$this->logArray['quitresponse'] = $this->smtpResponse;
	}

	/**
	* Execute a simulated e-mail test
	*
	* @return	boolean			Always return true
	*/
	function simulate()
	{
		if ($this->type == 'smtp')
		{
			$this->mimebody = '';

			// Format list of recipients
			$recips = array_merge($this->to, $this->cc, $this->bcc);
			$this->formattedRecips = $this->formatRecipients($recips);

			// Flag the e-mail body
			$nlbr = PHP_EOL;

			if ($this->contentType == 'text/html')
			{
				$nlbr = '<br>';
			}

			$string = '#############################################' . $nlbr;
			$string .= '#			This is a simulation!			#' . $nlbr;
			$string .= '#		No e-mail was actually sent.		#' . $nlbr;
			$string .= '#############################################' . $nlbr;

			if (substr($this->subject, 0, 11) != '[Simulated]')
			{
				// Tag this e-mail as a simulation so any e-mail logging outside will see this
				$this->body = $string . $this->body;
				$this->subject = '[Simulated] ' . $this->subject;
			}

			// Set commands
			$this->commands[] = 'Simulated connect to [mail.example.com] on port [25]' . $this->newLine;
			$this->commands[] = 'EHLO localhost' . $this->newLine;
			$this->commands[] = 'AUTH LOGIN' . $this->newLine;
			$this->commands[] = 'bWFpbHNlcnZlckBiYW5kd2lzZS5jb20=' . $this->newLine;
			$this->commands[] = 'YkBuZHchJDNtQCFs' . $this->newLine;
			$this->commands[] = 'MAIL FROM: ' . $this->fromAddress . $this->newLine;

			if (sizeof($this->to) > 0)
			{
				foreach($this->to AS $key => $value)
				{
					$this->commands[] = 'RCPT TO: ' . $value[0] . $this->newLine;
				}
			}

			if (sizeof($this->cc) > 0)
			{
				foreach($this->cc AS $key => $value)
				{
					$this->commands[] = 'RCPT TO: ' . $value[0] . $this->newLine;
				}
			}

			if (sizeof($this->bcc) > 0)
			{
				foreach($this->bcc AS $key => $value)
				{
					$this->commands[] = 'RCPT TO: ' . $value[0] . $this->newLine;
				}
			}

			$this->commands[] = 'DATA' . $this->newLine;

			// Begin building data to send
			$data .= $this->headers . $this->newLine;

			// Get data part of the e-mail
			if (empty($this->attachment))
			{
				$data .= $this->body . $this->newLine . $this->newLine . $this->newLine;
			}
			else
			{
				// We have an attachment
				if (!$file = @fopen($this->attachment, 'rb'))
				{
					$this->error['noattach'] = "File attachment could not be read.";
					$this->close();
					return false;
				}

				//$fileatt_type = mime_content_type($this->attachment);

				$content = @fread($file, filesize($this->attachment));
				@fclose($file);
				$content = chunk_split(base64_encode($content));

				// Adjust message to prepare for attachment
				$data .= $this->newLine . "--" . $this->boundary . $this->newLine;
				$data .= "Content-Type: " . $this->contentType . "; charset = \"iso-8859-1\"" . $this->newLine;
				$data .= "Content-Transfer-Encoding: 8bit" . $this->newLine . $this->newLine;

				// Attach e-mail body
				$data .= $this->body . $this->newLine . $this->newLine;

				// Attach file to e-mail
				$data .= "--" . $this->boundary . $this->newLine;
				$data .= "Content-Type: application/octet-stream; name=\"" . basename($this->attachment) . "\"" . $this->newLine;
				$data .= "Content-Transfer-Encoding: base64" . $this->newLine;
				$data .= "Content-Disposition: attachment; name=\"" . basename($this->attachment) . "\"" . $this->newLine . $this->newLine;
				$data .= $content . $this->newLine . $this->newLine;
				$data .= "--" . $this->boundary . "--" . $this->newLine . $this->newLine . $this->newLine;
			}

			// Final addition to data
			$data .= "." . $this->newLine;

			// Enforce proper CRLF
			//$data = str_replace("\r", "", $data);
			//$data = str_replace("\n", $this->newLine, $data);
			$search = array('/\r/', '/\n/');
			$replace = array('', $this->newLine);

			$this->mimebody .= preg_replace($search, $replace, $this->body);

			$this->commands[] = $data . $this->newLine;

			$this->commands[] = 'QUIT' . $this->newLine;

			// Set responses
			$this->logArray['Connected'] = '[Connected] 220 This is a simulated connection.' . $this->newLine;
			$this->logArray['ehloresponse'] = '[ehloresponse] 250 Nobody is home.' . $this->newLine;
			$this->logArray['authrequest'] = '[authrequest] 334 VrNlcm8hbEU1' . $this->newLine;
			$this->logArray['authusername'] = '[authusername] 334 065ec6c36b89' . $this->newLine;
			$this->logArray['authpassword'] = '[authpassword] 235 Authentication succeeded <trollface>' . $this->newLine;
			$this->logArray['mailfromresponse'] = '[mailfromresponse] 250 OK' . $this->newLine;
			$this->logArray['mailtoresponse'] = '[mailtoresponse] 250 Accepted' . $this->newLine;
			$this->logArray['data1response'] = '[data1response] 354 Enter message, ending with "." on a line by itself or else you will screw things up.' . $this->newLine;
			$this->logArray['data2response'] = '[data2response] 250 OK id=b0b020d36317' . $this->newLine;
			$this->logArray['quitresponse'] = '[quitresponse] 221 Closing this connection that we never opened' . $this->newLine;

			return true;
		}

		if ($this->type=='mail')
		{
			// Format list of recipients
			//$recips = array_merge($this->to, $this->cc, $this->bcc);
			//$this->formattedRecips = $this->formatRecipients($recips);

			$string = '#############################################' . $this->newLine;
			$string .= '#			This is a simulation!			#' . $this->newLine;
			$string .= '#		No e-mail was actually sent.		#' . $this->newLine;
			$string .= '#############################################' . $this->newLine;

			if (substr($this->subject, 0, 11) != '[Simulated]')
			{
				// Tag this e-mail as a simulation so any e-mail logging outside will see this
				$this->body = $string . $this->body;
				$this->subject = '[Simulated] ' . $this->subject;
			}

			$this->body = $string . $this->body;

			$this->logArray['phpmail'] = "A simulated attempt to e-mail through PHP mail was made.";
			return true;
		}
	}


	/**
	* Old Mailer Version Catch
	*
	*	Older versions of the mailer use the toName and toAddress properties.
	*	This method will catch the contents of these properties and convert
	*	them to the new array-based method. This way, software using the old
	*	method of sending e-mail won't break.
	*/
	function updateCatch()
	{
		if (!empty($this->toAddress))
		{
			$this->logArray['deprecation_warning'] = 'NOTICE! You are adding recipients using the toAddress property. Consider using the addTo method.';
			$this->addTo($this->toAddress, $this->toName);
		}
	}

	/**
	* Validate for proper e-mail format
	*
	* @param	string			The e-mail address to validate
	*
	* @return	boolean			Return true if the address is valid; otherwise false
	*/
	function validateAddress($email)
	{
		if (!preg_match("/^([a-zA-Z0-9\._\-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/", $email))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	* Reset all of the recipients arrays
	*	This is useful when you are looping multiple connections and sends.
	*/
	function resetRecipients()
	{
		$this->to = array();
		$this->cc = array();
		$this->bcc = array();
	}
}

?>
