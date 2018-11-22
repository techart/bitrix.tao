<?php
if (! function_exists('vd')) {
	/**
	 * vd
	 * Рестартит буфер, чтоб очистить вывод до вызова функции и вар-дампит переданные аргументы, показывая откуда была вызвана сама функция.
	 * В конце срабатывает die
	 *
	 */
	function vd()
	{
		call_user_func_array(array('\TAO\Dump', 'var_die'), func_get_args());
	}
}

if (! function_exists('dd')) {
	/**
	 * dd
	 * Рестартит буфер, чтоб очистить вывод до вызова функции и вар-дампит переданные аргументы, показывая откуда была вызвана сама функция
	 *
	 */
	function dd()
	{
		call_user_func_array(array('\TAO\Dump', 'var_dump'), func_get_args());
	}
}
