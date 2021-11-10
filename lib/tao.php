<?php
spl_autoload_register(array('\TAO', 'autoload'));
\CModule::IncludeModule("iblock");

\TAO::load('type');
\TAO::load('infoblock');
\TAO::load('HighloadBlockRepository');
\TAO::load('HighloadBlock');
\TAO::load('HBEntity');
\TAO::load('cache');
\TAO::load('entity');
\TAO::load('infoblock_handlers');
\TAO::load('tables_schema');
\TAO::load('urls');
\TAO::load('auth');
\TAO::load('short_call_functions');
\TAO::load('Events');
\TAO::load('cli');
\TAO::load('ExceptionHandler');
\TAO::load('Vars');

/**
 * Class TAO
 * @package TAO
 */
class TAO
{
	/**
	 * @var array
	 */
	static $config = array(
		'check_schema' => true,
		'admin_menu_export' => true,
		'pager_class' => '\\TAO\\Pager',
		'navigation_class' => '\\TAO\\Navigation',
		'search_class' => '\\TAO\\Search',
		'fs_pages' => true,
		'elements' => true,
		'less_cache' => 'cache/less',
	);

	/**
	 * @var array
	 */
	static $globals = array();

	/**
	 * @var array
	 */
	static $infoblocks = array();

	/**
	 * @var array
	 */
	static $i2code = array();

	/**
	 * @var array
	 */
	static $bundles = array();

	/**
	 * @var string
	 */
	static $layout = 'work';

	/**
	 * @var \TAO\Environment
	 */
	public static $env;

	/**
	 * @var \TAO\Frontend[]
	 */
	public static $frontends = array();

	/**
	 * @var array
	 */
	public static $routes = array();

	/**
	 * @var bool
	 */
	public static $compositeContent = false;

	/**
	 * @var bool
	 */
	public static $compositeStub = false;

	/**
	 * @var bool
	 */
	protected static $forcedLang = false;

	protected static $normalizedCodes = array();

	/**
	 * @return \CMain
	 */
	public static function app()
	{
		return $GLOBALS['APPLICATION'];
	}

	/**
	 * @param $re
	 * @param $data
	 */
	public static function addRoute($re, $data)
	{
		self::$routes[$re] = $data;
	}

	/**
	 * Возвращает данные сайта по ID (для многосайтовых конфигурация)
	 *
	 * Если ID сайта не передан, то возвращает данные текущего сайта
	 *
	 * @param bool|false $id
	 * @return mixed
	 */
	public static function getSiteData($id = false)
	{
		static $data = array();

		if (!$id) {
			$id = SITE_ID;
		}

		if (!isset($data[$id])) {
			$res = \CSite::GetByID($id);
			$data[$id] = $res->Fetch();
		}

		return $data[$id];
	}

	/**
	 * Возвращает код языка сайта
	 *
	 * Если ID сайта не передан, то возвращает язык текущего сайта
	 *
	 * @param bool|false $id
	 * @return mixed
	 */
	public static function getSiteLang($id = false)
	{
		$data = self::getSiteData($id);
		return $data['LANGUAGE_ID'];
	}

	/**
	 * @param $id
	 */
	public static function setCurrentLang($id)
	{
		self::$forcedLang = $id;
	}

	/**
	 * @return bool|mixed
	 */
	public static function getCurrentLang()
	{
		if (self::$forcedLang) {
			return self::$forcedLang;
		}

		if (self::inAdmin()) {
			return 'ru';
		}
		return self::getSiteLang();
	}


	/**
	 * Возвращает данные по коду языка
	 *
	 * Если код языка не передан, то возвращает данные по языку текущего сайта
	 *
	 * @param bool|false $id
	 * @return mixed
	 */
	public static function getLangData($id = false)
	{
		static $data = array();

		if (!$id) {
			$id = self::getSiteLang();
		}

		if (!isset($data[$id])) {
			$res = \CLanguage::GetByID($id);
			$data[$id] = $res->Fetch();
		}

		return $data[$id];
	}

