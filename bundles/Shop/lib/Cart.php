<?php

namespace TAO\Bundle\Shop;

class Cart
{
	public function addProduct(CartProductInterface $product, $quantity)
	{
		$product = $this->create_product($product);
		Add2Basket($product->price_id(), $quantity, array(), array());
	}

	protected function create_product(CartProductInterface $product)
	{
		if (!\CModule::IncludeModule('iblock') || !\CModule::IncludeModule('catalog')) {
			throw new \Exception('Module iblock or catalog not found');
		}
		return new \stdClass();
	}

	protected function delete_product(CartProductInterface $product)
	{

	}
}

interface CartProductInterface
{
//	public function __construct($name, $price);

	public function name();

	public function price();
}

class CartProduct
{
	protected $name;
	protected $price;

	public function __construct($name, $price)
	{
		$this->name = $name;
		$this->price = $price;
	}

	public function price_id()
	{
		return 5;
	}
}
