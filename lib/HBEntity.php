<?php

namespace TAO;

use \TAO\UField\AbstractUField;

class HBEntity
{
	protected $data;
	protected $fields;
	protected $hlb;

	protected $fieldsValue;

	public function __construct($data, $fields, $hlb)
	{
		$this->data = $data;
		$this->fields = $fields;
		$this->hlb = $hlb;
	}

	public function id()
	{
		return $this->data['ID'];
	}

	public function property($name)
	{
		if (array_key_exists($name, $this->data)) {
			$fieldData = $this->hlb->getFieldInfo($name);
			$this->fieldsValue[$name] = AbstractUField::getField($fieldData);

			return $this->fieldsValue[$name];
		}

		return null;
	}

	public function setProperty(AbstractUField $field)
	{
		$this->hlb->update($this->id(), $field);
	}

	public function update($fields)
	{
		$table = $this->hlb->table()->getDataClass();
		$table::update(
			(int)$this->data['ID'],
			$fields
		);
	}

	public function getFields()
	{
		return $this->fields;
	}
}
