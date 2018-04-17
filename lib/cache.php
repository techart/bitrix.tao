<?php

namespace TAO;

/**
 * Class Cache
 * @package TAO
 */
class Cache
{
	/**
	 * @var bool
	 */
	static $instance = false;

	/**
	 * @return bool
	 */
	public static function instance()
	{
		if (!static::$instance) {
			static::$instance = new self();
		}
		return static::$instance;
	}

	/**
	 * @param $name
	 * @return array
	 */
	protected function dirAndName($name)
	{
		if (preg_match('{^(.+)/([^/]+)$}', $name, $m)) {
			return array('/tao/' . $m[1], $m[2]);
		}
		return array('/tao', $name);
	}

	/**
	 * @param $name
	 * @param int $time
	 * @return bool
	 */
	public function get($name, $time = 3600)
	{
		$cache = new \CPHPCache();
		list($dir, $name) = $this->dirAndName($name);
		if ($time > 0 && $cache->InitCache($time, $name, $dir)) {
			$vars = $cache->GetVars();
			return $vars['value'];
		}
		return false;
	}

	/**
	 * @param $name
	 * @param $value
	 * @param int $time
	 */
	public function set($name, $value, $time = 3600)
	{
		$cache = new \CPHPCache();
		list($dir, $name) = $this->dirAndName($name);
		$cache->InitCache($time, $name, $dir);
		$cache->Clean($name, $dir);
		$cache->StartDataCache();
		$cache->EndDataCache(array('value' => $value));
	}

	/**
	 * @param $path
	 * @return bool
	 */
	public function fileUpdated($path)
	{
		if (!is_file($path)) {
			return true;
		}
		$key = 'run/' . md5($path);
		$time = filemtime($path);
		$last = (int)$this->get($key);
		if ($last < $time) {
			$this->set($key, $time);
			return true;
		}
		return false;
	}
}
