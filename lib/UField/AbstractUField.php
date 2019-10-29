<?php

namespace TAO\UField;

abstract class AbstractUField
{
	protected $code;
	protected $name;
	protected $type;
	protected $entityId = null;
	protected $settings = [];
	protected $data;

	protected $isMultiple = false;
	protected $isShowInList = false;
	protected $isEditInList = true;
	protected $isSearchable = false;

	protected $value;

	/**
	 * AbstractUField constructor.
	 * @param string     $code
	 * @param string     $name
	 * @param null|array $data
	 * @throws IncorrectFieldNameException
	 */
	public function __construct($code, $name, $data = null)
	{
		if(strlen($code) > 20) {
			throw new IncorrectFieldNameException("Длинна имени поля (`${code}`) не должна превышать 20 символов");
		}
		$this->code = $code;
		$this->name = $name;
		$this->isShowInList = true;
		if (!is_null($data)) {
			$this->data = $data;
			$this->settings = $data['SETTINGS'];
		}
	}

	public function code()
	{
		return $this->code;
	}

	public function name()
	{
		return $this->name;
	}

	abstract public function type();

	public function value()
	{
		return $this->valueRaw();
	}

	public function valueRaw()
	{
		return $this->value;
	}

	public function settings()
	{
		return $this->settings;
	}

	public function setOption($name, $value)
	{
		$this->settings[$name] = $value;
		return $this;
	}

	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}

	//TODO разобраться с магией битрикса
	//При создании пользовательского поля допустим для раздела, создаются таблицы b_uts_..., но если просто создать пользовательское поле, то почему то они не создаются,
	//а данный способ обновления значения поля требует эти таблицы, хотя обновление через entity, работает
	protected function save()
	{
		global $USER_FIELD_MANAGER;
		$USER_FIELD_MANAGER->Update(
			$this->data['ENTITY_ID'],
			13,
			array($this->code() => $this->value)
		);
	}

	/**
	 * https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=04804&LESSON_PATH=3913.4776.4804
	 * @param string $isShowInList Возможные варианты HLBLOCK_{id}, IBLOCK_{id}, IBLOCK_{id}_SECTION
	 *
	 * @return this
	 */
	public function setEntityID($id)
	{
		$this->entityId = $id;
		return $this;
	}

	/**
	 * @param bool $isShowInList
	 *
	 * @return this
	 */
	public function setMultiple($isMultiple)
	{
		if ($isMultiple) {
			$this->settings['LIST_HEIGHT'] = 5;
		}
		$this->isMultiple = $isMultiple;
		return $this;
	}

	public function isMultiple()
	{
		return $this->isMultiple;
	}

	/**
	 * @param bool $isShowInList
	 *
	 * @return this
	 */
	public function setShowInList($isShowInList)
	{
		$this->isShowInList = $isShowInList;
		return $this;
	}

	/**
	 * @param bool $isShowInList
	 *
	 * @return this
	 */
	public function setSearchable($isShowInList)
	{
		$this->setSearchable = $isShowInList;
		return $this;
	}

	/**
	 * @return array
	 */
	public function data()
	{
		return [
			'ID' => $this->data['ID'],
			'ENTITY_ID' => $this->entityId,
			'FIELD_NAME' => $this->code,
			'USER_TYPE_ID' => $this->type(),
			'SORT' => '',
			'MULTIPLE' => $this->isMultiple ? 'Y' : 'N',
			'MANDATORY' => 'N',
			'SHOW_FILTER' => $this->isShowFilter ? 'Y' : 'N',
			'SHOW_IN_LIST' => $this->isShowInList ? 'Y' : 'N',
			'EDIT_IN_LIST' => $this->isEditInList ? 'Y' : 'N',
			'IS_SEARCHABLE' => $this->isSearchable ? 'Y' : 'N',
			'EDIT_FORM_LABEL' => [
				'ru' => $this->name,
			],
			'LIST_COLUMN_LABEL' => [
				'ru' => $this->name,
			],
			'LIST_FILTER_LABEL' => [
				'ru' => $this->name,
			],
			'SETTINGS' => $this->settings(),
		];
	}

	/**
	 * Возвращает объект пользовательского поля
	 *
	 * @param $fieldData - массив с параметрами пользовательского поля
	 * @return AbstractUField
	 * @throws UnsupportedFieldTypeException
	 */
	public static function getField($fieldData)
	{
		$class = '\\App\\UField\\UField' . self::snakeCaseToCamelCase($fieldData['USER_TYPE_ID']);
		if (!class_exists($class)) {
			$class = '\\TAO\\UField\\UField' . self::snakeCaseToCamelCase($fieldData['USER_TYPE_ID']);
			if (!class_exists($class)) {
				throw new UnsupportedFieldTypeException("UField: неподдерживаемый тип пользовательского поля \"{$fieldData['USER_TYPE_ID']}\"");
			}
		}

		$field = new $class($fieldData['FIELD_NAME'], $fieldData['EDIT_FORM_LABEL'], $fieldData);
		$field->setEntityID($fieldData['ENTITY_ID'])
			->setMultiple($fieldData['MULTIPLE'] === 'Y')
			->setValue($fieldData['VALUE']);

		return $field;
	}

	private static function snakeCaseToCamelCase($str)
	{
		return implode('', array_map('ucfirst', explode('_', $str)));
	}
}

class UnsupportedFieldTypeException extends \TAOException
{

}

class IncorrectFieldNameException extends \TAOException
{

}
