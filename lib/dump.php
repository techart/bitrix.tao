<?php

namespace TAO;

/**
 * Class Dump
 * @package TAO
 */
class Dump
{
	protected static $restarted = false;

	public static function var_die()
	{
		self::optionalRestart();

		$last_call = debug_backtrace()[2];
		echo '<pre style="padding: 10px; background-color: #ffe8e8; border: 1px solid #e2b3b3">';

		foreach (func_get_args() as $value) {
			echo "\n\n--------------- Variable ---------------\n";
			var_dump($value);
		}

		echo "\n\n\n--------------------\nFILE: " . $last_call['file'] . "\nLINE: " . $last_call['line'] . "\n--------------------";
		echo '</pre>';
		die();
	}

	public static function var_dump()
	{
		self::optionalRestart();

		$last_call = debug_backtrace()[2];
		echo '<pre style="padding: 10px; background-color: #ffe8e8; border: 1px solid #e2b3b3">';

		foreach (func_get_args() as $value) {
			echo "\n\n--------------- Variable ---------------\n";
			var_dump($value);
		}

		echo "\n\n\n--------------------\nFILE: " . $last_call['file'] . "\nLINE: " . $last_call['line'] . "\n--------------------";
		echo '</pre>';
	}

	public static function optionalRestart()
	{
		if (!self::$restarted) {
			\TAO::app()->RestartBuffer();
			while (ob_get_clean()) ;

			self::$restarted = true;
		}
	}
}


