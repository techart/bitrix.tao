<?php

namespace TAO;

/**
 * Class CacheManager
 * @package TAO
 */
class CacheManager
{
	protected static $instance = null;

	public static function getInstance()
	{
		if (!static::$instance) {
			static::$instance = new self();
		}
		return static::$instance;
	}

	public function removeLocalCache()
	{
		$local_cache_path = $_SERVER['DOCUMENT_ROOT'] . '/local/cache';
		if (file_exists($local_cache_path)) {
			$this->rmdirRecursive($local_cache_path);
		}
	}

	public function removeTwigCache()
	{
		$path = $_SERVER['DOCUMENT_ROOT'] . '/local/templates';
		if (file_exists($path)) {
			$directory = new \RecursiveDirectoryIterator($path);
			$iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
			$iterator->setMaxDepth(1);
			$match = new \RegexIterator($iterator, '~/twig$~');
			foreach ($match as $filePath => $fileInfo) {
				$twig_path = (string)$filePath;
				$this->rmdirRecursive($twig_path);
			}
		}
	}

	/**
	 * Рекурсивное удаление папки с вложенными файлами
	 * @param string $dir
	 */
	private function rmdirRecursive($dir)
	{
		if ($objects = glob($dir . "/*")) {
			foreach ($objects as $object) {
				is_dir($object) ? $this->rmdirRecursive($object) : unlink($object);
			}
		}
		rmdir($dir);
	}

	/**
	 * @return bool
	 */
	public function removeBitrixCache()
	{
		return \BXClearCache(true);
	}

	/**
	 * @return bool
	 */
	public function removeBitrixStaticHtmlCache()
	{
		return \Bitrix\Main\Data\StaticHtmlCache::getInstance()->deleteAll();
	}

	public function removeBitrixManagedCache()
	{
		(new \Bitrix\Main\Data\ManagedCache())->cleanAll();
	}

	public function removeBitrixStackCache()
	{
		(new \CStackCacheManager())->CleanAll();
	}

	public function clearAll()
	{
		$this->removeLocalCache();
		$this->removeTwigCache();
		$this->removeBitrixCache();
		$this->removeBitrixManagedCache();
		$this->removeBitrixStackCache();
		$this->removeBitrixStaticHtmlCache();
	}
}