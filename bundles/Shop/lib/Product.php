<?php

namespace TAO\Bundle\Shop;

use CCatalogProductSet;

class Product implements \ArrayAccess
{
	protected $data;
	protected $shopParameters;

	public function __construct($data, $parameters)
	{
		$this->data = $data;
		$this->shopParameters = $parameters;
	}

	public function id()
	{
		return $this['ID'];
	}

	public function getShopParameters()
	{
		return $this->shopParameters;
	}

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}

	public function getPrice()
	{
		$prices = $this['PRICES'];
		$price = reset($prices);
		return $price['PRICE'];
	}

	public function hasComposition()
	{
		return $this->data['TYPE'] == CCatalogProductSet::TYPE_SET || $this;
	}

	public function items()
	{
		$catalogProductSet = new CCatalogProductSet();
		$arSets = $catalogProductSet->getAllSetsByProduct($this->id(), CCatalogProductSet::TYPE_SET);
		/** @var Bundle $shop */
		$shop = \TAO::bundle('Shop');
		$items = array();
		foreach ($arSets as $arSet) {
			$items[] = $shop->getProductById($arSet['ID']);
		}
		return $items;
	}
}
