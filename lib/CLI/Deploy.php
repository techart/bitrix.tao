<?php

namespace TAO\CLI;

use TAO\CacheManager;
use TAO\CLI;

class Deploy extends CLI
{
	public function after()
	{
		CacheManager::getInstance()->clearAll();
	}
}