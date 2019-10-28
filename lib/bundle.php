<?php

namespace TAO;

/**
 * Class Bundle
 * @package TAO
 */
class Bundle
{
	/**
	 * @var
	 */
	public $file;
	/**
	 * @var
	 */
	public $dir;
	/**
	 * @var
	 */
	public $name;
	/**
	 * @var
	 */
	public $route;
	/** @var ConfigReader[] */
	protected $options = array();

	/**
	 * @param $name
	 * @return mixed
	 */
	public static function findBundle($name)
	{
		$class = false;
		$path = \TAO::localDir("/bundles/{$name}/lib/Bundle.php");
		if (is_file($path)) {
			$class = "\\App\\Bundle\\{$name}\\Bundle";
			$dir = \TAO::localDir("/bundles/{$name}");
		} else {
			$path = \TAO::taoDir("/bundles/{$name}/lib/Bundle.php");
			if (is_file($path)) {
				$class = "\\TAO\\Bundle\\{$name}\\Bundle";
				$dir = \TAO::taoDir("/bundles/{$name}");
			}
		}

		if ($class) {
			include_once($path);
			$bundle = new $class();
			$bundle->name = $name;
			$bundle->file = $path;
			$bundle->dir = $dir;
			if (\TAO::cache()->fileUpdated($path)) {
				$bundle->cachedInit();
			}
			$bundle->init();
			return $bundle;
		}
	}

	/**
	 * @return mixed
	 */
	public static function routeBundles()
	{
		global $DB;
		$uri = $_SERVER['REQUEST_URI'];
		$p = strpos($uri, '?');
		if ($p > 0) {
			$uri = substr($uri, 0, $p);
		}
		foreach (\TAO::$routes as $re => $data) {
			$route = self::routeOne($uri, $re, $data);
			if (is_array($route)) {
				self::checkComposite($route);
				if (isset($route['element_of'])) {
					return self::dispatchElement($route);
				}
				if (isset($route['elements_of'])) {
					return self::dispatchElements($route);
				}
				if (isset($route['section_of'])) {
					return self::dispatchSection($route);
				}
				if (isset($route['sections_of'])) {
					return self::dispatchSections($route);
				}
			}
		}
		foreach (\TAO::$bundles as $name => $bundle) {
			$content = $bundle->routeStaticPage($uri);
			if (!empty($content)) {
				return $content;
			}
			$route = $bundle->route($uri);
			if (is_array($route)) {
				$rbundle = isset($route['bundle']) ? \TAO::bundle($route['bundle']) : $bundle;
				return $rbundle->dispatch($route);
			}
		}
		return self::routeElement($uri);
	}

	/**
	 * @param $uri
	 * @return bool
	 */
	public static function routeElement($uri)
	{
		global $DB;
		$site = SITE_ID;
		$uri = str_replace("'", '', $uri);
		$res = $DB->Query("SELECT * FROM tao_urls WHERE url='{$uri}' AND (site='' OR site='{$site}') ORDER BY time_update DESC LIMIT 1");
		while ($row = $res->Fetch()) {
			$id = $row['item_id'];
			$mode = $row['mode'];
			$code = $row['infoblock'];
			$infoblock = \TAO::getInfoblock($code);
			if ($infoblock) {
				$imode = \TAO::getOption("infoblock.{$code}.route_detail");
				if ($imode === true) {
					$imode = 'full';
				}
				$urls = $infoblock->urls();
				if ($mode == $imode || isset($urls[$mode])) {
					return self::dispatchElement(array('id' => $id, 'element_of' => $code, 'mode' => $mode));
				}
			}
		}
	}

	/**
	 *
	 */
	protected function myInfoblock()
	{
		foreach (func_get_args() as $name) {
			\TAO::setOption("infoblock.{$name}.bundle", $this);
		}
	}

	/**
	 *
	 */
	public function init()
	{

	}

	/**
	 *
	 */
	public function cachedInit()
	{

	}

	/**
	 * @return array
	 */
	public function routes()
	{
		return array();
	}

	/**
	 * @param $name
	 * @param string $optionsName
	 * @return null
	 */
	public function option($name, $optionsName = 'common')
	{
		return $this->options($optionsName)->get($name);
	}

	/**
	 * @param $uri
	 * @return bool|string
	 */
	public function routeStaticPage($uri)
	{
		if ($uri == '/index/') {
			return;
		}
		if ($uri == '/') {
			$uri = '/index/';
		}
		if (preg_match('{^/(.+)/$}', $uri, $m)) {
			$file = $m[1];
			$site = SITE_ID;
			$path = $this->staticPagePath(".{$site}/{$file}/index.phtml");
			if (!$path) {
				$path = $this->staticPagePath(".{$site}/{$file}.phtml");
			}
			if (!$path) {
				$path = $this->staticPagePath("{$file}/index.phtml");
			}
			if (!$path) {
				$path = $this->staticPagePath("{$file}.phtml");
			}
			if ($path) {
				ob_start();
				$app = $APPLICATION = \TAO::app();
				$bundle = $this;
				$r = include($path);
				$content = ob_get_clean();
				if ($content === '') {
					$content = ' ';
				}
				return $r === false ? false : $content;
			}
		}
	}

