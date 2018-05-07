<?php

namespace TAO;

/**
 * Class Lang
 * @package TAO
 */
class Lang
{
	/**
	 * @var array
	 */
	static $data = array();

	/**
	 * @param $name
	 * @param string $domain
	 * @param bool|false $lang
	 * @return string
	 */
	public static function t($name, $domain = 'messages', $lang = false)
	{
		if (!$lang) {
			$lang = \TAO::getCurrentLang();
		}
		$key = "{$domain}.{$lang}";
		if (!isset(self::$data[$key])) {
			self::$data[$key] = array();
			$file = "lang/{$key}.php";
			self::mergeLangData(self::$data[$key], \TAO::taoDir($file));
			foreach (\TAO::bundles() as $bundle) {
				self::mergeLangData(self::$data[$key], $bundle->filePath($file));
			}
			self::mergeLangData(self::$data[$key], \TAO::localDir($file));
		}
		if (isset(self::$data[$key][$name])) {
			return self::$data[$key][$name];
		} else {
			if (\TAO::isDebugMode()) {
				return "[lang:{$domain}/{$lang}/{$name}]";
			} else {
				return $name;
			}
		}
	}

	/**
	 * @param $data
	 * @param $path
	 */
	protected static function mergeLangData(&$data, $path)
	{
		if (is_file($path)) {
			$messages = include($path);
			$data = \TAO::mergeArgs($data, $messages);
		}
	}
}