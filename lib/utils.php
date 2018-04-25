<?php

namespace TAO;

class Utils
{
	/**
	 * @param array $linearMenu
	 * @return array
	 */
	public static function buildTreeMenu($linearMenu) {
		$parentId = 0;
		$firstLevel = 1;
		$treeMenu = [];

		foreach($linearMenu as $id => $arItem) {
			if($arItem['DEPTH_LEVEL'] == $firstLevel || $arItem['IS_PARENT']) {
				$treeMenu[$id] = [
					'title' => $arItem['TEXT'],
					'url' => $arItem['LINK'],
					'selected' => $arItem['SELECTED'],
					'params' => $arItem['PARAMS'],
					'depthLevel' => $arItem['DEPTH_LEVEL']
				];
				$parentId = $id;
			} else {
				$treeMenu[$parentId]['sub'][$id] = [
					'title' => $arItem['TEXT'],
					'url' => $arItem['LINK'],
					'selected' => $arItem['SELECTED'],
					'params' => $arItem['PARAMS'],
					'depthLevel' => $arItem['DEPTH_LEVEL']
				];
			}
		}
		return $treeMenu;
	}
}
