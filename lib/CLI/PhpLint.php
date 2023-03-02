<?php

namespace TAO\CLI;

use TAO\CLI;

class PhpLint extends CLI
{
	public static function php_lint_run()
	{
		echo 'Php-lint start...';
		$path = exec('pwd');
		exec("cd $path/local/php-tooling && php ./vendor/bin/phpcs ../../ --colors", $output);
		echo implode("\n", $output);

	}
}
