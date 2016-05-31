<?php

namespace TAO\Bundle\Shop;

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
}
