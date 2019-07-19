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
			throw new ModuleNotInstalledException("Модуль \"{$moduleName}\" не установлен");
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

class ModuleNotInstalledException extends VarsException
{
}
