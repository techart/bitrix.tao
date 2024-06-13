<?php

namespace TAO\CLI;

use TAO\CLI;

class Storybook extends CLI
{
	public static function generate_stories()
	{
		if (isset(static::$options['help'])) {
			\Techart\Frontend\Storybook::showHelp();
			exit();
		}


		$rootPath = rtrim(dirname($_SERVER['DOCUMENT_ROOT']), '/');

		$storybook = new \Techart\Frontend\Storybook($rootPath);

		$params = $storybook->getParams();

		foreach (static::$options as $param => $value) {
			if (isset($params[$param])) {
				if (isset($params[$param]['variants']) &&
					!in_array($value, $params[$param]['variants'])) {
					continue;
				}
				$params[$param]['value'] = $value;
			}
		}

		foreach (array_keys($params) as $param) {
			if (!isset($params[$param]['value']) ||
				('' === $params[$param]['value'])) {
				if (isset($params[$param]['default'])) {
					$params[$param]['value'] = $params[$param]['default'];
				}
			}
		}

		$storybook->Run([
			'all_overwrite' => ('' !== $params['overwrite']['value']) ? ('yes' === $params['overwrite']['value']) : null,
			'only_blocks' => ('' !== $params['only']['value']) ? explode(',', $params['only']['value']) : [],
		]);
	}
}
