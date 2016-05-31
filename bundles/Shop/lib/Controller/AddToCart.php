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
			die();
		}

		//$data_from_request = '[{"name":"Test product","price":"1100","quantity":3,"description":"Some random text","parameters":{"\u0426\u0432\u0435\u0442":"\u041b\u0438\u043b\u043e\u0432\u044b\u0439","\u0413\u0430\u0431\u0430\u0440\u0438\u0442\u044b":"20x40","\u0412\u0435\u0441":"100"}},{"name":"Other test product","price":"1100","quantity":1,"description":"Some random text","parameters":{"\u0426\u0432\u0435\u0442":"Green","\u0413\u0430\u0431\u0430\u0440\u0438\u0442\u044b":"100x40","\u0412\u0435\u0441":"125"}}]'

		$data = json_decode($request->get('products'), true);
		if (!$data) {
			die();
		}


		/** @var \TAO\Bundle\Shop\Bundle $shop */
		$shop = \TAO::bundle('Shop');

		foreach ($data as $productData) {
			$product = $shop->getProduct(
				$productData['name'],
				$productData['price'],
				isset($productData['description']) ? $productData['description'] : '',
				$productData['parameters']
			);
			$shop->addProductToCart($product, $productData['quantity']);
		}

//		$set = $shop->getProductSet('Test set', 600, 'Test set Description', [
//			$shop->getProduct('Test', 200, 'Test description1', [
//				'Цвет' => 'Лиловый',
//				'Габариты' => '20x40',
//				'Вес' => '100',
//			]),
//			$shop->getProduct('Test', 200, 'Test description1', [
//				'Цвет' => 'Фиолетовый',
//				'Габариты' => '20x40',
//				'Вес' => '100',
//			])
//		]);

//		$shop->addProductToCart($set, 2);
		die('12121');
	}
}