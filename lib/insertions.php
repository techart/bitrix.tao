<?php

namespace TAO;

class Insertions
{
	private $insertions = array();
	private static $instance = null;

	public static function instance()
	{
		return self::$instance ?: self::$instance = new self();
	}

	public function renderInsertion($name)
	{
		return '#TECHART_INSERTION_' . strtoupper($name) . '#';
	}

	public function addInsertionContent($name, $content)
	{
		$this->insertions[$name] = $content;
	}

	public function startInsertionBlock($name)
	{
		ob_start();
	}

	public function endInsertionBlock($name)
	{
		$this->addInsertionContent($name, ob_get_clean());
	}

	public function processInsertion($name, $content)
	{
		return str_replace($this->insertionCode($name), $this->insertions[$name], $content);
	}

	public function processInsertions($content)
	{
		foreach (array_keys($this->insertions) as $name) {
			$content = $this->processInsertion($name, $content);
		}
		return $content;
	}

	private function __construct()
	{
		\AddEventHandler("main", "OnEndBufferContent", function(&$content) {
			$content = \TAO::insertions()->processInsertions($content);
		});
	}

	private function __clone()
	{
		// close it
	}

	private function insertionCode($name)
	{
		return '#TECHART_INSERTION_' . strtoupper($name) . '#';
	}
}