	/**
	 * @param $uri
	 * @return array
	 */
	public function route($uri)
	{
		foreach ($this->routes() as $re => $data) {
			$route = self::routeOne($uri, $re, $data);
			if (is_array($route)) {
				return $route;
			}
		}
	}

	/**
	 * @param $uri
	 * @param $re
	 * @param $data
	 * @return array
	 */
	public static function routeOne($uri, $re, $data)
	{
		if (preg_match($re, $uri, $m)) {
			$route = $data;
			array_walk_recursive($route, function (&$v, $k, $m) {
				if (is_string($v)) {
					foreach ($m as $n => $s) {
						$v = str_replace('{' . $n . '}', $s, $v);
					}
				}
			}, $m);

			if (isset($route['element_of']) || isset($route['elements_of']) || isset($route['section_of']) || isset($route['sections_of'])) {
				return $route;
			}
			if (!isset($route['controller'])) {
				$route['controller'] = 'Index';
			}
			if (preg_match('{^([^:]+):([^:]+)$}', $route['controller'], $m)) {
				$route['controller'] = trim($m[1]);
				$route['action'] = trim($m[2]);
			}
			if (!isset($route['action'])) {
				$route['action'] = 'index';
			}
			return $route;
		}
	}

	/**
	 * @param $file
	 * @return bool|string
	 */
	public function staticPagePath($file)
	{
		return $this->filePath("pages/{$file}");
	}

	/**
	 * @param $file
	 * @return string
	 */
	public function localPath($file)
	{
		$file = trim($file, '/');
		return \TAO::localDir("bundles/{$this->name}/{$file}");
	}

	/**
	 * @param $file
	 * @return string
	 */
	public function taoPath($file)
	{
		$file = trim($file, '/');
		return \TAO::taoDir("bundles/{$this->name}/{$file}");
	}

	/**
	 * @param $file
	 * @return bool|string
	 */
	public function filePath($file)
	{
		$path = $this->localPath($file);
		if (is_file($path)) {
			return $path;
		}
		$path = $this->taoPath($file);
		if (is_file($path)) {
			return $path;
		}
		return false;
	}

	/**
	 * @param $name
	 * @return string
	 */
	public function fileUrl($name)
	{
		$path = $this->filePath($name);
		if ($path) {
			return '/' . ltrim(substr($path, strlen($_SERVER['DOCUMENT_ROOT'])), '/');
		}
	}

	/**
	 * @param $name
	 */
	public function useStyle($name)
	{
		$url = $this->fileUrl("styles/{$name}");
		if ($url) {
			\TAO::useStyle($url);
		}
	}

	/**
	 * @param $name
	 */
	public function useScript($name)
	{
		$url = $this->fileUrl("scripts/{$name}");
		if ($url) {
			\TAO::useScript($url);
		}
	}

	/**
	 * @param $class
	 * @param bool|false $check
	 * @return bool|string
	 */
	public function className($class, $check = false)
	{
		$className = "\\App\\Bundle\\{$this->name}\\{$class}";
		$file = \TAO::getClassFile($className);
		if (!is_file($file)) {
			$className = "\\TAO\\Bundle\\{$this->name}\\{$class}";
			if ($check) {
				$file = \TAO::getClassFile($className);
				if (!is_file($file)) {
					return false;
				}
			}
		}
		return $className;
	}

	/**
	 * @param $class
	 * @return bool|string
	 */
	public function hasClass($class)
	{
		return $this->className($class, true);
	}

	/**
	 * @param $code
	 * @return bool|string
	 */
	public function getEntityClassName($code)
	{
		$name = \TAO::chunkCap($code);
		$class = $this->hasClass("Entity\\{$name}");
		return $class;
	}

