<?php

namespace TAO\Bundle\Elements\Controller;

/**
 * Class Index
 * @package TAO\Bundle\Elements\Controller
 */
class Index extends \TAO\Controller
{
	/**
	 * @return bool
	 */
	function index()
	{
		$infoblock = $this->route['infoblock'];
		$id = $this->route['item_id'];
		$mode = $this->route['mode'];
		$item = $infoblock->loadItem($id);
		if (!$item) {
			return $this->pageNotFound();
		}
		$item->preparePage($mode);
		return $item->render($mode);
	}
}