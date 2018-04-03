<?php

namespace TAO;

/**
 * Class Deploy
 * @package TAO
 */
class Deploy
{
	/**
	 * Полное удаление кэша после деплоя
	 */
	public static function afterDeploy()
	{
		\TAO\CacheManager::getInstance()->clearAll();
	}
}