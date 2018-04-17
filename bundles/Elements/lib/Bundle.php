<?php

namespace TAO\Bundle\Elements;

/**
 * Class Bundle
 * @package TAO\Bundle\Elements
 */
class Bundle extends \TAO\Bundle
{
	/**
	 * @param $uri
	 * @return array
	 */
	public function route($uri)
	{
		global $DB;

		$site = SITE_ID;
		$uri = str_replace("'", '', $uri);
		$res = $DB->Query("SELECT * FROM tao_urls WHERE url='{$uri}' AND (site='' OR site='{$site}') ORDER BY time_update DESC LIMIT 1");
		while ($row = $res->Fetch()) {
			$id = $row['item_id'];
			$mode = $row['mode'];
			$infoblock = \TAO::getInfoblock($row['infoblock']);
			if ($infoblock) {
				$urls = $infoblock->urls();
				if (isset($urls[$mode])) {
					$data = $urls[$mode];
					$route = isset($data['route']) ? $data['route'] : array();
					if (!isset($route['action'])) {
						$route['bundle'] = 'Elements';
						$route['controller'] = 'Index';
						$route['action'] = 'index';
					}
					$route['item_id'] = $id;
					$route['mode'] = $mode;
					$route['infoblock'] = $infoblock;
					return $route;
				}
			}
		}
	}
}
