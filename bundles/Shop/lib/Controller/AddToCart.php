<?php

namespace TAO\Bundle\Shop\Controller;

use Bitrix\Main\Application;
use TAO\Controller as BaseController;

/**
 * Class AddToCart
 *
 * @package TAO\Bundle\Shop\Controller
 */
class AddToCart extends BaseController
{
	public function addProduct()
	{
		$request = Application::getInstance()->getContext()->getRequest();
		if (!$request->isAjaxRequest() || !$request->isPost()) {
			return $this->pageNotFound();
		}
		$data = json_decode($request->get('products'), true);
		if (!$data) {
			return $this->pageNotFound();
		}

		/** @var \TAO\Bundle\Shop\Bundle $shop */
		$shop = \TAO::bundle('Shop');

		foreach ($data as $productData) {
			$isProductSet = !empty($productData['items']);
			if ($isProductSet) {
				$items = array();
				foreach ($productData['items'] as $itemData) {
					$items[] = $shop->getProduct(
						$itemData['name'],
						$itemData['price'],
						isset($itemData['description']) ? $itemData['description'] : '',
						$itemData['parameters']
					);
				}
				$product = $shop->getProductSet(
					$productData['name'],
					$productData['price'],
					isset($productData['description']) ? $productData['description'] : '',
					$items
				);
			} else {
				$product = $shop->getProduct(
					$productData['name'],
					$productData['price'],
					isset($productData['description']) ? $productData['description'] : '',
					$productData['parameters']
				);
			}
			$shop->addProductToCart($product, $productData['quantity']);
		}
		die();
	}
}