<?php

namespace TAO;

/**
 * Class InfoblockType
 * @package TAO
 */
class InfoblockType
{

	public static function check($type, $data = false)
	{
		$data = self::canonizeData($type, $data);
		$type = trim($data['ID']);
		$result = \CIBlockType::GetByID($type);
		$cdata = $result->Fetch();
		if ($cdata) {
			$cdata['LANG'] = array();
			foreach (array_keys(\TAO::getLangs()) as $lang) {
				$l = \CIBlockType::GetByIDLang($type, $lang);
				$cdata['LANG'][$lang] = array(
					'NAME' => $l['NAME'],
					'ELEMENT_NAME' => $l['ELEMENT_NAME'],
					'SECTION_NAME' => $l['SECTION_NAME'],
				);
			}
			$cdata = \TAO::mergeArgs($cdata, $data);
			self::updateType($cdata);
		} else {
			self::addNewType($data);
		}
	}

	public static function canonizeData($type, $data)
	{
		if (!$data) {
			$data = $type;
		}

		if (is_string($data)) {
			$data = array('NAME' => $data);
		}

		if (!isset($data['ID'])) {
			$data['ID'] = $type;
		}

		if (!isset($data['NAME'])) {
			$data['NAME'] = $type;
		}

		if (!isset($data['IN_RSS'])) {
			$data['IN_RSS'] = 'N';
		}

		if (!isset($data['SORT'])) {
			$data['SORT'] = '500';
		}

		if (!isset($data['EDIT_FILE_BEFORE'])) {
			$data['EDIT_FILE_BEFORE'] = '';
		}

		if (!isset($data['EDIT_FILE_AFTER'])) {
			$data['EDIT_FILE_AFTER'] = '';
		}

		$ldata = isset($data['LANG']) ? $data['LANG'] : array();

		foreach (array_keys(\TAO::getLangs()) as $lang) {
			$ld = isset($ldata[$lang]) ? $ldata[$lang] : array();
			if (!isset($ld['NAME'])) {
				$ld['NAME'] = isset($data["NAME_{$lang}"]) ? $data["NAME_{$lang}"] : $data["NAME"];
			}
			$ldata[$lang] = $ld;
		}
		$data['LANG'] = $ldata;
		unset($data['NAME']);

		return $data;
	}

	/**
	 * @return int
	 */
	public function sort()
	{
		return 500;
	}

	/**
	 * @throws \TAOAddTypeException
	 */
	protected static function addNewType($data)
	{
		global $DB;
		$DB->StartTransaction();
		$o = new \CIBlockType;
		$res = $o->Add($data);
		if (!$res) {
			$DB->Rollback();
			throw new \TAOAddTypeException("Error create type " . $data['ID']);
		} else {
			$DB->Commit();
		}
	}

	/**
	 * @throws \TAOUpdateTypeException
	 */
	protected static function updateType($data)
	{
		global $DB;
		$DB->StartTransaction();
		$o = new \CIBlockType;
		$res = $o->Update($data['ID'], $data);
		if (!$res) {
			$DB->Rollback();
			throw new \TAOUpdateTypeException("Error update type " . $data['ID']);
		} else {
			$DB->Commit();
		}
	}
}
