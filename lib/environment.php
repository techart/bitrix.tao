<?php
namespace TAO;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Request;

use \Techart\Frontend\Environment as BaseEnvironment;
use \Techart\Frontend\EnvironmentStorageInterface;

class Environment extends BaseEnvironment
{
	protected static $instance;

	public static function getInstance()
	{
		if (!self::$instance) {
			$storage = new EnvironmentStorage(
					Configuration::getInstance(),
					Application::getInstance()->getContext()->getRequest()
			);

			self::$instance = new static($storage);
		}

		return self::$instance;
	}
}

class EnvironmentStorage implements EnvironmentStorageInterface
{
	protected $config;

	protected $request;

	public function __construct(Configuration $config, Request $request)
	{
		$this->config = $config;
		$this->request = $request;
	}

	public function getFromConfig($name)
	{
		return $this->config->get($name);
	}

	public function getFromRequest($name)
	{
		return $this->request->get($name);
	}

	public function getFromSession($name)
	{
		return $_SESSION[$name];
	}

	public function setToSession($name, $value)
	{
		$_SESSION[$name] = $value;
	}
}