<?php

namespace TAO;

/**
 * Class Controller
 * @package TAO
 */
class Controller
{
	/**
	 * @var
	 */
	public $bundle;
	/**
	 * @var array
	 */
	public $route = array();
	/**
	 * @var string
	 */
	protected $layout = 'work';

	/**
	 *
	 */
	public function setup()
	{
	}

	/**
	 * @return string
	 */
	protected function viewsDir()
	{
		return '';
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	protected function viewPath($name)
	{
		if (!preg_match('{\.phtml$}', $name)) {
			$name .= '.phtml';
		}

		$path = $this->bundle->filePath("views/{$name}");
		if (!$path) {
			print "View {$this->name}/{$name} not found!";
			die;
		}
		return $path;
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
	 * @param $tpl
	 * @param array $args
	 * @return string
	 */
	protected function render($tpl, $args = array())
	{
		$path = $this->viewPath($tpl);
		if (!is_file($path)) {
			die("File not found: {$path}");
		}

		foreach ($args as $k => $v) {
			$$k = $v;
		}

		ob_start();
		include($path);
		$content = ob_get_clean();

		return $content;
	}

	protected function jsonResponse($data)
	{
		$this->noLayout();
		header('Content-Type: application/json');
		return json_encode($data);
	}
}
