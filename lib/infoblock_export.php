<?php

namespace TAO;

/**
 * Class InfoblockExport
 * @package TAO
 */
class InfoblockExport
{
	/**
	 * @param $id
	 * @param bool|false $forCache
	 * @return bool|string
	 * @throws \TAOException
	 */
	public static function run($id, $forCache = false)
	{
		$result = \CIBlock::GetList(array(), array('ID' => $id, 'CHECK_PERMISSIONS' => 'N'));
		if($result->SelectedRowsCount() === 0) {
			throw new \TAOException("Невозможно получить данные инфоблока c ID={$id}.");
		} else {
			$data = $result->Fetch();
			$code = $data['CODE'];
			$name = $data['NAME'];
			$isactive = ($data['ACTIVE'] == 'Y');
			$sort = $data['SORT'];
			$description = (string)$data['DESCRIPTION'];
			$descriptionType = $data['DESCRIPTION_TYPE'];
			$className = $forCache ? \TAO::normalizeMnemocode($code) : \TAO::chunkCap($code);

			unset($data['ID']);
			unset($data['TIMESTAMP_X']);
			unset($data['IBLOCK_TYPE_ID']);
			unset($data['CODE']);
			unset($data['NAME']);
			unset($data['LANG_DIR']);
			unset($data['SERVER_NAME']);
			unset($data['LID']);
			unset($data['ACTIVE']);
			unset($data['SORT']);
			unset($data['DESCRIPTION']);
			unset($data['DESCRIPTION_TYPE']);

			$defs = array(
				'RSS_ACTIVE' => 'Y',
				'RSS_TTL' => '24',
				'RSS_FILE_ACTIVE' => 'N',
				'RSS_YANDEX_ACTIVE' => 'N',
				'INDEX_ELEMENT' => 'Y',
				'INDEX_SECTION' => 'N',
				'WORKFLOW' => 'Y',
				'VERSION' => '1',
				'BIZPROC' => 'N',
				'SECTION_CHOOSER' => 'L',
				'RIGHTS_MODE' => 'S',
				'SECTION_PROPERTY' => 'N',
				'PROPERTY_INDEX' => 'N',
			);


			foreach (array_keys($data) as $k) {
				if (empty($data[$k])) {
					unset($data[$k]);
				}
				if (isset($defs[$k]) && $defs[$k] == $data[$k]) {
					unset($data[$k]);
				}
			}

			$sites = '';
			$res = \CIBlock::GetSite($id);
			while ($row = $res->Fetch()) {
				$sites .= $sites != '' ? ',' : '';
				$sites .= "'" . $row['SITE_ID'] . "'";
			}

			unset($data['ELEMENTS_NAME']);
			unset($data['ELEMENT_NAME']);

			$sData = self::generateArrayExport($data, '        ');

			$sDescription = self::generateSimpleStringFunctionText('description', $description, '');
			$sDescriptionType = self::generateSimpleStringFunctionText('descriptionType', $descriptionType, 'text');
			$sIsActive = self::generateSimpleStringFunctionText('isActive', $isactive, true);
			$sSort = self::generateSimpleStringFunctionText('sort', $sort, '500');
			$sSites = "\n\n    public function sites()\n    {\n        return array({$sites});\n    }";

			$properties = array();
			$result = \CIBlockProperty::GetList(array(), array('IBLOCK_ID' => $id, 'CHECK_PERMISSIONS' => 'N'));
			$defs = array(
				'VERSION' => '1',
				'FILTRABLE' => 'N',
				'SEARCHABLE' => 'N',
				'LIST_TYPE' => 'L',
				'COL_COUNT' => '30',
				'ROW_COUNT' => '1',
				'MULTIPLE' => 'N',
				'SORT' => '500',
				'IS_REQUIRED' => 'N',
				'WITH_DESCRIPTION' => 'N',
				'MULTIPLE_CNT' => '5',
			);
			while ($row = $result->Fetch()) {
				$code = trim($row['CODE']);
				if ($code == '') {
					$code = 'PROP_' . $row['ID'];
				}
				$pid = $row['ID'];
				unset($row['ID']);
				unset($row['TIMESTAMP_X']);
				unset($row['IBLOCK_ID']);
				unset($row['ACTIVE']);
				unset($row['CODE']);
				foreach (array_keys($row) as $k) {
					if (empty($row[$k])) {
						unset($row[$k]);
					}
					if (isset($defs[$k]) && $defs[$k] == $row[$k]) {
						unset($row[$k]);
					}
				}
				if ($row['PROPERTY_TYPE'] == 'L') {
					$items = array();
					$res = \CIBlockPropertyEnum::GetList(array('SORT' => 'ASC', 'VALUE' => 'ASC'), array('PROPERTY_ID' => $pid, 'CHECK_PERMISSIONS' => 'N'));
					while ($lrow = $res->Fetch()) {
						$iid = $lrow['ID'];
						$eid = $lrow['EXTERNAL_ID'];
						unset($lrow['ID']);
						unset($lrow['EXTERNAL_ID']);
						unset($lrow['XML_ID']);
						unset($lrow['TMP_ID']);
						unset($lrow['PROPERTY_ID']);
						unset($lrow['PROPERTY_NAME']);
						unset($lrow['PROPERTY_CODE']);
						unset($lrow['PROPERTY_SORT']);
						if ($lrow['SORT'] == '500') unset($lrow['SORT']);
						if ($lrow['DEF'] == 'N') unset($lrow['DEF']);
						if (count($lrow) == 1 && isset($lrow['VALUE'])) $lrow = $lrow['VALUE'];
						$items[$eid] = $lrow;
					}
					$row['ITEMS'] = $items;
				}
				if (isset($row['LINK_IBLOCK_ID']) && !$forCache) {
					$row['LINK_IBLOCK_CODE'] = \TAO::getInfoblockCode($row['LINK_IBLOCK_ID']);
					unset($row['LINK_IBLOCK_ID']);
				}
				$properties[$code] = $row;
			}
			$sProperties = self::generateArrayExport($properties, '        ');

			$messages = \CIBlock::GetMessages($id);
			$sMessages = self::generateArrayExport($messages, '        ');

			$fields = self::trimArrayValues(\CIBlock::GetFields($id));
			$defFields = self::defaultFields();
			foreach (array_keys($fields) as $field) {
				if (isset($defFields[$field])) {
					$md5 = md5(serialize($fields[$field]));
					$def = md5(serialize($defFields[$field]));
					if ($md5 == $def) {
						//unset($fields[$field]);
					} else {
						//var_dump($fields[$field], $defFields[$field]);
					}
				}
			}
			$sFields = self::generateArrayExport($fields, '        ');

			$permissions = \CIBlock::GetGroupPermissions($id);
			$sPermissions = self::generateArrayExport($permissions, '        ');


			ob_start();
			include(\TAO::taoDir() . '/views/template-iblock.phtml');
			$content = "<?php\n" . ob_get_clean();
			return $content;
		}
	}

