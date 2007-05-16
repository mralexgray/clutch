<?php

	/*
	 *	Copyright © Malcolm Jarvis and Kendall Hopkins
	 *	This code is licensed under the Creative Commons Attribution-NonCommercial-ShareAlike 3.0 license.
	 *	For more details, see http://creativecommons.org/licenses/by-nc-sa/3.0/
	 */

	class TransmissionController
	{
		public $SocketURL;
		private $LastMessage;
		private $Socket;

		public $MessageController;

		public $Debug = false;

		private $NewLine = "\n";

		public $IPCProtocol; // Message Protocol class instance

		/* 
		 * __construct()
		 * Constructer for class. runs initial setup
		 */
		public function __construct($SocketURL = false)
		{
			if ($SocketURL === false)
			{
				if (PHP_OS == 'Darwin')
					$this->SocketURL = '/Users/malcolm/Library/Application Support/Transmission/daemon/socket';
				else
					$this->SocketURL = '~/.transmission/daemon/socket';
			}
			else
				$this->SocketURL = $SocketURL;

			$this->IPCProtocol = new IPCProtocol;
			$this->Connect();

			// write something in for $this->NewLine (CLI == \n ; WEB == <br />)
		}

		/*
		 * __destruct()
		 * Runs shutdown procedure
		 */
		public function __destruct()
		{
			$this->Disconnect();
		}

		/*
		 * private Debug()
		 * outputs debug messages
		 */
		private function Debug($Message, $Return = true)
		{
			if ($Return === false)
				$ReturnMessage = '[ERROR]';
			elseif ($Return === null)
				$ReturnMessage = '';
			else
				$ReturnMessage = '[OK]';

			if ($this->Debug === true)
				echo $Message.' '.$ReturnMessage.$this->NewLine;

			if ($Return === false)
				die('This program has encountered an error and will now exit.'.$this->NewLine);
		}

		/*
		 * private Connect()
		 * Creates and sets up socket.
		 * Returns: void
		 */
		private function Connect()
		{
			$this->Create();
			$this->Bind();
			$Message = array("version" => array('min' => 2,'max' => 2));
			$this->Send($this->IPCProtocol->CreateMessage($Message));
		}

		/*
		 * private Create()
		 * Creates UNIX socket for communication
		 * Returns: Resource on success, bool(false) on failure
		 */
		private function Create()
		{
			$this->Socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
			$this->Debug('Creating Socket...', $this->Socket);
			return $this->Socket;
		}

		/*
		 * private Bind()
		 * Binds socket to $this->$SocketURL
		 * Returns: bool(true) or bool(false)
		 */
		private function Bind()
		{
			socket_set_option($this->Socket, SOL_SOCKET, SO_REUSEADDR, true);
			$Return = socket_connect($this->Socket, $this->SocketURL);
			$this->Debug('Connecting to socket at [ '.$this->SocketURL.' ] ...', $Return);
			return $Return;
		}

		/*
		 * public Send()
		 * Sends bencoded messages to the socket
		 * Returns: Response from server on success, bool(false) on failure
		 */
		public function Send($Message = null, $read = true)
		{
			if (is_null($Message)) $Message = $this->LastMessage;
			$Return = socket_write($this->Socket, $Message);

			if ($this->Debug === true)
			{
				ob_start();
					print_r($Message);
					$DebugMessage = ob_get_contents();
				ob_end_clean();
				$this->Debug('Writing [ '.$DebugMessage.' ] to the socket...', $Return);
			}

			return ($read === true) ? $this->Read() : true;
		}

		/*
		 * private Read()
		 * Reads response from socket
		 * Returns: Message on success, bool(false) on failure
		 */
		private function Read()
		{
			$Data = socket_read($this->Socket, hexdec(socket_read($this->Socket, 8)));
			$this->Debug('Reading from socket...', (!empty($Data)));
			$this->Debug('Read [ '.$Data.' ] from socket.', null);
	
			$Value = $this->IPCProtocol->UnSerializer->UnSerialize($Data);

			return $Value;
		}

		/*
		 * public Disconnect()
		 * Closes $this->Socket (use when quitting the controller)
		 */
		public function Disconnect()
		{
			if (is_resource($this->Socket))
				socket_close($this->Socket);
		}
	}
?>