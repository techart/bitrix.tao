<?php

namespace TAO;

use \Bitrix\Main\Loader;

Loader::includeModule("highloadblock");

use \Bitrix\Highloadblock as HL;

/**
 * Класс для работы с highload блоками:
 * получение,
 * добавление,
 * работа с схемами, ...
 */
abstract class HighloadBlockRepository
{
	protected static $cachedHLBlocksById = [];
	protected static $cachedHLBlocksByName = [];
	protected static $cachedHLBlocksByTableName = [];

	/**
	 * Получаем HighloadBlock по имени
	 *
	 * @param string $name Имя highload Блока
	 *
	 * @return \TAO\Highloadblock|null
	 */

	public static function get($name)
	{
		if(!isset(self::$cachedHLBlocksByName[$name])) {
			$hlData = HL\HighloadBlockTable::getList(
				array('filter' => array('NAME' => $name))
			)->fetch();

			if ($hlData !== false) {
				$hlBlock = HL\HighloadBlockTable::compileEntity($hlData);
				self::$cachedHLBlocksByName[$name] = new HighloadBlock($hlBlock, $hlData);
			} else {
				self::$cachedHLBlocksByName[$name] = null;
			}
		}
		return self::$cachedHLBlocksByName[$name];
	}

	/**
	 * Получаем HighloadBlock по имени таблицы
	 *
	 * @param string $tableName Имя highload Блока
	 *
	 * @return \TAO\Highloadblock|null
	 */
	public static function getByTableName($tableName)
	{
		if(!isset(self::$cachedHLBlocksByTableName[$tableName])) {
			$hlData = HL\HighloadBlockTable::getList(
				array('filter' => array('TABLE_NAME' => $tableName))
			)->fetch();

			if ($hlData !== false) {
				$hlBlock = HL\HighloadBlockTable::compileEntity($hlData);
				self::$cachedHLBlocksByTableName[$tableName] = new HighloadBlock($hlBlock, $hlData);
			} else {
				self::$cachedHLBlocksByTableName[$tableName] = null;
			}
		}

		return self::$cachedHLBlocksByTableName[$tableName];
	}

	/**
	 * Получаем HighloadBlock по ID
	 *
	 * @param $id
	 * @return HighloadBlock|null
	 */
	public static function getById($id)
	{
		if(!isset(self::$cachedHLBlocksById[$id])) {
			$hlData = HL\HighloadBlockTable::getList(
				array('filter' => array('ID' => $id))
			)->fetch();

			if ($hlData !== false) {
				$hlBlock = HL\HighloadBlockTable::compileEntity($hlData);
				self::$cachedHLBlocksById[$id] = new HighloadBlock($hlBlock, $hlData);
			} else {
				self::$cachedHLBlocksById[$id] = null;
			}
		}
		return self::$cachedHLBlocksById[$id];
	}

	/**
	 * Добавляет higloadblock
	 *
	 * @param string $name Имя поддерживаятся только латиница и цифры
	 * @param string $tableName Имя, для таблицы, необязательный параметр, имя таблицы может быть сформировано из имени
	 *
	 * @return int id нового highload блока
	 */

	public static function add($name, $table_name = false)
	{
		$name = preg_replace('/([^A-Za-z0-9]+)/', '', trim($name));
		if ($table_name === false) {
			$table_name = 'bxt_' . strtolower($name);
		}

		$name = ucfirst($name);

		$result = HL\HighloadBlockTable::add(array(
			'NAME' => $name,
			'TABLE_NAME' => $table_name
		));
		if (!$result->isSuccess()) {
			return $result->getErrorMessages();
		} else {
			return $result->getId();
		}
	}

	public static function getSchemaTableByName($name)
	{
		//TODO нужно реализовывать
		$hlBlock = self::get($name);
		$name = $hlBlock->name();

		return $hlBlock->getFields();
	}
}

