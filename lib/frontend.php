<?php

namespace TAO;

class Frontend extends \Techart\Frontend\Frontend
{
	/**
	 * Делаем доступными некоторые методы для удобства.
	 *
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 * @throws FrontendMissingMethodException
	 */
	public function __call($name, $arguments)
	{
		switch ($name) {
			case 'render':
			case 'renderBlock':
				$obj = $this->templates();
				break;

			case 'url':
			case 'cssUrl':
			case 'jsUrl':
			case 'cssTag':
			case 'jsTag':
				$obj = $this->assets();
				break;

			default:
				throw new FrontendMissingMethodException($name);
		}

		return call_user_func_array(array($obj, $name), $arguments);
	}
}

class FrontendMissingMethodException extends \Exception
{
}