<?php

namespace TAO\CLI;

use TAO\CacheManager;
use TAO\CLI;

class Cache extends CLI
{
	public function clear()
	{
		CacheManager::getInstance()->clearAll();
	}
}