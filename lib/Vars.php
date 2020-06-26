<?php

namespace TAO;

use TAO\UField\AbstractUField;

/**
 * Обёртка над модулем <b>Настройки++</b><br/>
 * https://marketplace.1c-bitrix.ru/solutions/askaron.settings/
 *
 * Class SettingsVars
 * @package TAO
 */
class Vars
{
	const USER_FIELDS_ENTITY_ID = 'ASKARON_SETTINGS';
	const MODULE_NAME = 'Настройки++';

	private static $instance = null;
	private $fields;

	/**
	 * Vars constructor.
	 * @throws VarsException
	 */
	function __construct()
	{
		if (!\CModule::IncludeModule('askaron.settings')) {
			$moduleName = self::MODULE_NAME;
			throw new VarsModuleNotInstalledException("Модуль \"{$moduleName}\" не установлен");
		}
	}

	/**
	 * @return Vars
	 * @throws VarsException
	 */
	public static function getInstance()
	{
		return self::$instance ?: self::$instance = new self();
	}

	/**
	 * Возвращает значение запрашиваемого пользовательского поля модуля
	 *
	 * @param string $varName
	 * @return string | null
	 * @throws UField\UnsupportedFieldTypeException
	 * @throws VarsException
	 */
	public function get($varName)
	{
		if ($fieldData = $this->getUserFieldDataByVarName($varName)) {
			return AbstractUField::getField($fieldData)->value();
		} else {
			throw new UndefinedVarException("Переменная \"{$varName}\" не найдена");
		}
	}

	/**
	 * Проверяет существование переданной переменной
	 *
	 * @param string $varName
	 * @return bool
	 */
	public function exists($varName)
	{
		return !empty($this->getUserFieldDataByVarName($varName));
	}

	/**
	 * Возвращает пользовательские поля модуля
	 *
	 * @return array
	 */
	protected function getUserFields()
	{
		if (is_null($this->fields)) {
			/** @var CUserTypeManager $USER_FIELD_MANAGER */
			global $USER_FIELD_MANAGER;

			$this->fields = $USER_FIELD_MANAGER->GetUserFields(self::USER_FIELDS_ENTITY_ID, 1, LANGUAGE_ID);
		}
		return $this->fields;
	}

	protected function getUserFieldDataByVarName($varName)
	{
		$userFieldName = 'UF_' . strtoupper($varName);
		$fields = $this->getUserFields();
		return isset($fields[$userFieldName]) ? $fields[$userFieldName] : null;
	}

	public static function createDefault()
	{
		$userTypeField = new \CUserTypeEntity();
		foreach (self::defaultFields() as $fieldData) {
			$field = new $fieldData['type'](
				$fieldData['code'],
				$fieldData['name']
			);
			$field->setEntityID(self::USER_FIELDS_ENTITY_ID);

			foreach ($fieldData['options'] as $name => $value) {
				$field->setOption($name, $value);
			}

			$id = $userTypeField->add($field->data());
		}
	}

	private static function defaultFields()
	{
		return [
			[
				'type' => '\\TAO\\UField\\UFieldString',
				'code' => 'UF_PHONE',
				'name' => 'Телефон',
			],
			[	'type' => '\\TAO\\UField\\UFieldString',
				'code' => 'UF_EMAIL',
				'name' => 'E-mail',
			],
			[	'type' => '\\TAO\\UField\\UFieldString',
				'code' => 'UF_CODE_HEAD',
				'name' => 'счетчик аналитики в теге HEAD, например script для Google Tag Manager',
				'options' => [
					'SIZE' => 120,
					'ROWS' => 6,
				],
			],
			[	'type' => '\\TAO\\UField\\UFieldString',
				'code' => 'UF_CODE_BODY_OPENING',
				'name' => 'счетчик аналитики после открывающего тега BODY, например noscript для Google Tag Manager',
				'options' => [
					'SIZE' => 120,
					'ROWS' => 6,
				],
			],
			[	'type' => '\\TAO\\UField\\UFieldString',
				'code' => 'UF_CODE_BODY_CLOSING',
				'name' => 'счетчик аналитики перед закрывающим тегом BODY, например внешние чаты',
				'options' => [
					'SIZE' => 120,
					'ROWS' => 6,
				],
			],
		];
	}
}

/**
 * Class VarsException
 * @package TAO
 */
class VarsException extends \TAOException
{
}

class UndefinedVarException extends VarsException
{
}

class VarsModuleNotInstalledException extends VarsException
{
}
