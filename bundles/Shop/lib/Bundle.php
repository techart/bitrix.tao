<?php

namespace TAO\Bundle\Shop;

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use TAO\Bundle as BaseBundle;

/**
 * Class Bundle
 * @package TAO\Bundle\Shop
 */
class Bundle extends BaseBundle
{
	private $repository;

	public function cachedInit()
	{
		parent::cachedInit();
		$this->infoblockType('shop', 'Магазин');
	}

	public function init()
	{
		parent::init();
		$this->infoblockSchema('shop', 'shop', '\TAO\Bundle\Shop\Infoblock\Shop');
		if ($this->option('delete_products')) {
			$this->subscribeDelete($this->option('delete_status'));
		}
	}

	public function routes()
	{
		return array(
			'~/tao/shop/add-to-cart/spread/~' => array(
				'controller' => 'AddToCart',
				'action' => 'addProductFromSpread',
			),
			'~/tao/shop/add-to-cart/id/~' => array(
				'controller' => 'AddToCart',
				'action' => 'addProductById',
			),
			'~/tao/shop/add-to-cart/data/~' => array(
				'controller' => 'AddToCart',
				'action' => 'addProduct',
			),
		);
	}

	public function getProduct($name, $price, $description, $parameters = array())
	{
		return $this->getRepository()->getProduct($name, $price, $description, $parameters);
	}

	public function getProductById($id)
	{
		return $this->getRepository()->getProductById($id);
	}

	public function getProductSet($name, $price, $description, $products = array())
	{
		return $this->getRepository()->getProductSet($name, $price, $description, $products);
	}

	public function addProductToCart(Product $product, $quantity)
	{
		Loader::includeModule('sale');
		$price = reset($product['PRICES']);
		$properties = array();
		foreach ($product->getShopParameters() as $name => $value) {
			$properties[] = array(
				'NAME' => $name,
				'VALUE' => $value,
			);
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		\CSaleBasket::Add(array(
			'PRODUCT_ID' => $product['ID'],
			'PRODUCT_PRICE_ID' => $price['ID'],
			'PRICE' => $price['PRICE'],
			'CURRENCY' => $price['CURRENCY'],
			'QUANTITY' => $quantity,
			'LID' => SITE_ID,
			'NAME' => $product['NAME'],
			'NOTES' => $product['DETAIL_TEXT'],
			'PROPS' => $properties,
		));
	}

	public function addToCartButton(Product $product, $quantity, $type = 'with-quantity')
	{
		return $this->render($type, array(
			'product' => $product,
			'quantity' => $quantity,
		));
	}

	public function cartUrl()
	{
		return $this->option('cart_url');
	}

	protected function subscribeDelete($deleteStatus)
	{
		$deleteStatus = $deleteStatus ?: 'F';
		$bundle = $this;
		$callback = function (Event $event) use ($deleteStatus, $bundle) {
			/** @var \Bitrix\Sale\Order $order */
			$order = $event->getParameter('ENTITY');
			if ($order->getField('STATUS_ID') == $deleteStatus) {
				$bundle->deleteProducts($order);
			}
		};
		EventManager::getInstance()->addEventHandler('sale', 'OnSaleOrderSaved', $callback);
		EventManager::getInstance()->addEventHandler('sale', 'OnSaleStatusOrderChange', $callback);
	}

	/**
	 * @param \Bitrix\Sale\Order $order
	 */
	public function deleteProducts($order)
	{
		$basket = $order->getBasket();
		$shopInfoblockId = \TAO::getInfoblock('shop')->id();
		/** @var \Bitrix\Sale\BasketItem $item */
		foreach ($basket->getBasketItems() as $item) {
			$productId = $item->getProductId();
			$data = \CIBlockElement::GetByID($productId)->Fetch();
			if ($data && ($data['IBLOCK_ID'] = $shopInfoblockId)) {
				\CIBlockElement::Delete($productId);
			}
		}
	}

	private function getRepository()
	{
		if (!$this->repository) {
			$this->repository = new ProductRepository(\TAO::getInfoblock('shop'));
		}
		return $this->repository;
	}

	private function render($template, $parameters)
	{
		ob_start();
		extract($parameters);
		include($this->viewPath($template));
		return ob_get_clean();
	}

	/**
	 * @param $file
	 * @return mixed
	 */
	private function viewPath($file)
	{
		return \TAO::filePath($this->fileDirs('views'), "{$file}.phtml");
	}

	/**
	 * @param $dir
	 * @return array
	 */
	private function fileDirs($dir)
	{
		$dirs = array();
		$sub = $this->subdir();
		if ($this->bundle) {
			if ($sub) {
				$dirs[] = $this->bundle->localPath("{$dir}/shop/{$sub}");
			}
			$dirs[] = $this->bundle->localPath("{$dir}/shop");
		}
		if ($sub) {
			$dirs[] = \TAO::localDir("{$dir}/shop/{$sub}");
		}
		$dirs[] = \TAO::localDir("{$dir}/shop");
		$dirs[] = \TAO::taoDir("{$dir}/shop");
		return $dirs;
	}

	/**
	 * @return string
	 */
	private function subdir()
	{
		return \TAO::unchunkCap($this->name);
	}
}
