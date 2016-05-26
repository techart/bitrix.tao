<?php

namespace TAO;

class Assets
{
	protected $env;
	protected $jsonDir;
	protected $jsonData = array();
	protected $publicPath;

	public function __construct($env_name, $jsonDir = '/frontend/assets', $publicPath = '/builds')
	{
		$this->env = $env_name;
		$this->jsonDir = $jsonDir;
		$this->publicPath = $publicPath;
	}

	public function css($name, $additional = false)
	{
		if ($file = $this->file('css', $name)) {
			\TAO::app()->SetAdditionalCSS($file, $additional);
		}
	}

	public function js($name, $additional = false)
	{
		if ($file = $this->file('js', $name)) {
			\TAO::app()->AddHeadScript($file, $additional);
		}
	}

	public function url($path)
	{
		$path = trim($path, '/');
		$env = ($this->env == 'dev' || $this->env == 'hot') ? 'dev' : 'prod';
		return "{$this->publicPath()}/{$env}/$path";
	}

	protected function file($type, $name)
	{
		$this->readJson($this->env);
		$url = $this->getJsonUrl($name, $type) ?: $this->getStaticUrl($name, $type);
		return $url ?: '';
	}

	protected function getJsonUrl($name, $type)
	{
		if (empty($this->jsonData[SITE_TEMPLATE_PATH]) || empty($this->jsonData[SITE_TEMPLATE_PATH][$name])) {
			return null;
		}
		$data = $this->jsonData[SITE_TEMPLATE_PATH][$name];
		return isset($data[$type]) ? $data[$type] : null;
	}

	protected function getStaticUrl($name, $type)
	{
		$url = "{$this->publicPath()}/{$this->env}/{$type}/{$name}.{$type}";
		if (is_file("./{$url}")) {
			return $url;
		}
		return null;
	}

	protected function readJson($env)
	{
		$path = $this->getJsonPath($env);
		if ($path) {
			$this->jsonData[SITE_TEMPLATE_PATH] = json_decode(file_get_contents($path), true);
		}
	}

	protected function getJsonPath($env)
	{
		return $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . $this->jsonDir . '/' . $env . '.json';
	}

	protected function publicPath()
	{
		return SITE_TEMPLATE_PATH . $this->publicPath;
	}
}