	/**
	 * @param $in
	 * @return array|string
	 */
	protected static function trimArrayValues($in)
	{
		if (!is_array($in)) {
			return trim($in);
		}
		$out = array();
		foreach ($in as $k => $v) {
			$out[$k] = self::trimArrayValues($v);
		}
		return $out;
	}


	/**
	 * @param $name
	 * @param $value
	 * @param $def
	 * @return string
	 */
	protected static function generateSimpleStringFunctionText($name, $value, $def)
	{
		if ($value === $def) {
			return '';
		}
		$v = str_replace("'", "\'", $value);
		$content = "'{$v}'";
		if (is_bool($value)) {
			$content = $value ? 'true' : 'false';
		}
		return "\n\n    public function {$name}()\n    {\n        return {$content};\n    }";
	}

	/**
	 * @param $m
	 * @param string $prefix
	 * @return string
	 */
	protected static function generateArrayExport($m, $prefix = '')
	{
		if (is_string($m)) {
			$m = str_replace("'", "\'", $m);
			return "'{$m}'";
		}
		if (is_bool($m)) {
			return $m ? 'true' : 'false';
		}
		if (is_null($m)) {
			return 'null';
		}
		if (is_array($m)) {
			$out = 'array(';
			foreach ($m as $k => $v) {
				$ks = is_string($k) ? "'{$k}'" : $k;
				$out .= "\n{$prefix}    {$ks} => " . self::generateArrayExport($v, "{$prefix}    ") . ',';
			}
			$out .= "\n{$prefix})";
			return $out;
		}
		$v = str_replace("'", "\'", (string)$m);
		return "'{$v}'";
	}

