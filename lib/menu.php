<?php

namespace TAO;

class Menu
{
	protected $linearMenu = null;
	protected $props = array();
	protected $cursorItem = null;

	public function __construct($linearMenu)
	{
		$this->linearMenu = $linearMenu;
		$this->props = array(
			'title' => 'TEXT',
			'url' => 'LINK',
			'selected' => 'SELECTED',
			'params' => 'PARAMS',
			'depthLevel' => 'DEPTH_LEVEL',
		);
	}

	/**
	 * @param string $name
	 * @param string $bitrixName
	 * @return $this;
	 */
	public function addProp($name, $bitrixName)
	{
		$this->props[$name] = $bitrixName;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getProps()
	{
		return $this->props;
	}

	/**
	 * @return $this
	 */
	public function calculateCurrent() {
		$this->addProp('current', 'CURRENT');
		$cur_page = \TAO::app()->GetCurPage(true);
		$cur_page_no_index = \TAO::app()->GetCurPage(false);
		foreach ($this->linearMenu as &$item) {
			$url_chunks = parse_url($item['LINK']);
			$url = $url_chunks['path'];
			$item['CURRENT'] = $url == $cur_page || $url == $cur_page_no_index;
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getTreeMenu() {
		reset($this->linearMenu);
		$this->cursorItem = current($this->linearMenu);
		if($this->cursorItem === false) {
			return array();
		}

		return self::buildTreeMenu();
	}

	private function buildTreeMenu()
	{
		$currentLevel = $this->cursorItem['DEPTH_LEVEL'];
		$list = array();
		do {
			$data = self::getPropsFromMenuItem($this->cursorItem);
			$this->cursorItem = next($this->linearMenu);
			if($this->cursorItem !== false && $this->cursorItem['DEPTH_LEVEL'] > $currentLevel) {
				$data['sub'] = self::buildTreeMenu();
			}
			$list[] = $data;
		} while ($this->cursorItem !== false && $this->cursorItem['DEPTH_LEVEL'] >= $currentLevel);
		return $list;
	}

	/**
	 * @param array $item
	 * @return array
	 */
	private function getPropsFromMenuItem($item)
	{
		$data = array();
		foreach ($this->getProps() as $key => $from) {
			$data[$key] = isset($item[$from]) ? $item[$from] : null;
		}
		return $data;
	}
}
