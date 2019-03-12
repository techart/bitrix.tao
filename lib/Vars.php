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

	/**
	 * Доступные (реализованные) типы пользовательских полей
	 *
	 * @var array
	 */
	private static $availableUserTypes = array(
		'boolean',
		'datetime',
		'double',
		'file',
		'iblock_element',
		'iblock_section',
		'integer',
		'string',
	);

	/**
	 * Vars constructor.
	 * @throws VarsException
	 */
	private function __construct()
	{
		if (!\CModule::IncludeModule('askaron.settings')) {
			$moduleName = self::MODULE_NAME;

			throw new VarsException("Модуль \"{$moduleName}\" не установлен");
		}
	}

	private function __clone()
	{
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
	 * @param $varName
	 * @return string | null
	 * @throws \Exception
	 */
	public static function get($varName)
	{
		if (!$varName) {
			throw new VarsException("Не задано название переменной");
		}

		$userFieldName = 'UF_' . strtoupper($varName);
		$arUserFields = self::getUserFields();

		if (isset($arUserFields[$userFieldName])) {
			$requiredUserField = $arUserFields[$userFieldName];
			$requiredUserFieldType = $requiredUserField['USER_TYPE_ID'];

			if (!in_array($requiredUserFieldType, self::$availableUserTypes)) {
				throw new VarsException("Тип переменной \"{$requiredUserFieldType}\" не реализован в Bitrix.TAO");
			}

			$field = AbstractUField::getField($requiredUserField);

			return $field->value();
		} else throw new VarsException("Переменная \"{$varName}\" не найдена");
	}

	/**
	 * Возвращает пользовательские поля модуля
	 *
	 * @return array
	 */
	protected static function getUserFields()
	{
		global $USER_FIELD_MANAGER;

		$arUserFields = $USER_FIELD_MANAGER->GetUserFields(self::USER_FIELDS_ENTITY_ID, 1, LANGUAGE_ID);

		return $arUserFields;
	}
}

/**
 * Class VarsException
 * @package TAO
 */
class VarsException extends \Exception
{

}
