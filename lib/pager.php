<?php

namespace TAO;

/**
 * Class Pager
 * @package TAO
 */
class Pager
{
	/**
	 * @var string
	 */
	protected $url = '/';
	/**
	 * @var string
	 */
	protected $var = 'page';
	/**
	 * @var
	 */
	protected $callback;
	/**
	 * @var string
	 */
	protected $type = 'common';
	/**
	 * @var bool
	 */
	protected $dir = false;

	/**
	 * @param $url
	 * @return $this
	 */
	public function setUrl($url)
	{
		$this->url = $url;
		return $this;
	}

	/**
	 * @param $var
	 * @return $this
	 */
	public function setVar($var)
	{
		$this->var = $var;
		return $this;
	}

	/**
	 * @param $callback
	 * @return $this
	 */
	public function setCallback($callback)
	{
		$this->callback = $callback;
		return $this;
	}

	/**
	 * @param $dir
	 * @return $this
	 */
	public function setDir($dir)
	{
		$this->dir = $dir;
		return $this;
	}

	/**
	 * @param $type
	 * @return $this
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @param $page
	 * @return bool|mixed|string
	 */
	public function url($page)
	{
		if (is_callable($this->callback)) {
			return call_user_func($this->callback, $page);
		}

		return \TAO\Urls::pagerUrl($this->url, $this->var, $page);
	}

	/**
	 * @param $sub
	 * @return array
	 */
	public function dirs($sub)
	{
		$dirs = array();
		if ($this->dir) {
			$dirs[] = "{$this->dir}/{$sub}";
		}

		$dirs[] = \TAO::localDir($sub);
		$dirs[] = \TAO::taoDir($sub);
		return $dirs;
	}

	/**
	 * @param $page
	 * @param $numPages
	 * @return string
	 */
	public function renderLinks($page, $numPages)
	{
		$path = \TAO::filePath($this->dirs('views'), 'page-links.phtml', $this->type);

		ob_start();
		include($path);
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * @param $page
	 * @param $numPages
	 * @return string
	 */
	public function render($page, $numPages)
	{
		$path = \TAO::filePath($this->dirs('views'), 'page-navigator.phtml', $this->type);
		$style = \TAO::filePath($this->dirs('styles'), 'pager.css', $this->type);

		ob_start();
		include($path);
		$content = ob_get_clean();
		return $content;
	}
}
