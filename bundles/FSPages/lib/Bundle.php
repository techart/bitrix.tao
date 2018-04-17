<?php

namespace TAO\Bundle\FSPages;

/**
 * Class Bundle
 * @package TAO\Bundle\FSPages
 */
class Bundle extends \TAO\Bundle
{
	/**
	 * @param $file
	 * @return bool|string
	 */
	public function staticPagePath($file)
	{
		$path = \TAO::localDir("pages/{$file}");
		return is_file($path) ? $path : false;
	}
}
