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
		if (!empty(\TAO::$config['shop']['delete_product'])) {
			$this->subscribeDelete(\TAO::$config['shop']['delete_status']);
		}
	}

	public function routes()
	{
		return array(
			'~/tao/shop/add-to-cart/~' => array(
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

	protected function subscribeDelete($deleteStatus)
	{
		$deleteStatus = $deleteStatus ?: 'F';
		$callback = function (Event $event) use ($deleteStatus) {
			/** @var \Bitrix\Sale\Order $order */
			$order = $event->getParameter('ENTITY');
			if ($order->getField('STATUS_ID') == $deleteStatus) {
				$this->deleteProducts($order);
			}
		};
		EventManager::getInstance()->addEventHandler('sale', 'OnSaleOrderSaved', $callback);
		EventManager::getInstance()->addEventHandler('sale', 'OnSaleStatusOrderChange', $callback);
	}

	/**
	 * @param \Bitrix\Sale\Order $order
	 */
	protected function deleteProducts($order)
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
}
