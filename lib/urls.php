<?php

namespace TAO;

/**
 * Class Urls
 * @package TAO
 */
class Urls
{
	/**
	 * @var array
	 */
	static $vars = array(/*
        'page' => array(
            'var' => 'page',
            'type' => 'int',
        ),
        'section' => array(
            'var' => 'section',
            'type' => 'int',
        ),
        */
	);

	/**
	 * @var array
	 */
	static $currentUrls = array();

	/**
	 * @var array
	 */
	static $noSendVars = array();
	/**
	 * @var array
	 */
	static $defaultUrls = array();

	/**
	 * @param $url
	 * @return string
	 */
	public static function clean($url)
	{
		$p = strpos($url, '?');
		if ($p > 0) {
			$url = substr($url, 0, $p);
		}
		return $url;
	}

	/**
	 * @param $regexp
	 * @param $data
	 */
	public static function addDefaultUrl($regexp, $data)
	{
		self::$defaultUrls[$regexp] = $data;
	}

	/**
	 * @param $url
	 */
	public static function addCurrentUrl($url)
	{
		$url = self::clean($url);
		self::$currentUrls[$url] = $url;
	}

	/**
	 * @param $url
	 * @return bool
	 */
	public static function isCurrent($url)
	{
		$url = self::clean($url);
		if (self::clean($_SERVER['REQUEST_URI']) == $url) {
			return true;
		}
		return isset(self::$currentUrls[$url]);
	}

	/**
	 * @param $url
	 * @return bool
	 */
	public static function isCurrentStartsWith($url)
	{
		$url = self::clean($url);
		if (strpos(self::clean($_SERVER['REQUEST_URI']), $url) === 0) {
			return true;
		}

		foreach (self::$currentUrls as $curl) {
			if (strpos($curl, $url) === 0) {
				return true;
			}
		}
	}

	/**
	 * @param $name
	 * @param bool|false $var
	 * @param bool|false $type
	 */
	public static function addVar($name, $var = false, $type = false)
	{
		$m = array(
			'var' => $var ? $var : $name,
			'type' => $type,
		);
		self::$vars[$name] = $m;
	}

	/**
	 * @param $uri
	 * @return bool|mixed|string
	 */
	public static function uriToFile($uri)
	{
		$path = $_SERVER['DOCUMENT_ROOT'] . $uri;
		if (is_file($path)) {
			return $path;
		}
		if (preg_match('{/$}', $path)) {
			$path1 = $path . 'index.php';
			if (is_file($path1)) {
				return $path1;
			}
			$path1 = preg_replace('{/$}', '.php', $path);
			if (is_file($path1)) {
				return $path1;
			}
		}
		return false;
	}

	/**
	 * @return mixed
	 */
	public static function cleanUri()
	{
		$uri = $_SERVER['REQUEST_URI'];
		if (preg_match('{^([^?]+)\?}', $uri, $m)) {
			$uri = $m[1];
		}
		return $uri;
	}

	/**
	 *
	 */
	public static function processVars()
	{
		$uri = self::cleanUri();
		foreach (self::$vars as $name => $data) {
			$var = $data['var'];
			$type = $data['type'];
			switch ($type) {
				case 'int':
					$re = "/{$name}-(\\d+)/";
					break;
				default:
					$re = "/{$name}-([^/]+)/";
			}
			if (preg_match('{' . $re . '}', $uri, $m)) {
				$uri = str_replace($m[0], '/', $uri);
				$_GET[$var] = $m[1];
			}
		}
		$path = self::uriToFile($uri);
		if ($path) {
			$_SERVER['SCRIPT_FILENAME'] = $path;
			$script = substr($path, strlen($_SERVER['DOCUMENT_ROOT']));
			$_SERVER['SCRIPT_NAME'] = $script;
			$_SERVER['PHP_SELF'] = $script;
			$_SERVER['DOCUMENT_URI'] = $script;
		}
		$query = trim(http_build_query($_GET));
		if ($query) {
			$uri .= "?$query";
		}
		$_SERVER['REQUEST_URI'] = $uri;
	}

	/**
	 * @param $id
	 * @param $infoblock
	 * @param $mode
	 * @param $page
	 * @param $path
	 */
	protected static function processElement($id, $infoblock, $mode, $page, $path)
	{
		$_SERVER['SCRIPT_FILENAME'] = $path;
		$script = substr($path, strlen($_SERVER['DOCUMENT_ROOT']));
		$_SERVER['SCRIPT_NAME'] = $script;
		$_SERVER['PHP_SELF'] = $script;
		$_SERVER['DOCUMENT_URI'] = $script;
		$_GET['id'] = $id;
		$_GET['mode'] = $mode;
		$_GET['infoblock'] = $infoblock;
		$query = trim(http_build_query($_GET));
		if ($query) {
			$page .= "?$query";
		}
	}

