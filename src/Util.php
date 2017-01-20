<?php

namespace dSStdlib;

class Util {

	const FILES_ONLY = 1;
	const DIRS_ONLY = 2;
	const ALL = 3;
	const UPLOAD_ERROR_NO_FILE = 'No file found';
	const UPLOAD_ERROR_SIZE = 'Size of file is too big';
	const UPLOAD_ERROR_EXTENSION = 'File extension is not allowed';
	const UPLOAD_ERROR_PATH = 'Create path failed';
	const UPLOAD_ERROR_PERMISSION = 'Insufficient permission to save';
	const UPLOAD_ERROR_FAILED = 'Upload failed';
	const UPLOAD_SUCCESSFUL = 'File uploaded successfully';

	public static $timezones;
	public static $uploadSuccess = self::UPLOAD_SUCCESSFUL;

	/**
	 * Turns camelCasedString to under_scored_string
	 * @param string $str
	 * @return string
	 */
	public static function camelTo_($str) {
		if (!is_string($str)) return '';
		$str[0] = strtolower($str[0]);
		$func = create_function('$c', 'return "_" . strtolower($c[1]);');
		return preg_replace_callback('/([A-Z])/', $func, $str);
	}

	/**
	 * Turns camelCasedString to hyphened-string
	 * @param string $str
	 * @param boolean $strtolower
	 * @return string
	 */
	public static function camelToHyphen($str, $strtolower = true) {
		if (!is_string($str)) return '';
		$str[0] = strtolower($str[0]);
		$func = create_function('$c', 'return "-" . $c[1];');
		$str = preg_replace_callback('/([A-Z])/', $func, $str);
		return ($strtolower) ? strtolower($str) : $str;
	}

	public static function arrayValuesCamelTo(array &$array, $to) {
		$func = create_function('$c', 'return "' . $to . '" . strtolower($c[1]);');
		foreach ($array as &$value) {
			if (!is_string($value)) continue;
			$value[0] = strtolower($value[0]);
			$value = preg_replace_callback('/([A-Z])/', $func, $value);
		}
		return $array;
	}

	/**
	 * Turns camelCasedString to spaced out string
	 * @param string $str
	 * @return string
	 */
	public static function camelToSpace($str) {
		if (!is_string($str)) return '';
		$str[0] = strtolower($str[0]);
		$func = create_function('$c', 'return " " . $c[1];');
		return preg_replace_callback('/([A-Z])/', $func, $str);
	}

	/**
	 * Turns under_scored_string to camelCasedString
	 * @param string $str
	 * @return string
	 */
	public static function _toCamel($str) {
		if (!is_string($str)) return '';
		return preg_replace_callback('/_([a-z])/', function($c) {
			return strtoupper($c[1]);
		}, $str);
	}

	/**
	 * Turns hyphened-string to camelCasedString
	 * @param string $str
	 * @return string
	 */
	public static function hyphenToCamel($str) {
		if (!is_string($str)) return '';
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/-([a-z])/', $func, $str);
	}

