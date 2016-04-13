<?php

	/**
	 * The following class is provided for being able to perform conversions between different color notations
	 *
	 * @author Federico Stange (pthreat) <jpfstange@gmail.com>
	 * @license 3 Clause BSD 
	 * @package stange\util\conversion
	 * @example examples color.php
	 * @todo Add other notations such as LAB, etc
	 * 
	 */

	 namespace stange\util\conversion{

		class Color{

			private	$hex					=	NULL;
			private	$origValue			=	NULL;
			private	$dataDir				=	NULL;
			private	$detectedFormat	=	NULL;

			const	FORMAT_ANSI		=	'ANSI';
			const	FORMAT_STRING	=	'STRING';
			const FORMAT_RGB		=	'RGB';
			const	FORMAT_HEX		=	'HEX';
			const FORMAT_UNKNOWN	=	'UNKNOWN';

			/**
			 * Class constructor
			 * @param string $string Color string in hex, rgb, ANSI or string notation
			 * @throws \Exception If the given string format can not be detected
			 * @throws \RuntimeException If the data directory can not be found (this should not happen unless the package is broken)
			 */

			public function __construct($string){

				$string			=	trim(sprintf('%s',$string));

				$ds				=	DIRECTORY_SEPARATOR;

				$this->dataDir	=	sprintf('%s%s..%s..%s..%sdata%scolor%s',realpath(__DIR__),$ds,$ds,$ds,$ds,$ds,$ds);

				if(!is_dir($this->dataDir)){

					throw new \RuntimeException("Data dir could not be found at the specified location: {$this->dataDir}");

				}

				//Save original value
				$this->origValue	=	$string;

				//Detect string format and translate it to hex
				$this->hex			=	$this->anyToHex($string);


			}

			/**
			 * Convert any string to hex notation.
			 * Detect the format of the string through a series of regexes.
			 * @return string Hex color string including the hash, i.e #112233
			 * @throws \Exception If the given string format can not be detected
			 */

			private function anyToHex($string){

				$string	=	trim($string);

				//Test for hex first
				if(preg_match('/^#[0-9a-fA-F]+$/',$string)){

					$string	=	$this->stripHash($string);
					$len		=	strlen($string);

					if($len==6){

						return "#$string";

					}

					$lastChar	=	$string[$len];

					$this->detectedFormat	=	self::FORMAT_HEX;

					return sprintf('#%s%s',$string,str_repeat($string[$len],6));

				}

				//RGB notation
				if(preg_match('#^rgb\((\d+),(\d+),(\d+)\)$#',$string)){

					$this->detectedFormat	=	self::FORMAT_RGB;

					return $this->rgb2hex($string);

				}

				//If dealing with a string, assume color names like, navy, teal, ivory, etc
				if(preg_match('#^[A-Za-z]+$#',$string)){

					$this->detectedFormat	=	self::FORMAT_STRING;

					return $this->stringToHex($string);

				}

				//Assume ANSI integer
				if($string<255){

					$this->detectedFormat	=	self::FORMAT_ANSI;

					return sprintf('#%s',$this->getMap('ansi')[$string]);

				}

				$this->detectedFormat	=	self::FORMAT_UNKNOWN;

				throw new \Exception("Color format ->$string<- not recognized");

			}

			/**
			 * Obtains which was the detected format for the entered string at the __construct method
			 * @return string Name of detected format
			 */

			public function getDetectedFormat(){

				return $this->detectedFormat;

			}

			/**
			 * Returns the original value entered at the __construct method
			 * @return string The original value
			 */

			public function getOriginalValue(){

				return $this->origValue;

			}

			/**
			 * Convert a color string, i.e: blue, orange, red to HEX notation.
			 *
			 * @param string $string A valid color name, for instance, green, ivory, etc.
			 * @return string Hex string of the indicated color.
			 * @throws \InvalidArgumentException if the indicated color in $string was not found.
			 */

			private function stringToHex($string){

				$data	=	$this->getMap('strings');

				if(array_key_exists($string,$data)){

					return sprintf('%s',$data[$string]);

				}

				throw new \InvalidArgumentException("Color ->$string<- not found");

			}

			/**
			 * Strip the hash from a HEX color string
			 * @param string $rgb a HEX notated color string, i.e: #112233
			 * @return string The same string without the starting hash, i.e: 112233
			 */

			private function stripHash($rgb){

				if($rgb[0] == '#'){

					$rgb	=	str_split($rgb);
					unset($rgb[0]);
					return implode('',$rgb);

				}

				return $rgb;

			}

			/**
			 * Convert a RGB string to HEX notation
			 * @param $str an RGB notated string, for instance: rgb(1,2,3)
			 * @throws \InvalidArgumentException if the RGB string has an incorrect format.
			 * @return string a HEX string, i.e: #aabbcc
			 */

			private function rgb2hex($str){

				$str	=	substr($str,strpos($str,'(')+1);
				$str	=	substr($str,0,strpos($str,')'));
				$rgb	=	explode(',',$str);

				$size	=	sizeof($rgb);

				if($size !== 3){

					throw new \InvalidArgumentException("Invalid RGB string ->$str<-");

				}

				return sprintf('#%s%s%s',sprintf('%02x', $rgb[0]),sprintf('%02x', $rgb[1]),sprintf('%02x', $rgb[2]));

			}

			/**
			 * Get a color map file from the data library
			 * @return Array Associative array containing the indicated color map.
			 * @throws \InvalidArgumentException if the requested map file could not be open.
			 * @throws \Exception if the given map file contains errors and could not be json decoded.
			 */

			private function getMap($map){

				$file	=	sprintf('%s%s.json',$this->dataDir,$map);

				if(file_exists($file) && is_readable($file)){

					$data		=	json_decode(file_get_contents($file),$assoc=TRUE);
					$error	=	json_last_error();

					if($error !== \JSON_ERROR_NONE){

						throw new \Exception("Error decoding requested map file ->$map<-. Details: $error");

					}

					return $data;

				}

				throw new \InvalidArgumentException("No such color map -> $file <-");

			}

			/**
			 * Convert to ANSI color notation, this method has two approaches.
			 * First, it tries to detect if the current color matches directly a color in the ANSI color table.
			 * If it doesn't it will try to calculate the nearest ANSI color for the current HEX string.
			 *
			 * @throws \InvalidArgumentException if the ansi color map could not be found.
			 * @return int An integer number representing the ANSI color
			 */

			public function toANSI(){

				if($this->detectedFormat == self::FORMAT_ANSI){

					return $this->getOriginalValue();

				}

				$data		=	array_flip($this->getMap('ansi'));
				$hex		=	$this->stripHash($this->hex);

				if(array_key_exists($hex,$data)){

					return $data[$hex];

				}

				//Thanks to Micah Elliott (http://micahelliott.com)

				$incs		=	Array('00','5f','87','af','d7','ff');
				$parts	=	str_split($this->stripHash($this->hex),2);
				$size		=	sizeof($incs);

				$res	=	Array();

				foreach($parts as $part){

					$part	=	intval($part,16);

					$i=0;

					while($i<$size-1){

						$s	=	hexdec($incs[$i]);
						$b	=	hexdec($incs[$i+1]);

						if($s <= $part && $part <= $b){

							$s1 = abs($s - $part);
							$b1 = abs($b - $part);

							$res[]	=	dechex($s1 < $b1	?	$s	:	$b);

							break;

						}

						$i++;

					}

				}

				return $data[implode('',$res)];

			}

			public function toString(){

				if($this->detectedFormat == self::FORMAT_STRING){

					return $this->getOriginalValue();

				}

				$data	=	array_flip($this->getMap('strings'));

				if(array_key_exists($this->hex,$data)){

					return sprintf('%s',$data[$this->hex]);

				}

				return '';

			}

			/**
			 * Get the HEX Color string. Everything is converted to HEX notation from the beginning, turning this to be very simple.
			 * @return string Color string in HEX notation
			 */

			public function toHEX(){

				return $this->hex;

			}

			/**
			 * Get the RGB Color string.
			 * @return string Color string in RGB notation
			 */

			public function toRGB(){

				if($this->detectedFormat == self::FORMAT_RGB){

					return $this->getOriginalValue();

				}

				$hex	=	$this->stripHash($this->hex);
				$hex	=	str_split($hex,2);

				return sprintf('rgb(%s,%s,%s)',hexdec($hex[0]),hexdec($hex[1]),hexdec($hex[2]));

			}

		}

	}