	/**
	 * @param $code
	 * @return bool|string
	 */
	public function getSectionClassName($code)
	{
		$name = \TAO::chunkCap($code);
		$class = $this->hasClass("Section\\{$name}");
		return $class;
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function getController($name)
	{
		$class = $this->className("Controller\\{$name}");
		$file = \TAO::getClassFile($class);
		if (is_file($file)) {
			$controller = new $class;
			$controller->bundle = $this;
			$controller->setup();
			return $controller;
		}
		print "Unknown controller {$this->name}:{$name}";
		die;
	}

	/**
	 * @param $type
	 * @param $code
	 * @param $class
	 */
	public function infoblockSchema($type, $code, $class)
	{
		if (strpos($class, '\\') === false) {
			$class = $this->className("Infoblock\\{$class}");
		}
		\TAO::setOption("infoblock.{$code}.bundle", $this);
		\TAO\Infoblock::processSchema($type, $code, $class);
	}


	/**
	 * @param $route
	 * @return mixed
	 */
	public function dispatch($route)
	{
		self::checkComposite($route);
		if (isset($route['element_of'])) {
			return self::dispatchElement($route);
		}
		if (isset($route['elements_of'])) {
			return self::dispatchElements($route);
		}
		if (isset($route['section_of'])) {
			return self::dispatchSection($route);
		}
		if (isset($route['sections_of'])) {
			return self::dispatchSections($route);
		}
		$controller = $this->getController($route['controller']);
		$controller->route = $route;
		$action = $route['action'];
		if (!method_exists($controller, $action)) {
			die("Unknown action {$this->name}:{$route['controller']}:{$action}");
		}
		$args = array();
		foreach ($route as $k => $v) {
			if (is_int($k)) {
				$args[] = $v;
			}
		}
		$args[] = $route;
		return call_user_func_array(array($controller, $action), $args);
	}

	/**
	 * @param $args
	 */
	protected static function checkComposite($args)
	{
		if (isset($args['reject_composite']) && $args['reject_composite']) {
			\TAO::rejectComposite(trim($args['reject_composite']));
		}
		if (isset($args['composite']) && $args['composite']) {
			\TAO::$compositeContent = $args['composite'];
			if (isset($args['composite_stub'])) {
				\TAO::$compositeStub = $args['composite_stub'];
			}
		}
	}

	/**
	 * @param $route
	 * @return bool
	 */
	public static function dispatchElement($route)
	{
		$infoblock = \TAO::infoblock($route['element_of']);
		if (!$infoblock) {
			return false;
		}
		$by = false;
		$param = false;
		if (!isset($route['mode'])) {
			$route['mode'] = 'full';
		}
		if (isset($route['code'])) {
			$by = 'CODE';
			$param = $route['code'];
		}
		if (isset($route['id'])) {
			$by = 'ID';
			$param = $route['id'];
		}
		if (isset($route['id_or_code'])) {
			$by = false;
			$param = $route['id_or_code'];
		}
		if (!$param) {
			return false;
		}
		$item = $infoblock->loadItem($param, true, $by);
		if (!$item) {
			return false;
		}
		if (!$item->isActive()) {
			if (!$item->userCanEdit()) {
				return false;
			}
		}
		$item->preparePage($route);
		$item->incShowCount();
		return $item->render($route);
	}

	/**
	 * @param $route
	 * @return bool
	 */
	public static function dispatchElements($route)
	{
		$infoblock = \TAO::infoblock($route['elements_of']);
		if (!$infoblock) {
			return false;
		}
		return $infoblock->renderElementsPage($route);
	}

	/**
	 * @param $route
	 * @return bool
	 */
	public static function dispatchSections($route)
	{
		$infoblock = \TAO::infoblock($route['sections_of']);
		if (!$infoblock) {
			return false;
		}
		return $infoblock->renderSectionsPage($route);
	}

	/**
	 * @param $route
	 * @return bool
	 */
	public static function dispatchSection($route)
	{
		$infoblock = \TAO::infoblock($route['section_of']);
		if (!$infoblock) {
			return false;
		}
		$by = false;
		$param = false;
		if (isset($route['code'])) {
			$by = 'CODE';
			$param = $route['code'];
		}
		if (isset($route['id'])) {
			$by = 'ID';
			$param = $route['id'];
		}
		if (isset($route['id_or_code'])) {
			$by = false;
			$param = $route['id_or_code'];
		}
		if (!$param) {
			return false;
		}
		$section = $infoblock->getSection($param, $by);
		if (!$section) {
			return false;
		}
		$section->preparePage($route);
		return $section->render($route);
	}

	/**
	 * @return bool
	 */
	protected function pageNotFound()
	{
		return false;
	}

	/**
	 * @param $url
	 */
	protected function movedPermanentlyTo($url)
	{
		header("location: {$url}");
		die;
	}

	/**
	 * @param $url
	 */
	protected function redirectTo($url)
	{
		header("location: {$url}");
		die;
	}

	/**
	 * @param $name
	 */
	protected function withinLayout($name)
	{
		\TAO::$layout = $name;
	}

	/**
	 *
	 */
	protected function noLayout()
	{
		\TAO::$layout = false;
	}

	/**
	 * @param $type
	 * @param $data
	 */
	protected function infoblockType($type, $data)
	{
		\TAO\InfoblockType::check($type, $data);
	}

	/**
	 * @param $optionsName
	 * @return ConfigReader
	 */
	protected function options($optionsName)
	{
		if (!$this->options[$optionsName]) {
			return $this->options[$optionsName] = new ConfigReader($this, $optionsName);
		}
		return $this->options[$optionsName];
	}

	/**
	 * @param $options
	 * @param $action
	 */
	public function cli($action, $options)
	{

	}

}
