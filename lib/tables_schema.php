<?php

namespace TAO;

/**
 * Class TablesSchema
 * @package TAO
 */
class TablesSchema
{
	/**
	 * @var array
	 */
	static $tables = array();
	/**
	 * @var array
	 */
	static $schema = array(
		'tao_urls' => array(
			'id' => 'serial',
			'url' => 'varchar(250) DEFAULT "" NOT NULL index',
			'infoblock' => 'varchar(50) DEFAULT "" NOT NULL index',
			'item_id' => 'int(11) DEFAULT "0" NOT NULL index',
			'mode' => 'varchar(50) DEFAULT "" NOT NULL index',
			'site' => 'varchar(10) DEFAULT "" NOT NULL index',
			'time_update' => 'int(11) DEFAULT "0" NOT NULL index',
		),
	);

	/**
	 *
	 */
	public static function process()
	{
		global $DB;
		$result = $DB->Query('SHOW TABLES');
		while ($row = $result->Fetch()) {
			$table = array_shift($row);
			self::$tables[$table] = $table;
		}
		foreach (self::$schema as $table => $fields) {
			self::processTable($table, $fields);
		}
	}

	/**
	 * @param $table
	 * @param $fields
	 */
	public static function processTable($table, $fields)
	{
		if (!isset(self::$tables[$table])) {
			self::createTable($table, $fields);
		} else {
			self::updateTable($table, $fields);
		}
	}

	/**
	 * @param $table
	 * @param $field
	 * @param $type
	 * @return array
	 */
	public static function parseField($table, $field, $type)
	{
		$index = false;
		if ($type == 'serial') {
			$index = "PRIMARY KEY (`{$field}`)";
			$column = "`{$field}` int(11) auto_increment NOT NULL";
		} else {
			if (strpos($type, ' index') > 0) {
				$index = "KEY `idx_{$table}_{$field}` (`{$field}`)";
				$type = str_replace(' index', '', $type);
			}
			$column = "`{$field}` {$type}";
		}
		return array($column, $index);
	}

	/**
	 * @param $table
	 * @param $fields
	 */
	public static function createTable($table, $fields)
	{
		global $DB;
		$columns = '';
		$indexes = '';
		foreach ($fields as $field => $type) {
			list($column, $index) = self::parseField($table, $field, $type);
			if ($index) {
				$indexes .= "{$index}, ";
			}
			$columns .= "{$column}, ";
		}
		$columns .= $indexes;
		$columns = trim($columns, ', ');
		$query = "CREATE TABLE `{$table}` ({$columns})";
		$DB->Query($query);
	}

	/**
	 * @param $table
	 * @param $fields
	 */
	public static function updateTable($table, $fields)
	{
		global $DB;
		$result = $DB->Query("SHOW FIELDS FROM {$table}");
		$columns = array();
		$indexes = array();
		while ($row = $result->Fetch()) {
			$name = $row['Field'];
			if ($name != 'id') {
				$type = $row['Type'];
				$def = $row['Default'];
				if (!is_null($def)) {
					$type .= " DEFAULT \"{$def}\"";
				}
				if ($row['Null'] == 'NO') {
					$type .= " NOT NULL";
				}
				if ($row['Key'] == 'MUL') {
					$type .= ' index';
					$indexes[$name] = true;
				}
				$columns[$name] = $type;
			}
		}
		foreach ($fields as $field => $type) {
			if ($field == 'id') continue;
			list($column, $index) = self::parseField($table, $field, $type);
			if (isset($columns[$field]) && $column) {
				$query = "ALTER TABLE {$table} CHANGE `{$field}` {$column}";
				$DB->Query($query);
			} else {
				$query = "ALTER TABLE {$table} ADD {$column}";
				$DB->Query($query);
			}
			if ($index && !isset($indexes[$field])) {
				$index = preg_replace('{^KEY }', 'INDEX ', $index);
				$query = "ALTER TABLE {$table} ADD {$index}";
				$DB->Query($query);
			}
		}
	}
}

if (\TAO::cache()->fileUpdated(__FILE__)) {
	TablesSchema::process();
}