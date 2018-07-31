<?php

namespace TAO;

class Utils
{

	/**
	 * @param array $linearMenu
	 * @deprecated
	 * @see \TAO\Menu
	 *
	 * @return array
	 */
	public static function buildTreeMenu($linearMenu) {
		$menu = new \TAO\Menu($linearMenu);
		return $menu->calculateCurrent()->getTreeMenu();
	}
}