	/**
	 * Проверка нахождения в админской зоне
	 *
	 * @return bool
	 */
	public static function inAdmin()
	{
		return defined("ADMIN_SECTION") && ADMIN_SECTION === true && preg_match('{^/bitrix/admin/}', \Bitrix\Main\Context::getCurrent()->getRequest()->getRequestUri());
	}

	/**
	 * Добавляет бандл
	 *
	 * @param $name
	 * @return mixed
	 * @throws TAOBundleNotFoundException
	 */
	public static function addBundle($name)
	{
		$bundle = \TAO\Bundle::findBundle($name);
		if ($bundle) {
			self::$bundles[$name] = $bundle;
			return $bundle;
		} else {
			throw new TAOBundleNotFoundException("Bundle {$name} not found");
		}
	}

	/**
	 * @throws TAOBundleNotFoundException
	 */
	public static function addBundles()
	{
		foreach (func_get_args() as $name) {
			self::addBundle($name);
		}
	}

	/**
	 * @return array
	 */
	public static function bundles()
	{
		return self::$bundles;
	}

	/**
	 * @param $name
	 * @return mixed
	 * @throws TAOBundleNotFoundException
	 */
	public static function bundle($name)
	{
		if (isset(self::$bundles[$name])) {
			return self::$bundles[$name];
		}
		return self::addBundle($name);
	}

