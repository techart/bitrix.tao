<?php

namespace TAO;

/**
 * Class Frontend
 * @package TAO
 *
 * @method \string renderBlock(\string $block, array $parms = array(), \string $mode = 'default')
 * @method \string addHelper(\object $helper, \string $name, \string $mode = 'default')
 * @method \string cssTag(\string $entryPoint)
 * @method \string jsTag(\string $entryPoint)
 * @method \string url(\string $path)
 */
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
			case 'exists':
			case 'render':
			case 'renderBlock':
			case 'addHelper':
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

	/**
	 * @param $name
	 * @return \Techart\Frontend\Templates\Bem\Block
	 */
	public function block($name)
	{
		return new \Techart\Frontend\Templates\Bem\Block($name);
	}
}

class FrontendMissingMethodException extends \Exception
{
}
