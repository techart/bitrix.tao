<?php

namespace TAO;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Request;

class Environment
{
	protected static $instance;

	public static function getInstance()
	{
		if (!self::$instance) {
			$env = new EnvironmentService(new EnvironmentStorage(
				Configuration::getInstance(),
				Application::getInstance()->getContext()->getRequest())
			);
			$GLOBALS['ENV_NAME'] = $env->getName();
			self::$instance = $env;
		}

		return self::$instance;
	}
}

class EnvironmentService
{
	protected $storage;
	protected $default_env;

	/**
	 * EnvironmentService constructor.
	 *
	 * @param EnvironmentStorage $storage
	 * @param string             $default_env
	 */
	public function __construct($storage, $default_env = 'prod')
	{
		$this->storage = $storage;
		$this->default_env = $default_env;
	}

	public function getName()
	{
		$env_from_config = $this->storage->getFromConfig();
		if ($env_from_config == 'prod') {
			return $env_from_config;
		}
		if ($env_from_request = $this->storage->getFromRequest()) {
			return $env_from_request;
		}
		if ($env_from_session = $this->storage->getFromSession()) {
			return $env_from_session;
		}
		return $env_from_config ?: $this->default_env;
	}
}

class EnvironmentStorage
{
	/**
	 * @var Configuration
	 */
	protected $config;
	/**
	 * @var Request
	 */
	protected $request;

	public function __construct($config, $request)
	{
		$this->config = $config;
		$this->request = $request;
	}

	public function getFromConfig()
	{
		return $this->config->get('env');
	}

	public function getFromRequest()
	{
		return $this->request->get('__run_env');
	}

	public function getFromSession()
	{
		if ($this->request->get('__env') && $_SESSION['env'] != $this->request->get('__env')) {
			$_SESSION['__env'] = trim($this->request->get('__env'));
		}
		return $_SESSION['__env'];
	}

}