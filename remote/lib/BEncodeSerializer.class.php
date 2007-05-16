<?php

	/*
	 *	Copyright © Malcolm Jarvis and Kendall Hopkins
	 *	This code is licensed under the Creative Commons Attribution-NonCommercial-ShareAlike 3.0 license.
	 *	For more details, see http://creativecommons.org/licenses/by-nc-sa/3.0/
	 */

	class BEncodeSerializer
	{
		public function Serialize($mixed)
		{
			switch (gettype($mixed)) {
			case is_null($mixed):
				return $this->encode_string('');
				break;
			case 'string':
				return $this->encode_string($mixed);
				break;
			case 'integer':
				return  $this->encode_int($mixed);
				break;
			case 'array':
				return $this->encode_array($mixed);
				break;
			default:
				die("BEncodeSerializer::encode() - Unsupported type. Variable must be one of 'string', 'integer' or 'array'\n");
			}
		}

		private function encode_string($str)
		{
			return sprintf('%s:%s', strlen($str), $str);
		}
	
		private function encode_int($int)
		{
			return sprintf('i%se', $int);
		}
	
		private function encode_array($array)
		{
			$isList = true;
			foreach (array_keys($array) as $key) {
				if (!is_int($key)) {
					$isList = false;
					break;
				}
			}
			if ($isList) {
				ksort($array, SORT_NUMERIC);
				$return = 'l';
				foreach ($array as $val) {
					$return .= $this->Serialize($val);
				}
				$return .= 'e';
			} else {
				ksort($array, SORT_STRING);
				$return = 'd';
				foreach ($array as $key => $val) {
					$return .= $this->Serialize(strval($key));
					$return .= $this->Serialize($val);
				}
				$return .= 'e';
			}
			return $return;
		}

	}

?>