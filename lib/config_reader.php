<?php

namespace TAO;

/**
 * Class ConfigReader
 * @package TAO
 */
class ConfigReader
{
	/**
	 * @var string
	 */
	protected $configName = '';
	/**
	 * @var
	 */
	protected $bundle;
	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * ConfigReader constructor.
	 * @param $bundle
	 * @param $configName
	 */
	public function __construct($bundle, $configName)
	{
		$this->configName = $configName;
		$this->bundle = $bundle;
	}

	/**
	 * @param $name
	 * @return null
	 */
	public function get($name)
	{
		$options = $this->options();
		return isset($options[$name]) ? $options[$name] : null;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function has($name)
	{
		$options = $this->options();
		return isset($options[$name]);
	}

	/**
	 * @return array
	 */
	protected function options()
	{
		if (empty($this->options)) {
			foreach ($this->files() as $file) {
				if (!file_exists($file)) {
					continue;
				}
				$fileData = include($file);
				$this->options = array_replace_recursive($this->options, is_array($fileData) ? $fileData : array());
			}
		}
		return $this->options;
	}

	/**
	 * @param $sub
	 * @return array
	 */
	protected function files()
	{
		return array(
			$this->bundle->taoPath("config/{$this->configName}.php"),
			$this->bundle->localPath("config/{$this->configName}.php"),
		);
	}
}
