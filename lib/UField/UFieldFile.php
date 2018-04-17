<?php

namespace TAO\UField;

class UFieldFile extends AbstractUField
{

	public function type()
	{
		return 'file';
	}

	public function value()
	{
		return new \TAO\File($this->valueRaw());
	}
}
