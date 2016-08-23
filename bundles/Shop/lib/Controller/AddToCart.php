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
	public function addProductFromData()
	{
		$request = Application::getInstance()->getContext()->getRequest();
		if (!$request->isAjaxRequest() || !$request->isPost()) {
			return $this->pageNotFound();
		}
		$data = json_decode($request->get('products'), true);
		if (!$data) {
			return $this->pageNotFound();
		}

		$this->addProduct($data);

		if ($request->isAjaxRequest()) {
			return 'ok';
		}
		LocalRedirect($request->get('backurl') ? urldecode($request->get('backurl')) : \TAO::bundle('Shop')->cartUrl());
	}

	public function addProductFromSpread()
	{
		$request = Application::getInstance()->getContext()->getRequest();
		if (!$request->isAjaxRequest() || !$request->isPost()) {
			return $this->pageNotFound();
		}
		if (!$request->get('name') || !$request->get('quantity')) {
			return $this->pageNotFound();
		}
		$this->addProduct([
			[
				'name' => $request->get('name'),
				'description' => $request->get('description'),
				'price' => $request->get('price'),
				'quantity' => $request->get('quantity'),
				'parameters' => !empty($request->get('parameters')) ? $request->get('parameters') : [],
				'items' => !empty($request->get('items')) ? $request->get('items') : [],
			]
		]);
		if ($request->isAjaxRequest()) {
			return 'ok';
		}
		LocalRedirect($request->get('backurl') ? urldecode($request->get('backurl')) : \TAO::bundle('Shop')->cartUrl());
	}

	public function addProductById()
	{
		$request = Application::getInstance()->getContext()->getRequest();
		if (!$request->isAjaxRequest() && !$request->isPost()) {
			return $this->pageNotFound();
		}
		if (!(int)$request->get('id') || !(int)$request->get('quantity')) {
			return $this->pageNotFound();
		}
		/** @var \TAO\Bundle\Shop\Bundle $shop */
		$shop = \TAO::bundle('Shop');
		$product = $shop->getProductById((int)$request->get('id'));
		$shop->addProductToCart($product, (int)$request->get('quantity'));
		if ($request->isAjaxRequest()) {
			return 'ok';
		}
		LocalRedirect($request->get('backurl') ? urldecode($request->get('backurl')) : $shop->cartUrl());
	}

	private function addProduct($data)
	{
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
	}
}