	/**
	 * @return bool
	 */
	public static function cache()
	{
		return TAO\Cache::instance();
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public static function navigation($name = 'navigation')
	{
		static $navigation = array();
		if (!isset($navigation[$name])) {
			$class = self::$config['navigation_class'];
			return $navigation[$name] = new $class($name);
		}
		return $navigation[$name];
	}

	/**
	 * @return mixed
	 */
	public static function search()
	{
		static $search = null;
		if (empty($search)) {
			$class = self::$config['search_class'];
			return $search = new $class();
		}
		return $search;
	}

	/**
	 * @param string $page
	 * @return mixed
	 */
	public static function pager($page = 'page')
	{
		$pager = new self::$config['pager_class'];
		if (is_callable($page)) {
			$pager->setCallback($page);
		} else {
			$pager->setVar($page);
		}
		return $pager;
	}

	/**
	 * @param $title
	 */
	public static function setTitle($title)
	{
		self::app()->SetTitle($title);
	}

	/**
	 * @param $class
	 * @return bool|string
	 * @throws TAOException
	 * @throws TAOInfoblockCacheException
	 */
	public static function getClassFile($class)
	{
		$class = ltrim($class, '\\');
		if (preg_match('{^TAO\\\\CachedInfoblock\\\\([^\\\\]+)$}', $class, $m)) {
			$fname = $m[1];
			$name = $fname;
			if (isset(self::$normalizedCodes[$name])) {
				$name = self::$normalizedCodes[$name];
			}
			$path = self::localDir("cache/infoblock/{$fname}.php");
			if (!is_file($path)) {
				$id = self::getInfoblockId($name);
				if (!$id) {
					return false;
				}
				$content = \TAO\InfoblockExport::run($id, true);
				if (!$content) {
					throw new TAOInfoblockCacheException("Error generate cache class for infoblock '{$name}'");
				}
				$dir = dirname($path);
				if (!is_dir($dir)) {
					mkdir($dir, 0777, true);
					if (!is_dir($dir)) {
						throw new TAOException("Can't create dir {$dir}");
					}
				}
				file_put_contents($path, $content);
				if (!is_file($path)) {
					throw new TAOException("Can't save file {$path}");
				}
			}
			return $path;
		} elseif (preg_match('{^App\\\\Forms\\\\([^\\\\]+)$}', $class, $m)) {
			$name = $m[1];
		} elseif (preg_match('{^TAO\\\\PropertyContainer\\\\([^\\\\]+)$}', $class, $m)) {
			$name = $m[1];
			return self::taoDir("lib/PropertyContainer/{$name}.php");
		} elseif (preg_match('{^TAO\\\\UField\\\\([^\\\\]+)$}', $class, $m)) {
			$name = $m[1];
			return self::taoDir("lib/UField/{$name}.php");
		} elseif (preg_match('{^App\\\\UField\\\\([^\\\\]+)$}', $class, $m)) {
			$name = $m[1];
			return self::localDir("lib/UField/{$name}.php");
		} elseif (preg_match('{^TAO\\\\CLI\\\\([^\\\\]+)$}', $class, $m)) {
			$name = $m[1];
			return self::taoDir("lib/CLI/{$name}.php");
		} elseif (preg_match('{^TAO\\\\Bundle\\\\([^\\\\]+)\\\\(.+)$}', $class, $m)) {
			$bundle = $m[1];
			$name = str_replace('\\', '/', $m[2]);
			$path = self::taoDir("bundles/{$bundle}/lib/{$name}.php");
			return $path;
		} elseif (preg_match('{^App\\\\Bundle\\\\([^\\\\]+)\\\\(.+)$}', $class, $m)) {
			$bundle = $m[1];
			$name = str_replace('\\', '/', $m[2]);
			$path = self::localDir("bundles/{$bundle}/lib/{$name}.php");
			return $path;
		} elseif (preg_match('{^TAO\\\\([^\\\\]+)$}', $class, $m)) {
			$name = self::unchunkCap($m[1]);
			return self::taoDir("lib/{$name}.php");
		} elseif (preg_match('{^App\\\\(.+)$}', $class, $m)) {
			$name = str_replace('\\', '/', $m[1]);
			return self::localDir("lib/{$name}.php");
		} elseif ($class == 'TAO\CLI') {
			return self::taoDir("lib/cli.php");
		}
		return false;
	}

	/**
	 * @param $class
	 * @throws TAOException
	 * @throws TAOInfoblockCacheException
	 */
	public static function autoload($class)
	{
		$file = self::getClassFile($class);
		if ($file && is_file($file)) {
			include_once($file);
		}
	}

	/**
	 * @param        $class
	 * @param string $method
	 * @return mixed
	 * @throws TAOException
	 * @throws TAOInfoblockCacheException
	 */
	public static function cachedRun($class, $method = 'run')
	{
		$path = self::getClassFile($class);
		if (is_file($path) && self::cache()->fileUpdated($path)) {
			include_once($path);
			return call_user_func(array($class, $method));
		}
	}

	/**
	 * @param $dirs
	 * @param $file
	 * @param bool|false $extra
	 * @return bool|string
	 */
	public static function filePath($dirs, $file, $extra = false)
	{
		static $paths = array();
		$cache = \TAO::getOption('cache.template.paths');

		$key = md5(serialize(func_get_args()));
		if ($cache && !is_int($cache)) {
			$cache = 3600;
		}
		if ($cache) {
			$key = "tplpath/{$key}";
			$path = \TAO::cache()->get($key, $cache);
			if ($path) {
				return $path;
			}
		}

		if (isset($paths[$key])) {
			return $paths[$key];
		}
		if (preg_match('{^(.+)\.(css|js|phtml|less|scss)$}', $file, $m)) {
			$base = $m[1];
			$ext = $m[2];
			$site = SITE_ID;

			$files = $extra ? array("{$base}-{$extra}-{$site}.{$ext}", "{$base}-{$extra}.{$ext}") : array();
			$files[] = "{$base}-{$site}.{$ext}";
			$files[] = "{$base}.{$ext}";

			foreach ($files as $fn) {
				foreach ($dirs as $dir) {
					$path = "{$dir}/{$fn}";
					if (is_file($path)) {
						$paths[$key] = $path;
						if ($cache) {
							\TAO::cache()->set($key, $path, $cache);
						}
						return $path;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @param $dirs
	 * @param $file
	 * @param bool|false $extra
	 * @return bool|string
	 */
	public static function fileUrl($dirs, $file, $extra = false)
	{
		$path = self::filePath($dirs, $file, $extra);
		if ($path) {
			return '/' . ltrim(substr($path, strlen($_SERVER['DOCUMENT_ROOT'])), '/');
		}
		return false;
	}

	/**
	 * Возвращает доменное имя сайта
	 *
	 * @return string
	 * @throws Exception
	 */
	static function site_host()
	{
		if (isset($_SERVER['HTTP_HOST'])) {
			return $_SERVER['HTTP_HOST'];
		}

		throw new Exception('host не задан');
	}

	/**
	 * Возвращает http протокол сайта
	 *
	 * @return string
	 */
	static function site_protocol()
	{
		if (isset($_SERVER['HTTPS'])) {
			return 'https';
		}
		return 'http';
	}

	/**
	 * Добавляет к url http протокол и доменное имя сайта, если таковых еще нет, или перезаписывает их, если указаны аргументы $host и $protocol
	 *
	 * @param string       $url
	 * @param false|string $host
	 * @param false|string $protocol
	 *
	 * @return string
	 * @throws Exception
	 */
	static function full_url($url, $host = false, $protocol = false)
	{
		$url_chunks = parse_url($url);
		if ($host) {
			$url_chunks['host'] = $host;
		}
		if ($protocol) {
			$url_chunks['scheme'] = $protocol;
		}

		if (!isset($url_chunks['scheme'])) {
			$url_chunks['scheme'] = self::site_protocol();
		}
		if (!isset($url_chunks['host'])) {
			$url_chunks['host'] = self::site_host();
		}
		return self::build_url($url_chunks);
	}

	static function build_url($url_chunks)
	{
		if (!isset($url_chunks['path'])) {
			$url_chunks['path'] = '/';
		}

		$url = $url_chunks['scheme'] . '://';

		if ($url_chunks['user'] && $url_chunks['pass']) {
			$url .= $url_chunks['user'] . ':' . $url_chunks['pass'] . '@';
		}

		$url .= $url_chunks['host'];

		if ($url_chunks['port']) {
			$url .= ':' . $url_chunks['port'];
		}

		$url .= '/' . ltrim($url_chunks['path'], '/');

		if ($url_chunks['query']) {
			$url .= '?' . $url_chunks['query'];
		}

		if ($url_chunks['fragment']) {
			$url .= '#' . $url_chunks['fragment'];
		}

		return $url;
	}


	/**
	 * @param array $filter
	 * @return array
	 */
	public static function getLangs($filter = array())
	{
		$out = array();
		$result = CLanguage::GetList($by = 'SORT', $order = 'asc', $filter);
		while ($row = $result->Fetch()) {
			$id = $row['LID'];
			$out[$id] = $row;
		}
		return $out;
	}

	/**
	 * @param $from
	 * @param $to
	 */
	public static function symlink($from, $to)
	{
		if (is_link($to)) {
			return;
		}
		if (is_dir($to)) {
			DeleteDirFilesEx($to);
		}
		if (is_file($to)) {
			unlink($to);
		}
		symlink($from, $to);
		chmod($to, 0777);
	}

	/**
	 * @param bool|false $sub
	 * @return string
	 */
	public static function rootDir($sub = false)
	{
		$dir = $_SERVER['DOCUMENT_ROOT'];
		if ($sub) {
			$sub = trim($sub, '/');
			$dir .= "/{$sub}";
		}
		return $dir;
	}

	/**
	 * @param bool $sub
	 * @return string
	 */
	public static function taoDir($sub = false)
	{
		$dir = $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/techart/bitrix.tao';
		if ($sub) {
			$sub = trim($sub, '/');
			$dir .= "/{$sub}";
		}
		return $dir;
	}

	/**
	 * @param bool $sub
	 * @return string
	 */
	public static function localDir($sub = false)
	{
		$dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/local';
		if ($sub) {
			$sub = trim($sub, '/');
			$dir .= "/{$sub}";
		}
		return $dir;
	}

	/**
	 * @param $class
	 */
	public static function load($class)
	{
		$path = self::taoDir() . "/lib/{$class}.php";
		include_once($path);
	}

	/**
	 *
	 */
	public static function initAdmin()
	{
		AddEventHandler("main", "OnBuildGlobalMenu", function (&$admin, &$module) {
			global $USER;
			if (!$USER->IsAdmin()) {
				return;
			}
			if (\TAO::$config['admin_menu_export']) {
				$module[] = array(
					'parent_menu' => 'global_menu_settings',
					'section' => 'TAO',
					'sort' => 5000,
					'url' => '/bitrix/admin/tao.php',
					'text' => 'TAO',
					"icon" => "iblock_menu_icon",
					"page_icon" => "iblock_page_icon",
					'items_id' => 'tao',
				);
			}
		});
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public static function setOption($name, $value = true)
	{
		self::$config[$name] = $value;
	}

	/**
	 * @param       $name
	 * @param mixed $default
	 * @return null
	 */
	public static function getOption($name, $default = null)
	{
		return isset(self::$config[$name]) ? self::$config[$name] : $default;
	}

	public static function isDebugMode() {
		$debug = self::getOption('debug');
		if (is_null($debug)) {
			$bitrixConfig = \Bitrix\Main\Config\Configuration::getInstance();
			$debug = $bitrixConfig['exception_handling']['debug'];
		}
		return $debug;
	}

	/**
	 * @return array
	 */
	public static function getOptions()
	{
		return self::$config;
	}

	/**
	 * @param array $cfg
	 * @throws TAOBundleNotFoundException
	 */
	public static function init($cfg = array())
	{
		foreach ($cfg as $k => $v) {
			self::$config[$k] = $v;
		}

		if (isset($GLOBALS['TAO_INITED'])) {
			return;
		}
		$GLOBALS['TAO_INITED'] = true;

		$cfgFile = \TAO::localDir('.config.php');
		if (is_file($cfgFile)) {
			$extraConfig = include($cfgFile);
			self::$config = \TAO::mergeArgs(self::$config, $extraConfig);
		}

		self::initAdmin();

		if (self::$config['fs_pages']) {
			self::addBundle('FSPages');
		}

		if (self::$config['elements']) {
			self::addBundle('Elements');
		}

		\TAO\Auth::init();

		self::setBitrixSettings();

		AddEventHandler("main", "OnBeforeProlog", function () {
		});

		AddEventHandler(
			'main',
			'OnBeforeResizeImage',
			array(\TAO\ImageProcessing::class, 'OnBeforeResizeImageHandler')
		);
		AddEventHandler(
			'main',
			'OnAfterResizeImage',
			array(\TAO\ImageProcessing::class, 'OnAfterResizeImageHandler')
		);
	}

	private static function setBitrixSettings() {
		$bitrixExceptionHandler = \Bitrix\Main\Application::getInstance()->getExceptionHandler();
		$exceptionHandler = \Tao\ExceptionHandler::instance();
		$bitrixExceptionHandler->setHandlerLog($exceptionHandler);

		$errorsNotifier = self::getOption('errors_notifier');
		if (isset($errorsNotifier['error_types']) && is_int($errorsNotifier['error_types'])) {
			$bitrixExceptionHandler->setHandledErrorsTypes($errorsNotifier['error_types']);
			$bitrixExceptionHandler->setExceptionErrorsTypes($errorsNotifier['error_types']);
		}

		$debugMode = self::getOption('debug');
		if (!is_null($debugMode)) {
			$bitrixExceptionHandler->setDebugMode($debugMode);
		}
	}

	/**
	 * @param $name
	 * @param string $domain
	 * @param bool|false $lang
	 * @return string
	 */
	public static function t($name, $domain = 'messages', $lang = false)
	{
		return \TAO\Lang::t($name, $domain, $lang);
	}

	/**
	 *
	 */
	public static function CLI()
	{
		\TAO\CLI::run();
	}

	/**
	 * @param string $path
	 * @return \TAO\Sitemap
	 */
	public static function sitemap($path = '')
	{
		return new \TAO\Sitemap($path);
	}

	/**
	 * @param $code
	 * @return \TAO\Infoblock|null
	 */
	public static function getInfoblock($code)
	{
		if (is_numeric($code)) {
			$code = self::getInfoblockCode($code);
			if (empty($code)) {
				return null;
			}
		}

		if (!isset(self::$infoblocks[$code])) {
			$name = \TAO\Infoblock::getClassName($code);
			$e = new $name($code);
			self::$infoblocks[$code] = $e;
			foreach ($e->urls() as $mode => $data) {
				if (isset($data['default']) && isset($data['page'])) {
					$url = $data['default'];
					$re = '{^' . str_replace('{id}', '(?<id>\d+)', $url) . '$}';
					\TAO\Urls::addDefaultUrl($re, array(
						'infoblock' => $code,
						'mode' => $mode,
						'default_url' => $url,
						'page' => $data['page'],
					));
				}
			}
		}
		return self::$infoblocks[$code];
	}

	/**
	 * @param $code
	 * @return \TAO\Infoblock|null
	 */
	public static function infoblock($code)
	{
		return self::getInfoblock($code);
	}

	/**
	 * @param $code
	 * @return bool
	 */
	public static function getInfoblockId($code)
	{
		return \TAO\Infoblock::codeToId($code);
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public static function getInfoblockCode($id)
	{
		if (!isset(self::$i2code[$id])) {
			$o = new \CIBlock();
			$res = $o->GetList(array(), array('ID' => $id, 'CHECK_PERMISSIONS' => false));
			$code = false;
			while ($row = $res->Fetch()) {
				$code = $row['CODE'];
			}
			if ($code) {
				self::$i2code[$id] = $code;
			}
		}
		return self::$i2code[$id];
	}

	public static function highloadblock($code)
	{
		return \TAO\HighloadBlockRepository::get($code);
	}

	/**
	 * @param $code
	 * @param $class
	 */
	public static function setEntityClass($code, $class)
	{
		\TAO\Infoblock::setEntityClass($code, $class);
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public static function normalizeMnemocode($name)
	{
		if (preg_match('{^[a-z_][a-z0-9_]+$}i', $name)) {
			return $name;
		}
		$norm = 'c' . md5($name);
		self::$normalizedCodes[$norm] = $name;
		return $norm;
	}

	/**
	 * @param $name
	 * @return string
	 */
	public static function chunkCap($name)
	{
		$s = '';
		$name = self::normalizeMnemocode($name);
		foreach (explode('_', $name) as $chunk) {
			$s .= ucfirst(strtolower(trim($chunk)));
		}
		return $s;
	}

	/**
	 * @param $name
	 * @return string
	 */
	public static function unchunkCap($name)
	{
		$name = preg_replace('{([A-Z]+)}', '_\\1', $name);
		$name = preg_replace('{_+}', '_', $name);
		$name = trim($name, '_');
		$name = strtolower($name);
		return $name;
	}

	/**
	 * @return string
	 */
	public static function schemaDir()
	{
		return $_SERVER['DOCUMENT_ROOT'] . '/local/schema';
	}

	/**
	 * @param $style
	 * @return string
	 * @throws Exception
	 */
	public static function styleUrl($style)
	{
		if (strpos($style, '/') === false) {
			$style = self::filePath(
				array(
					self::localDir('styles'),
					self::taoDir('styles'),
				),
				$style
			);
		}
		if (preg_match('{\.less$}', $style)) {
			$path = self::rootDir($style);
			if (is_file($path)) {
				$options = array(
					'cache_dir' => self::localDir(self::getOption('less_cache')),
				);
				$css = \Less_Cache::Get(array($path => ''), $options);
				if ($css) {
					return '/local/' . self::getOption('less_cache') . '/' . $css;
				}
			}
		}
		return $style;
	}

	/**
	 * @param $style
	 * @throws Exception
	 */
	public static function useStyle($style)
	{
		$style = self::styleUrl($style);
		if ($style) {
			return self::app()->SetAdditionalCSS($style);
		}
	}

	/**
	 *
	 */
	public static function useStyles()
	{
		foreach (func_get_args() as $style) {
			self::useStyle($style);
		}
	}

	/**
	 * @param $script
	 */
	public static function useScript($script)
	{
		return self::app()->AddHeadScript($script);
	}

	/**
	 *
	 */
	public static function useScripts()
	{
		foreach (func_get_args() as $script) {
			self::useScript($script);
		}
	}

	/**
	 * @param $s
	 * @return mixed|string
	 */
	public static function translit($s, $space = '-')
	{
		$s = preg_replace('{&[a-z0-9#]+;}i', ' ', $s);
		$replace = array("А" => "A", "а" => "a", "Б" => "B", "б" => "b", "В" => "V", "в" => "v", "Г" => "G", "г" => "g", "Д" => "D", "д" => "d",
			"Е" => "E", "е" => "e", "Ё" => "E", "ё" => "e", "Ж" => "Zh", "ж" => "zh", "З" => "Z", "з" => "z", "И" => "I", "и" => "i",
			"Й" => "I", "й" => "i", "К" => "K", "к" => "k", "Л" => "L", "л" => "l", "М" => "M", "м" => "m", "Н" => "N", "н" => "n", "О" => "O", "о" => "o",
			"П" => "P", "п" => "p", "Р" => "R", "р" => "r", "С" => "S", "с" => "s", "Т" => "T", "т" => "t", "У" => "U", "у" => "u", "Ф" => "F", "ф" => "f",
			"Х" => "Kh", "х" => "kh", "Ц" => "Tc", "ц" => "tc", "Ч" => "Ch", "ч" => "ch", "Ш" => "Sh", "ш" => "sh", "Щ" => "Shch", "щ" => "shch",
			"Ы" => "Y", "ы" => "y", "Э" => "E", "э" => "e", "Ю" => "Iu", "ю" => "iu", "Я" => "Ia", "я" => "ia", "ъ" => "", "ь" => "");
		$s = strtr($s, $replace);
		$s = trim($s);
		$s = preg_replace('{[^a-z0-9]+}i', $space, $s);
		$s = trim($s, $space);
		return $s;
	}

	/**
	 * @param $m
	 */
	public static function sort(&$m)
	{
		uasort($m, function ($m1, $m2) {
			$sort1 = (is_array($m1) && isset($m1['SORT'])) ? (int)$m1['SORT'] : 500;
			$sort2 = (is_array($m2) && isset($m2['SORT'])) ? (int)$m2['SORT'] : 500;
			if ($sort1 < $sort2) {
				return -1;
			}
			if ($sort1 > $sort2) {
				return 1;
			}
			return 0;
		});
	}

	/**
	 * @param      $name
	 * @param bool $check
	 * @return \TAO\Form
	 */
	public static function form($name, $check = true)
	{
		return \TAO\Form::formObject($name, $check);
	}

	/**
	 * @return array|string
	 */
	public static function processForm()
	{
		return \TAO\Form::processPost();
	}

	/**
	 * @return string
	 */
	public static function renderForm()
	{
		return \TAO\Form::renderByParams();
	}

	/**
	 * @param $args
	 * @param $extra
	 * @return mixed
	 */
	public static function mergeArgs($args, $extra)
	{
		foreach ($extra as $k => $v) {
			if (!isset($args[$k])) {
				$args[$k] = $v;
			} else {
				if (is_array($args[$k]) && is_array($v)) {
					$args[$k] = self::mergeArgs($args[$k], $v);
				} else {
					$args[$k] = $v;
				}
			}
		}
		return $args;
	}

	/**
	 * @param string $time
	 * @return int
	 */
	public static function timestamp($time)
	{
		if (preg_match('{^\d+$}', $time)) {
			return $time;
		}
		if ($m = \ParseDateTime($time)) {
			return mktime($m['HH'], $m['MI'], $m['SS'], $m['MM'], $m['DD'], $m['YYYY']);
		}
		return time();
	}

	/**
	 * @param false|string $format
	 * @param false|string $time
	 * @return bool|int|string
	 */
	public static function date($format = false, $time = false)
	{
		$time = $time ? $time : time();
		$t = self::timestamp($time);
		return $format ? date($format, $t) : $t;
	}

	/**
	 * @param int $length
	 * @return string
	 */
	public static function randString($length = 6)
	{
		static $rs = false;
		static $count = 0;
		if (!$rs) {
			$count++;
			$seed = md5($_SERVER['HTTP_HOST'] . '/' . $_SERVER['REQUEST_URI']) . $count;
			$rs = new \Bitrix\Main\Type\RandomSequence($seed);
		}
		return $rs->randString($length);
	}

	/**
	 * @param bool|true $name
	 * @return \Bitrix\Main\Page\FrameHelper
	 */
	public static function compositeFrame($name = true)
	{
		$name = is_string($name) && strlen($name) > 0 ? $name : \TAO::randString();
		return new Bitrix\Main\Page\FrameHelper($name, true);
	}

	/**
	 * @param string $context
	 */
	public static function rejectComposite($context = '')
	{
		return \Bitrix\Main\Data\StaticHtmlCache::applyComponentFrameMode($context);
	}

	/**
	 * @param $var
	 * @return bool
	 */
	public static function isIterable(&$var)
	{
		return is_array($var) || $var instanceof Iterable || $var instanceof IteratorAggregate;
	}

	/**
	 * @return \TAO\Environment
	 */
	public static function env()
	{
		return \TAO\Environment::getInstance();
	}

	/**
	 * @return \TAO\Insertions
	 */
	public static function insertions()
	{
		return TAO\Insertions::instance();
	}

	/**
	 * @deprecated При таком подключении в публичной части сайта не выводится панель редактирования компонента
	 *
	 * @param $name
	 * @param $template
	 * @param array $params
	 * @param null $parent
	 * @param array $extraParams
	 * @return string
	 */
	public static function renderComponent($name, $template, $params = array(), $parent = null, $extraParams = array())
	{
		ob_start();
		\TAO::app()->IncludeComponent($name, $template, $params, $parent, $extraParams);
		return ob_get_clean();
	}

	/**
	 * @param $s
	 * @return bool
	 */
	public static function isUrlPrefix($s)
	{
		$url = $_SERVER['REQUEST_URI'];
		return strpos($url, $s) === 0;
	}

	/**
	 * Возвращает инстанс frontend'a
	 *
	 * @param string|false $pathToFrontend Если не указан ищется в текущем шаблоне (или в .default если не найден)
	 *                                      Можно указать имя шаблона в котором будет искаться frontend
	 *                                      Либо указывается путь до папки frontend
	 * @param array $resolverOptions Опции для PathResolver
	 * @return \TAO\Frontend
	 */
	public static function frontend($pathToFrontend = false, $resolverOptions = array())
	{
		$defaultPath = self::getOption('default_frontend_path') ?: false;
		$pathToFrontend = $pathToFrontend ?: $defaultPath;

		if (!$pathToFrontend) {
			$pathToFrontend = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . self::app()->GetTemplatePath('frontend');
		}

		if (!file_exists($pathToFrontend)) {
			$pathToFrontend = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . "/local/templates/{$pathToFrontend}/frontend";
		}

		$resolver = new \Techart\Frontend\PathResolver($pathToFrontend, array_merge(array(
			'twigCachePath' => dirname($pathToFrontend) . '/twig',
			'docRoot' => \TAO::getOption('docRoot') ?: '',
		), $resolverOptions));

		return self::$frontends[$pathToFrontend] ?: self::$frontends[$pathToFrontend] = new \TAO\Frontend(self::env(), $resolver);
	}

	/**
	 * @param $name
	 * @param bool|false $additional
	 */
	public static function frontendCss($name, $additional = false)
	{
		self::app()->AddHeadString(self::frontend()->cssTag($name), $additional);
	}

	/**
	 * @param $name
	 * @param bool|false $additional
	 */
	public static function frontendJs($name, $additional = false)
	{
		self::app()->AddHeadString(self::frontend()->jsTag($name), $additional);
	}

	/**
	 * @param $path
	 * @return string
	 */
	public static function frontendUrl($path)
	{
		return self::frontend()->url($path);
	}

	/**
	 * @return \TAO\Vars
	 * @throws \TAO\VarsException
	 */
	public static function vars()
	{
		return \TAO\Vars::getInstance();
	}
}


/**
 * Class TAOException
 */
class TAOException extends Exception
{
}

/**
 * Class TAONoTypeFileException
 */
class TAONoTypeFileException extends TAOException
{
}

/**
 * Class TAONoInfoblockFileException
 */
class TAONoInfoblockFileException extends TAOException
{
}

/**
 * Class TAOAddTypeException
 */
class TAOAddTypeException extends TAOException
{
}

/**
 * Class TAOUpdateTypeException
 */
class TAOUpdateTypeException extends TAOException
{
}

/**
 * Class TAOBundleNotFoundException
 */
class TAOBundleNotFoundException extends TAOException
{
}

class TAOInfoblockCacheException extends TAOException
{
}
