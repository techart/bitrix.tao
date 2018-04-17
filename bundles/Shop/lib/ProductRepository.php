<?php

namespace TAO\Bundle\Shop;

use Bitrix\Main\Loader;
use CCatalogGroup;
use CCatalogProduct;
use CCatalogProductSet;
use CIBlockElement;
use CPrice;
use TAO\Bundle\Shop\Infoblock\Shop;

class ProductRepository
{
	/**
	 * @var Shop
	 */
	protected $infoblock;
	/** @var CCatalogProductSet */
	private $sets;
	/** @var CCatalogProduct */
	private $product;
	/** @var CIBlockElement */
	private $element;
	/** @var CPrice */
	private $price;

	public function __construct($infoblock)
	{
		Loader::includeModule('iblock');
		Loader::includeModule('catalog');

		$this->infoblock = $infoblock;
		$this->sets = new CCatalogProductSet();
		$this->product = new CCatalogProduct();
		$this->element = new CIBlockElement();
		$this->price = new CPrice();
	}

	public function getProduct($name, $price, $description = '', $parameters = array())
	{
		// Загружаем товар
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
		$product = CCatalogProduct::GetByIDEx($id);
		return $product ? new Product($product, array()) : null;
	}

	/**
	 * @param string $name
	 * @param float $price
	 * @param string $description
	 * @param Product[] $products
	 *
	 * @return Product|null
	 * @throws \Exception
	 */
	public function getProductSet($name, $price, $description, $products = array())
	{
		// Загружаем товар
		$productId = $this->loadProductElement($name, $description, array()) ?: $this->createProductSet($name, $price, $description, $products);
		$product = CCatalogProduct::GetByIDEx($productId);
		return $product ? $this->makeProductObject($product, array()) : null;
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
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
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

	private function createProductSet($name, $price, $description = '', $products = array())
	{
		if (!$productId = $this->createProductElement($name, $description, array())) {
			throw new \Exception('Cant create iblock element. Everything is awful');
		}
		if (!$this->addCatalogProperties($productId)) {
			throw new \Exception('Cant add catalog properties to product. Everything is awful');
		}
		if (!$this->addCatalogSetProperties($productId, $products)) {
			throw new \Exception('Cant add catalog properties to product. Everything is awful');
		}
		if (!$this->addPrice($productId, $price)) {
			throw new \Exception('Cant add price to product. Everything is awful');
		}
		return (int)$productId;
	}

	private function createProductElement($name, $description = '', $parameters = array())
	{
		$hashProperty = $this->infoblock->hashPropertyId();
		$shopParametersProperty = $this->infoblock->shopParametersPropertyId();
		return $this->element->Add(array(
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
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		return $this->product->Add(array(
			'ID' => $productId,
		));
	}

	private function addCatalogSetProperties($productId, $products)
	{
		$parameters = array(
			'TYPE' => CCatalogProductSet::TYPE_SET,
			'SET_ID' => 0,
			'ID' => $productId,
			'ITEM_ID' => $productId,
			'ITEMS' => array_map(function ($product) {
				/** @var Product $product */
				return array(
					'ACTIVE' => 'Y',
					'ITEM_ID' => $product->id(),
					'QUANTITY' => 1,
				);
			}, $products),
		);
		if (!$this->sets->add($parameters)) {
			throw new \Exception('Cant create product set ' . \TAO::app()->getException()->GetString());
		}
		return true;
	}

	private function addPrice($productId, $price)
	{
		$basePrice = CCatalogGroup::GetBaseGroup();
		if (!$basePrice) {
			throw new \Exception('Cant find base price. Everything is awful');
		}
		return $this->price->Add(array(
			"PRODUCT_ID" => $productId,
			"CATALOG_GROUP_ID" => $basePrice['ID'],
			"PRICE" => $price,
			"CURRENCY" => "RUB",
		), true);
	}
}