<?php

namespace TAO;

use \Bitrix\Highloadblock as HL;
use \TAO\UField\AbstractUField;

/**
 */
class HighloadBlock
{
	protected $table;
	protected $data;
	protected $fields;

	/**
	 * HighloadBlock constructor.
	 * @param Bitrix\Main\Entity\Base $table
	 * @param array                   $data
	 */
	public function __construct($table, $data)
	{
		$this->table = $table;
		$this->data = $data;
	}

	/**
	 *
	 * @return int id highlod блока
	 */
	public function id()
	{
		return (int)$this->data['ID'];
	}

	/**
	 *
	 * @return string имя highlod блока
	 */
	public function name()
	{
		return $this->data['NAME'];
	}

	public function table()
	{
		return $this->table;
	}

	/**
	 *
	 * @return array  массив с информацией о полях
	 */
	public function getFields()
	{
		if (is_null($this->fields)) {
			$dbField = \CUserTypeEntity::GetList(
				[],
				['ENTITY_ID' => 'HLBLOCK_' . $this->id(), 'LANG' => LANGUAGE_ID]
			);

			while ($field = $dbField->Fetch()) {
				$this->fields[$field['FIELD_NAME']] = $field;
			}
		}
		return $this->fields;
	}

	/**
	 * @param string код поля
	 *
	 * @return array массив с информацией о поле
	 */
	public function getFieldInfo($code)
	{
		$dbField = \CUserTypeEntity::GetList(
			[],
			['ENTITY_ID' => 'HLBLOCK_' . $this->id(), 'FIELD_NAME' => $code, 'LANG' => LANGUAGE_ID]
		);

		return $dbField->Fetch();
	}

	/**
	 * Добавляет поле к highload блоку
	 * @param AbstractUField $field
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addField(AbstractUField $field)
	{
		global $APPLICATION;

		$dbField = \CUserTypeEntity::GetList(
			[],
			['ENTITY_ID' => 'HLBLOCK_' . $this->id(), 'FIELD_NAME' => $field->code()]
		);

		if ($dbField->Fetch()) {
			echo 'field ' . $field->code() . ' xxx';
		}

		$field->setEntityID('HLBLOCK_' . $this->id());

		$userTypeField = new \CUserTypeEntity();
		$id = $userTypeField->add($field->data());
		if (!$id) {
			$ex = $APPLICATION->GetException();
			echo $ex->getString();
		} else {
			$this->table = HL\HighloadBlockTable::compileEntity($this->data);
		}
	}

	/**
	 * @param array $fields
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addFields($fields = array())
	{
		foreach ($fields as $field) {
			$this->addField($field);
		}
	}

	/**
	 * Обновляет информацию о поле
	 *
	 * не изменяются: USER_TYPE_ID - тип поля, ENTITY_ID - объекта привязки, FIELD_NAME - кода поля
	 * @param AbstractUField $field
	 */
	public function updateField(AbstractUField $field)
	{
		global $APPLICATION;

		$dbField = \CUserTypeEntity::GetList(
			[],
			['ENTITY_ID' => 'HLBLOCK_' . $this->id(), 'FIELD_NAME' => $field->code()]
		);

		if ($fieldData = $dbField->Fetch()) {
			$userTypeField = new \CUserTypeEntity();
			if (!$userTypeField->update($fieldData['ID'], $fieldData)) {
				$ex = $APPLICATION->GetException();
				echo $ex->getString();
			}
		} else {
			echo 'Такого поля не существует';
		}
	}

	public function getFieldsName()
	{
		$fieldsName = [];
		foreach ($this->table->getFields() as $fieldName => $fieldData) {
			$fieldsName[] = $fieldName;
		}

		return $fieldsName;
	}

	/**
	 * @return int
	 */
	public function getCount()
	{
		$tableClass = $this->table->getDataClass();
		return $tableClass::getCount();
	}


	/**
	 * @param int $id
	 * @param boolean $useCache
	 * @param array $cacheParams
	 *
	 * @return HBEntity
	 */
	public function loadItem($id, $useCache = false, $cacheParams = ['ttl' => 86400])
	{
		$tableClass = $this->table->getDataClass();
		$params = [
			'filter' => [
				'ID' => $id,
			],
		];
		if ($useCache) {
			$params['cache'] = $cacheParams;
		}

		$dbRows = $tableClass::getList($params);
		return new HBEntity($dbRows->fetch(), $this->getFields(), $this);
	}

	/**
	 * https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=5753
	 *
	 * select имена полей, которые необходимо получить в результате<br>
	 * filter описание фильтра для WHERE и HAVING<br>
	 * group явное указание полей, по которым нужно группировать результат<br>
	 * order параметры сортировки<br>
	 * offset количество записей<br>
	 * runtime динамически определенные поля<br>
	 * key поле, используемое для ключа
	 *
	 * @param array $args
	 *
	 * @return \TAO\HBEntity[]
	 */

	public function getRows($args = array())
	{
		$tableClass = $this->table->getDataClass();
		$key = null;
		if(key_exists('key', $args)) {
			$key = strtoupper(trim($args['key']));
			if($key !== 'ID' && strpos($key, 'UF_') === false) {
				$key = 'UF_' . $key;
			}
			unset($args['key']);
		}
		$rows = [];
		$dbRows = $tableClass::getList($args);
		$fieldsRow = $this->getFields();

		//fetchAll()
		while ($row = $dbRows->fetch()) {
			$entity = new HBEntity($row, $fieldsRow, $this);
			if(!is_null($key)) {
				$keyValue = null;
				if($key === 'ID') {
					$keyValue = $entity->id();
				} else {
					$keyValueObject = $entity->property($key);
					if(!is_null($keyValueObject)) {
						$keyValue = $keyValueObject->value();
					}
				}
				if(is_null($keyValue)) {
					$rows[] = $entity;
				} else {
					$rows[$keyValue] = $entity;
				}
			} else {
				$rows[] = $entity;
			}
		}
		return $rows;
	}

	public function add($args)
	{
		$args = $this->checkFields($args);
		if (count($args) > 0) {
			$tableClass = $this->table->getDataClass();
			$result = $tableClass::add(
				$args
			);
			return $result->getId();
		}
		return false;
	}

	public function delete($id)
	{
		$tableClass = $this->table->getDataClass();
		$tableClass::delete($id);
	}

	public function update($id, $args)
	{
		$args = $this->checkFields($args);
		if (count($args) > 0) {
			$tableClass = $this->table->getDataClass();
			$tableClass::update($id, $args);
		}
	}

	protected function checkFields($fieldList)
	{
		$fieldsRows = $this->getFields();
		foreach ($fieldList as $name => $value) {
			if (!array_key_exists($name, $fieldsRows)) {
				unset($fieldList[$name]);
			}
		}
		return $fieldList;
	}
}

