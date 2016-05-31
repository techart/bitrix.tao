<?php

namespace TAO\Bundle\Shop;

use Bitrix\Main\Loader;
use CCatalogGroup;
use CCatalogProduct;
use CIBlockElement;
use CPrice;
use TAO\Bundle\Shop\Infoblock\Shop;

class ProductRepository
{
	/**
	 * @var Shop
	 */
	protected $infoblock;

	public function __construct($infoblock)
	{
		$this->infoblock = $infoblock;
	}

	public function getProduct($name, $price, $description = '', $parameters = array())
	{
		// Загружаем товар
		Loader::includeModule('iblock');
		Loader::includeModule('catalog');

		$productId = $this->loadProductElement($name, $description, $parameters) ?: $this->createProduct($name, $price, $description, $parameters);
		$product = CCatalogProduct::GetByIDEx($productId);
		return $product ? $this->makeProductObject($product, $parameters) : null;
	}

	public function getProductById($id)
	{
		$id = (int)$id;
		if (!$id) {
			return null;
		}
		Loader::includeModule('catalog');
		$product = CCatalogProduct::GetByIDEx($id);
		return $product ? new Product($product, array()) : null;
	}

	public function getProductSet($name, $price, $description, $products = array())
	{
		$setProduct = $this->getProduct($name, $price, $description);
		if (!CCatalogProduct::Add(array(
			'TYPE' => 1,
			'SET_ID' => 0,
			'ID' => $setProduct->id(),
			'ITEMS' => array_map(function($product) {
				return array(
					'ACTIVE' => 'Y',
					'ITEM_ID' => $product->id(),
					'QUANTITY' => 1,
				);
			}, $products),
		))) {
			throw new \Exception('Cant create product set ' . \TAO::app()->sLastError);
		}
		return $setProduct;
	}

	private function makeProductObject($productData, $shopParameters = array())
	{
		if (empty($shopParameters) && !empty($data['PROPERTIES']['SHOP_PARAMETERS'])) {
			$shopParameters = unserialize($data['PROPERTIES']['SHOP_PARAMETERS']) ?: array();
		}
		return new Product($productData, $shopParameters);
	}

	private function loadProductElement($name, $description = '', $parameters = array())
	{
		$product = CIBlockElement::GetList(
			array(),
			array(
				'IBLOCK_ID' => $this->infoblock->id(),
				'=PROPERTY_' . $this->infoblock->hashPropertyCode() => $this->infoblock->hash($name, $description, $parameters),
			),
			false,
			false,
			array('ID', 'IBLOCK_ID')
		)->Fetch();
		return $product ? (int)$product['ID'] : null;
	}

	private function createProduct($name, $price, $description = '', $parameters = array())
	{
		if (!$productId = $this->createProductElement($name, $description, $parameters)) {
			throw new \Exception('Cant create iblock element. Everything is awful');
		}
		if (!$this->addCatalogProperties($productId)) {
			throw new \Exception('Cant add catalog properties to product. Everything is awful');
		}

		if (!$this->addPrice($productId, $price)) {
			throw new \Exception('Cant add price to product. Everything is awful');
		}
		return (int)$productId;
	}

	private function createProductElement($name, $description = '', $parameters = array())
	{
		$element = new CIBlockElement();
		$hashProperty = $this->infoblock->hashPropertyId();
		$shopParametersProperty = $this->infoblock->shopParametersPropertyId();
		return $element->Add(array(
			'NAME' => $name,
			'IBLOCK_ID' => $this->infoblock->id(),
			'ACTIVE' => 'Y',
			'DETAIL_TEXT' => $this->infoblock->composeDescription($description, $parameters),
			'PROPERTY_VALUES' => array(
				$hashProperty => $this->infoblock->hash($name, $description, $parameters),
				$shopParametersProperty => serialize($parameters)
			),
		));
	}

	private function addCatalogProperties($productId)
	{
		return CCatalogProduct::Add(array(
			'ID' => $productId,
		));
	}

	private function addPrice($productId, $price)
	{
		$basePrice = CCatalogGroup::GetBaseGroup();
		if (!$basePrice) {
			throw new \Exception('Cant find base price. Everything is awful');
		}
		$obPrice = new CPrice();
		return $obPrice->Add(array(
			"PRODUCT_ID" => $productId,
			"CATALOG_GROUP_ID" => $basePrice['ID'],
			"PRICE" => $price,
			"CURRENCY" => "RUB",
		), true
		);
	}
}