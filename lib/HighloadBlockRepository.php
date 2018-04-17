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
	/**
	 * Получаем HighloadBlock по имени
	 *
	 * @param string $name Имя highload Блока
	 *
	 * @return \Highloadblock, null
	 */

	public static function get($name)
	{
		$hlData = HL\HighloadBlockTable::getList(
			array('filter' => array('NAME' => $name))
		)->fetch();

		if ($hlData !== false) {
			$hlBlock = HL\HighloadBlockTable::compileEntity($hlData);
			return new HighloadBlock($hlBlock, $hlData);
		}
		return null;
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

		$result = HL\HighloadBlockTable::add([
			'NAME' => $name,
			'TABLE_NAME' => $table_name
		]);
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