	/**
	 *
	 */
	public static function findPage()
	{
		global $DB;
		$uri = self::cleanUri();

		$site = SITE_ID;
		$uri = str_replace("'", '', $uri);
		$res = $DB->Query("SELECT * FROM tao_urls WHERE url='{$uri}' AND (site='' OR site='{$site}') ORDER BY time_update DESC LIMIT 1");
		while ($row = $res->Fetch()) {
			$id = $row['item_id'];
			$mode = $row['mode'];
			$infoblock = \TAO::getInfoblock($row['infoblock']);
			if ($infoblock) {
				$urls = $infoblock->urls();
				if (isset($urls[$mode])) {
					$data = $urls[$mode];
					var_dump($data);
					die;
					if (isset($data['page'])) {
						$page = $data['page'];
						$path = self::uriToFile($page);
						if ($path) {
							self::processElement($id, $infoblock->getMnemocode(), $mode, $page, $path);
							return;
						}
					}
				}
			}
		}

		foreach (self::$defaultUrls as $regexp => $data) {
			if (preg_match($regexp, $uri, $m)) {
				if (isset($m['id'])) {
					$id = $m['id'];
					$page = $data['page'];
					$path = self::uriToFile($page);
					if ($path) {
						self::processElement($id, $data['infoblock'], $data['mode'], $page, $path);
						return;
					}
				}
			}
		}
	}


	/**
	 * @param $url
	 * @param $var
	 * @param $page
	 * @return bool|string
	 */
	public static function pagerUrl($url, $var, $page)
	{
		$page = (int)$page;
		if ($page < 2) {
			$page = 1;
		}
		self::noSendVarValue($var, 1);
		return self::url($url, array($var => $page));
	}

	/**
	 * @param $url
	 * @param array $values
	 * @return bool|string
	 */
	public static function url($url, $values = array())
	{
		$url = self::replaceQuery($url, $values, true);
		return $url;
	}

	/**
	 * @param $var
	 * @param $value
	 */
	public static function noSendVarValue($var, $value)
	{
		self::$noSendVars[$var] = $value;
	}

	/**
	 * @param $url
	 * @param $values
	 * @param bool|false $transform
	 * @return bool|string
	 */
	public static function replaceQuery($url, $values, $transform = false)
	{
		$data = parse_url($url);
		$scheme = isset($data['scheme']) ? $data['scheme'] : 'http';
		$host = isset($data['host']) ? $data['host'] : false;
		$port = isset($data['port']) ? $data['port'] : false;
		$user = isset($data['user']) ? $data['user'] : false;
		$pass = isset($data['pass']) ? $data['pass'] : false;
		$path = isset($data['path']) ? $data['path'] : '/';
		$query = isset($data['query']) ? $data['query'] : '';
		$fragment = isset($data['fragment']) ? $data['fragment'] : false;

		parse_str($query, $qdata);

		foreach ($values as $key => $value) {
			$qdata[$key] = $value;
		}

		foreach (self::$noSendVars as $key => $value) {
			if (isset($qdata[$key]) && $qdata[$key] == $value) {
				unset($qdata[$key]);
			}
		}

		if ($path[strlen($path) - 1] != '/') {
			$transform = false;
		}

		if ($transform) {
			foreach (self::$vars as $name => $data) {
				$var = $data['var'];
				if (isset($qdata[$var])) {
					$value = $qdata[$var];
					unset($qdata[$var]);
					if ($value !== false) {
						if ($qdata['type'] == 'int') {
							$value = (int)$value;
						}
						$path .= "{$name}-{$value}/";
					}
				}
			}
		}

		$query = trim(http_build_query($qdata));

		$url = '';
		if ($host) {
			$url = $host;
			if ($user) {
				if ($pass) {
					$user .= ":{$pass}";
				}
				$url = "{$user}@{$url}";
			}
			$url = "{$scheme}://{$url}";
		}
		$url .= $path;
		if (!empty($query)) {
			$url .= "?{$query}";
		}
		if ($fragment) {
			$url .= "#{$fragment}";
		}
		return $url;
	}
}
