<?php

namespace TAO\CLI;

use TAO\CLI;

class PhpStormMeta extends CLI
{
	protected $data = [];

	public static function storm_meta()
	{
		$cli = new static();
		$cli->addBundles();
		$cli->writeFile();
	}

	private function addBundles()
	{
		$list = [];
		foreach (\TAO::bundles() as $name => $bundle) {
			$list[$name] = get_class($bundle). '::class';
		}
		$this->addOverride('\TAO::bundle()', $list);
	}

	private function addOverride(string $who, array $list) {
		$this->data[] = 'override(' . $who . ', map([';
		foreach ($list as $name => $type) {
			$this->data[] = "\t'" . $name . "' => " . $type . ',';
		}
		$this->data[] = "]));";
	}

	protected function writeFile() {
		$data = ['<?php','namespace PHPSTORM_META {'];
		foreach ($this->data as $line) {
			$data[] = "\t" . $line;
		}
		$data[] = '}';
		$this->prepareDir();
		file_put_contents('../.phpstorm.meta.php/cli-generated.php', implode("\n", $data));
	}

	protected function prepareDir()
	{
		$dir = '../.phpstorm.meta.php';
		if (is_dir($dir)) {
			return;
		}
		if(!file_exists($dir)) {
			mkdir($dir, 0775, false);
			return;
		}
		rename($dir, $dir . '.app.php');
		\Bitrix\Main\IO\Directory::createDirectory($dir);
		rename($dir . '.app.php', $dir . '/app.php');
	}
}