	/**
	 * @return array
	 */
	public static function defaultFields()
	{
		return array(
			'IBLOCK_SECTION' => array(
				'NAME' => 'Привязка к разделам',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'KEEP_IBLOCK_SECTION_ID' => 'N',
				),
			),
			'ACTIVE' => array(
				'NAME' => 'Активность',
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => 'Y',
			),
			'ACTIVE_FROM' => array(
				'NAME' => 'Начало активности',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'ACTIVE_TO' => array(
				'NAME' => 'Окончание активности',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'SORT' => array(
				'NAME' => 'Сортировка',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '0',
			),
			'NAME' => array(
				'NAME' => 'Название',
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => '',
			),
			'PREVIEW_PICTURE' => array(
				'NAME' => 'Картинка для анонса',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'FROM_DETAIL' => 'N',
					'SCALE' => 'N',
					'WIDTH' => '',
					'HEIGHT' => '',
					'IGNORE_ERRORS' => 'N',
					'METHOD' => 'resample',
					'COMPRESSION' => 95,
					'DELETE_WITH_DETAIL' => 'N',
					'UPDATE_WITH_DETAIL' => 'N',
					'USE_WATERMARK_TEXT' => 'N',
					'WATERMARK_TEXT' => '',
					'WATERMARK_TEXT_FONT' => '',
					'WATERMARK_TEXT_COLOR' => '',
					'WATERMARK_TEXT_SIZE' => '',
					'WATERMARK_TEXT_POSITION' => 'tl',
					'USE_WATERMARK_FILE' => 'N',
					'WATERMARK_FILE' => '',
					'WATERMARK_FILE_ALPHA' => '',
					'WATERMARK_FILE_POSITION' => 'tl',
					'WATERMARK_FILE_ORDER' => null,
				),
			),
			'PREVIEW_TEXT_TYPE' => array(
				'NAME' => 'Тип описания для анонса',
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => 'text',
			),
			'PREVIEW_TEXT' => array(
				'NAME' => 'Описание для анонса',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'DETAIL_PICTURE' => array(
				'NAME' => 'Детальная картинка',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'SCALE' => 'N',
					'WIDTH' => '',
					'HEIGHT' => '',
					'IGNORE_ERRORS' => 'N',
					'METHOD' => 'resample',
					'COMPRESSION' => 95,
					'USE_WATERMARK_TEXT' => 'N',
					'WATERMARK_TEXT' => '',
					'WATERMARK_TEXT_FONT' => '',
					'WATERMARK_TEXT_COLOR' => '',
					'WATERMARK_TEXT_SIZE' => '',
					'WATERMARK_TEXT_POSITION' => 'tl',
					'USE_WATERMARK_FILE' => 'N',
					'WATERMARK_FILE' => '',
					'WATERMARK_FILE_ALPHA' => '',
					'WATERMARK_FILE_POSITION' => 'tl',
					'WATERMARK_FILE_ORDER' => null,
				),
			),
			'DETAIL_TEXT_TYPE' => array(
				'NAME' => 'Тип детального описания',
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => 'text',
			),
			'DETAIL_TEXT' => array(
				'NAME' => 'Детальное описание',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'XML_ID' => array(
				'NAME' => 'Внешний код',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'CODE' => array(
				'NAME' => 'Символьный код',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'UNIQUE' => 'N',
					'TRANSLITERATION' => 'N',
					'TRANS_LEN' => 100,
					'TRANS_CASE' => 'L',
					'TRANS_SPACE' => '-',
					'TRANS_OTHER' => '-',
					'TRANS_EAT' => 'Y',
					'USE_GOOGLE' => 'N',
				),
			),
			'TAGS' => array(
				'NAME' => 'Теги',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'SECTION_NAME' => array(
				'NAME' => 'Название',
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => '',
			),
			'SECTION_PICTURE' => array(
				'NAME' => 'Картинка для анонса',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'FROM_DETAIL' => 'N',
					'SCALE' => 'N',
					'WIDTH' => '',
					'HEIGHT' => '',
					'IGNORE_ERRORS' => 'N',
					'METHOD' => 'resample',
					'COMPRESSION' => 95,
					'DELETE_WITH_DETAIL' => 'N',
					'UPDATE_WITH_DETAIL' => 'N',
					'USE_WATERMARK_TEXT' => 'N',
					'WATERMARK_TEXT' => '',
					'WATERMARK_TEXT_FONT' => '',
					'WATERMARK_TEXT_COLOR' => '',
					'WATERMARK_TEXT_SIZE' => '',
					'WATERMARK_TEXT_POSITION' => 'tl',
					'USE_WATERMARK_FILE' => 'N',
					'WATERMARK_FILE' => '',
					'WATERMARK_FILE_ALPHA' => '',
					'WATERMARK_FILE_POSITION' => 'tl',
					'WATERMARK_FILE_ORDER' => null,
				),
			),
			'SECTION_DESCRIPTION_TYPE' => array(
				'NAME' => 'Тип описания',
				'IS_REQUIRED' => 'Y',
				'DEFAULT_VALUE' => 'text',
			),
			'SECTION_DESCRIPTION' => array(
				'NAME' => 'Описание',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'SECTION_DETAIL_PICTURE' => array(
				'NAME' => 'Детальная картинка',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'SCALE' => 'N',
					'WIDTH' => '',
					'HEIGHT' => '',
					'IGNORE_ERRORS' => 'N',
					'METHOD' => 'resample',
					'COMPRESSION' => 95,
					'USE_WATERMARK_TEXT' => 'N',
					'WATERMARK_TEXT' => '',
					'WATERMARK_TEXT_FONT' => '',
					'WATERMARK_TEXT_COLOR' => '',
					'WATERMARK_TEXT_SIZE' => '',
					'WATERMARK_TEXT_POSITION' => 'tl',
					'USE_WATERMARK_FILE' => 'N',
					'WATERMARK_FILE' => '',
					'WATERMARK_FILE_ALPHA' => '',
					'WATERMARK_FILE_POSITION' => 'tl',
					'WATERMARK_FILE_ORDER' => null,
				),
			),
			'SECTION_XML_ID' => array(
				'NAME' => 'Внешний код',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
			),
			'SECTION_CODE' => array(
				'NAME' => 'Символьный код',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => array(
					'UNIQUE' => 'N',
					'TRANSLITERATION' => 'N',
					'TRANS_LEN' => 100,
					'TRANS_CASE' => 'L',
					'TRANS_SPACE' => '-',
					'TRANS_OTHER' => '-',
					'TRANS_EAT' => 'Y',
					'USE_GOOGLE' => 'N',
				),
			),
			'LOG_SECTION_ADD' => array(
				'NAME' => 'LOG_SECTION_ADD',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => null,
			),
			'LOG_SECTION_EDIT' => array(
				'NAME' => 'LOG_SECTION_EDIT',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => null,
			),
			'LOG_SECTION_DELETE' => array(
				'NAME' => 'LOG_SECTION_DELETE',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => null,
			),
			'LOG_ELEMENT_ADD' => array(
				'NAME' => 'LOG_ELEMENT_ADD',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => null,
			),
			'LOG_ELEMENT_EDIT' => array(
				'NAME' => 'LOG_ELEMENT_EDIT',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => null,
			),
			'LOG_ELEMENT_DELETE' => array(
				'NAME' => 'LOG_ELEMENT_DELETE',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => null,
			),
			'XML_IMPORT_START_TIME' => array(
				'NAME' => 'XML_IMPORT_START_TIME',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => null,
				'VISIBLE' => 'N',
			),
			'DETAIL_TEXT_TYPE_ALLOW_CHANGE' => array(
				'NAME' => 'DETAIL_TEXT_TYPE_ALLOW_CHANGE',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => 'Y',
				'VISIBLE' => 'N',
			),
			'PREVIEW_TEXT_TYPE_ALLOW_CHANGE' => array(
				'NAME' => 'PREVIEW_TEXT_TYPE_ALLOW_CHANGE',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => 'Y',
				'VISIBLE' => 'N',
			),
			'SECTION_DESCRIPTION_TYPE_ALLOW_CHANGE' => array(
				'NAME' => 'SECTION_DESCRIPTION_TYPE_ALLOW_CHANGE',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => 'Y',
				'VISIBLE' => 'N',
			),
		);
	}


}
