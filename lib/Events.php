<?php

namespace TAO;

/**
 * Class Events
 * @package TAO
 */


abstract class Events
{
	static protected $listeners;

	public function addListener($eventName, $callback)
	{
		if (is_callable($callback)) {
			self::$listeners[$eventName][] = $callback;
		}
	}

	public function emit($eventName, &$arg1 = false, &$arg2 = false, &$arg3 = false, &$arg4 = false, &$arg5 = false, &$arg6 = false, &$arg7 = false)
	{
		if (isset(self::$listeners[$eventName])) {
			foreach (self::$listeners[$eventName] as $listener) {
				call_user_func_array($listener, array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7));
			}
		}
	}
}