	/**
	 * Reads the required source directory
	 * @param string $dir
	 * @param int $return
	 * @param boolean $recursive
	 * @param string|array\null $extension Extensions without the dot (.)
	 * @param boolean $nameOnly Indicates whether to return full path of dirs/files or names only
	 * @param array $options keys include: 
	 * @return array
	 * @throws \Exception
	 */
	public static function readDir($dir, $return = Util::ALL, $recursive = false, $extension = NULL,
								$nameOnly = false, array $options = array()) {
		if (!is_dir($dir)) return array(
				'error' => 'Directory "' . $dir . '" does not exist',
			);

		if (!is_array($extension) && !empty($extension)) {
			$extension = array($extension);
		}

		if (substr($dir, strlen($dir) - 1) !== DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;

		$toReturn = array('dirs' => array(), 'files' => array());
		try {
			foreach (scandir($dir) as $current) {
				if (in_array($current, array('.', '..'))) continue;

				if (is_dir($dir . $current)) {
					if (in_array($return, array(self::DIRS_ONLY, self::ALL))) {
						$toReturn['dirs'][] = ($nameOnly) ? $current : $dir . $current;
					}
					if ($recursive) {
						$result = self::readDir($dir . $current, $return, true, $extension, $nameOnly, $options);
						switch ($return) {
							case self::ALL:
								$toReturn = array_merge($toReturn, $result);
								break;
							case self::DIRS_ONLY:
								$toReturn['dirs'] = array_merge($toReturn['dirs'], $result);
								break;
							case self::FILES_ONLY:
								$toReturn['files'] = array_merge($toReturn['files'], $result);
								break;
						}
					}
				}
				else if (is_file($dir . $current) && in_array($return, array(self::FILES_ONLY, self::ALL))) {
					if ($extension) $info = pathinfo($current);
					if (empty($extension) || (is_array($extension) && in_array($info['extension'], $extension))) {
						$toReturn['files'][$dir][] = ($nameOnly) ? $current : $dir . $current;
					}
				}
			}

			if ($return == self::ALL) return $toReturn;
			elseif ($return == self::DIRS_ONLY) return $toReturn['dirs'];
			elseif ($return == self::FILES_ONLY) return $toReturn['files'];
		}
		catch (\Exception $ex) {
			throw new \Exception($ex->getMessage());
		}
	}

	/**
	 * Copies a directory to another location
	 * @param string $source
	 * @param string $destination
	 * @param string $permission
	 * @param boolean $recursive
	 * @throws \Exception
	 */
	public static function copyDir($source, $destination, $permission = 0777, $recursive = true) {
		if (substr($source, strlen($destination) - 1) !== DIRECTORY_SEPARATOR)
				$destination .= DIRECTORY_SEPARATOR;

		try {
			if (!is_dir($destination)) mkdir($destination, $permission);

			$contents = self::readDir($source, self::ALL, $recursive, NULL);
			if (isset($contents['dirs'])) {
				foreach ($contents['dirs'] as $fullPath) {
					@mkdir(str_replace(array($source, DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR),
						array($destination,
								DIRECTORY_SEPARATOR), $fullPath), $permission);
				}
			}

			if (isset($contents['files'])) {
				foreach ($contents['files'] as $fullPathsArray) {
					foreach ($fullPathsArray as $fullPath) {
						@copy($fullPath,
			str_replace(array($source, DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR),
			   array(
									$destination, DIRECTORY_SEPARATOR), $fullPath));
					}
				}
			}
		}
		catch (\Exception $ex) {
			throw new \Exception($ex->getMessage());
		}
	}

	/**
	 * Deletes a directory and all contents including subdirectories and files
	 * 
	 * @param string $file
	 * @return boolean
	 */
	public static function delDir($dir) {
		$all = self::readDir($dir, self::ALL, true, NULL);
		if (isset($all['files'])) {
			foreach ($all['files'] as $file) {
				if (is_array($file)) {
					foreach ($file as $fil) {
						if (!unlink($fil)) {
							return false;
						}
					}
				}
				else {
					if (!unlink($file)) {
						return false;
					}
				}
			}
		}

		if (isset($all['dirs'])) {
			foreach (array_reverse($all['dirs']) as $_dir) {
				if (is_array($_dir)) {
					foreach ($_dir as $_dr) {
						if (!rmdir($_dr)) {
							return false;
						}
					}
				}
				else {
					if (!rmdir($_dir)) {
						return false;
					}
				}
			}
		}

		return rmdir($dir);
	}

	/**
	 * Resizes an image
	 * @param string $source Path to image file
	 * @param int $desiredWidth The width of the new image
	 * @param string $destination Path to save image to. If null, the source 
	 * will be overwritten
	 * @param string $extension The extension of the source file, provided if 
	 * the source does not bear an explicit extension
	 * @return boolean
	 */
	public static function resizeImage($source, $desiredWidth = 200, $destination = null,
									$extension = null) {
		if (!$destination) $destination = $source;

		$info = pathinfo($source);
		$extension = !$extension ? $info['extension'] : $extension;
		/* read the source image */
		switch (strtolower($extension)) {
			case 'jpeg':
			case 'jpg':
				$sourceImage = imagecreatefromjpeg($source);
				break;
			case 'gif':
				$sourceImage = imagecreatefromgif($source);
				break;
			case 'png':
				$sourceImage = imagecreatefrompng($source);
				break;
		}
		$width = imagesx($sourceImage);
		$height = imagesy($sourceImage);

		/* find the "desired height" of this thumbnail, relative to the desired width  */
		$desiredHeight = floor($height * ($desiredWidth / $width));

		/* create a new "virtual" image */
		$virtualImage = imagecreatetruecolor($desiredWidth, $desiredHeight);

		/* copy source image at a resized size */
		imagecopyresampled($virtualImage, $sourceImage, 0, 0, 0, 0, $desiredWidth, $desiredHeight, $width,
					 $height);

		$return = false;
		/* create the physical thumbnail image to its destination */
		switch (strtolower($extension)) {
			case 'jpeg':
			case 'jpg':
				$return = imagejpeg($virtualImage, $destination);
				break;
			case 'gif':
				$return = imagegif($virtualImage, $destination);
				break;
			case 'png':
				$return = imagepng($virtualImage, $destination);
				break;
		}

		return $return;
	}

	/**
	 * Resize all images in a directory
	 * 
	 * @param string $dir Directory path of images
	 * @param int $desiredWidth Desired width of new images
	 * @param boolean $overwrite Overwrite old images?
	 * @param boolean $recursive Indicates if subdirectories should be searched too
	 * @param string $subDir If not overwrite, name of subfolder within the $dir
	 *  to resize into
	 */
	public static function resizeImageDirectory($dir, $desiredWidth = 200, $overwrite = false,
											 $recursive = true, $subDir = 'resized') {
		foreach (self::readDir($dir, self::FILES_ONLY, $recursive, null, true) as $path => $filesArray) {
			foreach ($filesArray as $file) {
				$destination = null;
				if (!$overwrite) {
					if (!is_dir($path . $subDir)) {
						mkdir($path . $subDir);
					}
					$destination = $path . $subDir . DIRECTORY_SEPARATOR . $file;
				}
				self::resizeImage($path . $file, $desiredWidth, $destination);
			}
		}
	}

	/**
	 * Shortens a string to desired length
	 * @param string $str String to shorten
	 * @param integer $length Length of the string to return
	 * @param string $break String to replace the truncated part with
	 * @return string
	 */
	public static function shortenString($str, $length = 75, $break = '...') {
		if (strlen($str) < $length) return $str;

		$str = strip_tags($str);

		return substr($str, 0, $length) . $break;
	}

	/**
	 * Make each value in the array an array
	 * @param array $array
	 * @return array
	 */
	public static function makeValuesArray(array &$array) {
		foreach ($array as &$value) {
			if (!is_array($value)) $value = array($value);
		}
		return $array;
	}

	/**
	 * Uploads file(s) to the server
	 * @param \Object $data Files to upload
	 * @param array $options Keys include [(string) path, (int) maxSize, 
	 * (array) extensions - in lower case, (array) ignore, (string) prefix]
	 * @return boolean|string
	 */
	public static function uploadFiles(\Object $data, array $options = array()) {
		$return = array('success' => array(), 'errors' => array());
		foreach ($data->toArray(TRUE) as $ppt => $info) {
			if (is_array($options['ignore']) && in_array($ppt, $options['ignore'])) continue;
			self::makeValuesArray($info);

			foreach ($info['name'] as $key => $name) {
				if ($info['error'][$key] !== UPLOAD_ERR_OK) {
					$return['errors'][$ppt][$name] = self::UPLOAD_ERROR_NO_FILE;
					continue;
				}

				if (isset($options['maxSize'][$key]) && $info['size'] > $options['maxSize'][$key]) {
					$return['errors'][$ppt][$name] = self::UPLOAD_ERROR_SIZE;
					continue;
				}

				$tmpName = $info['tmp_name'][$key];
				$pInfo = pathinfo($name);
				if (isset($options['extensions']) && !in_array(strtolower($pInfo['extension']),
															  $options['extensions'])) {
					$return['errors'][$ppt][$name] = self::UPLOAD_ERROR_EXTENSION;
					continue;
				}
				$dir = isset($options['path']) ? $options['path'] : DATA . 'uploads';
				if (substr($dir, strlen($dir) - 1) !== DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;
				if (!is_dir($dir)) {
					if (!mkdir($dir, 0777, true)) {
						$return['errors'][$ppt][$name] = self::UPLOAD_ERROR_PATH;
						continue;
					}
				}
				$savePath = $dir . $options['prefix'] . preg_replace('/[^A-Z0-9._-]/i', '_',
														 basename($pInfo['filename'])) . '.' . $pInfo['extension'];
				if (move_uploaded_file($tmpName, $savePath)) {
					$return['success'][$ppt][$name] = str_replace('\\', '/', $savePath);
					self::$uploadSuccess = $savePath;
				}
				else {
					$return['errors'][$ppt][$name] = self::UPLOAD_ERROR_FAILED;
				}
			}
		}
		return $return;
	}

	/**
	 * Generates a random password
	 * @param int $length Length of the password to generate. Default is 8
	 * @return string
	 */
	public static function randomPassword($length = 8, $string = null) {
		if (!$string) $string = 'bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ123456789&^%$#@!_-+=';
		$chars = str_split(str_shuffle($string));
		$password = '';
		foreach (array_rand($chars, $length) as $key) {
			$password .= $chars[$key];
		}
		return $password;
	}

	/**
	 * Updates a configuration file
	 * @param string $path Full path to the configuration file
	 * @param array $data Array of data to add/change in the config
	 * @param boolean $overwrite Inidicates whether to overwrite existing data
	 * @param boolean $recursiveMerge Indicates whether the data should be merge deeply
	 * @return boolean
	 */
	public static function updateConfig($path, array $data = array(), $overwrite = false,
									 $recursiveMerge = true) {
		$oldData = array();
		if (!$overwrite && is_readable($path)) $oldData = include $path;
		$config = ($recursiveMerge) ?
				array_merge_recursive($oldData, $data) :
				array_merge($oldData, $data);
		$content = str_replace(array("=> \n"), array('=>'), var_export($config, true));
		return FALSE !== file_put_contents($path, '<' . '?php' . "\r\n\treturn " . $content . ';');
	}

	/**
	 * Send an array or object to javascript
	 * @param object|array $value
	 * @return string
	 * @usage a = <?= Util::toJavascriptArray($array|$object) ?>
	 */
	public static function toJavascriptArray($value) {
		return json_encode($value);
	}

	/**
	 * Create a list of time zones
	 * @return array
	 */
	public static function listTimezones() {
		if (self::$timezones === null) {
			self::$timezones = array();
			$offsets = array();
			$now = new DateTime();

			foreach (DateTimeZone::listIdentifiers() as $timezone) {
				$now->setTimezone(new DateTimeZone($timezone));
				$offsets[] = $offset = $now->getOffset();
				self::$timezones[$timezone] = '(' . self::formatGmtOffset($offset) . ') ' . self::formatTimezoneName($timezone);
			}

			array_multisort($offsets, self::$timezones);
		}

		return self::$timezones;
	}

	/**
	 * Formats GMT offset to string
	 * @param string $offset
	 * @return string
	 */
	public static function formatGmtOffset($offset) {
		$hours = intval($offset / 3600);
		$minutes = abs(intval($offset % 3600 / 60));
		return 'GMT' . ($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');
	}

	/**
	 * Formats time zone name to a more presentable format
	 * @param string $name
	 * @return string
	 */
	public static function formatTimezoneName($name) {
		return str_replace(array('/', '_', 'St'), array(',', ' ', 'St.'), $name);
	}

	/**
	 * Search an array to see if it has the expected value [at the given key]
	 * @param array $array
	 * @param mixed $value Could be an array of values
	 * @param bool $recursive
	 * @param mixed $key Could be an array of keys
	 * @param bool $multiple Indicates whether to return all found arrays or just one - the first
	 * @return array|null The array containing the expected value
	 */
	public static function searchArray(array $array, $value, $recursive = false, $key = array(),
									$multiple = false) {
		if (!is_array($value)) $value = array($value);
		if ($key !== null && !is_array($key)) $key = array($key);
		else if ($key === null) $key = array();
		$found = array();
		foreach ($array as $ky => $val) {
			if (is_array($val)) {
				if (!$recursive) continue;
				$ret = static::searchArray($val, $value, $recursive, $key, $multiple);
				if (count($ret)) {
					if ($multiple) $found = array_merge($found, $ret);
					else return $ret;
				}
				continue;
			}
			if (count($key) && !in_array($ky, $key)) continue;

			$keys = array_flip($key);
			if ($val === $value[$keys[$ky]]) {
				if ($multiple) {
					$k = ($multiple === true) ? 0 : $array[$multiple];
					$found[$k] = $array;
				}
				else return $array;
			}
		}

		return $found;
	}

	/**
	 *
	 * @param string|int $date1
	 * @param string|int $date2
	 * @param int $return 1 - years, 2 - months, 3 - days
	 * @return int|float|string
	 */
	public static function dateDiff($date1, $date2, $return = 2) {
		$date1 = is_int($date1) ? $date1 : strtotime($date1);
		$date2 = is_int($date2) ? $date2 : strtotime($date2);
		$diff = abs($date1 - $date2);
		switch ($return) {
			case 1:
				return floor($diff / (60 * 60 * 24 * 365));
			case 2:
				return floor($diff / (60 * 60 * 24 * 30));
			case 3:
				return floor($diff / (60 * 60 * 24));
		}
	}

	public static function parseAttrArray(array $array, array $ignoreAttrs = array()) {
		$return = '';
		foreach ($array as $attr => $val) {
			if (in_array($attr, $ignoreAttrs)) continue;
			if (!empty($return)) $return .= ' ';
			$return .= $attr . '="' . $val . '"';
		}
		return $return;
	}

	/**
	 * Fetches the differences in the given arrays recursively
	 * @param array $array1 The first array
	 * @param array $array2 The second array
	 * @param bool $binary Indicates whether to compare the values binarywise or not
	 * @return array
	 */
	public static function arrayDiff(array $array1, array $array2, $binary = false) {
		$diff = array();
		foreach ($array1 as $key => $val) {
			if (!array_key_exists($val, $array2)) {
				$diff[$key] = $val;
				continue;
			}
			else if (is_array($val)) {
				$diff = array_merge($diff, self::arrayDiff($val, $array2[$key], $binary));
			}
			else if (($binary && $val !== $array2[$key]) || (!$binary && $val != $array2[$key]))
					$diff[$key] = $val;
			unset($array2[$key]);
		}
		return array_merge($diff, $array2);
	}

}
