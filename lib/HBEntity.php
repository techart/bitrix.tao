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
			$class = '\\TAO\\UField\\UField' . implode('', array_map('ucfirst', explode('_', $fieldData['USER_TYPE_ID'])));
			$field = new $class($fieldData['FIELD_NAME'], $fieldData['EDIT_FORM_LABEL'], $fieldData);
			$field->setEntityID($fieldData['ENTITY_ID'])
				->setMultiple($fieldData['MULTIPLE'] === 'Y')
				->setValue($this->data[$name]);
			$this->fieldsValue[$name] = $field;

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
