<?php

namespace TAO\UField;

use CUserFieldEnum;

class UFieldEnumeration extends AbstractUField
{
	public function type()
	{
		return 'enumeration';
	}

	public function getFields() {
		$fields = [];
		$db_res = CUserFieldEnum::GetList(array(), array(
			"ID" => $this->valueRaw(),
		));

		while ($field = $db_res->GetNext()) {
			$fields[] = $field;
		}

		return $fields;
	}

	public function value()
	{
		$fields = $this->getFields();
		if ($this->isMultiple()) {
			$values = [];
			foreach ($fields as $field) {
				$values[] = $field['VALUE'];
			}

			return $values;
		} else {
			return reset($fields)['VALUE'];
		}
	}

	public function getXmlId()
	{
		$fields = $this->getFields();

		if ($this->isMultiple()) {
			$values = [];
			foreach ($fields as $field) {
				$values[] = $field['XML_ID'];
			}

			return $values;
		} else {
			return reset($fields)['XML_ID'];
		}
	}

	public function variants() {
		$obEnum = new CUserFieldEnum;
		$rsEnum = $obEnum->GetList(array(), array("USER_FIELD_ID" => $this->data()['ID']));

		$enum = array();
		while($arEnum = $rsEnum->Fetch())
		{
			$enum[$arEnum['XML_ID']] = $arEnum;
		}

		return $enum;
	}

	public function addVariant($xmlId, $value) {
		$obEnum = new CUserFieldEnum;
		$obEnum->SetEnumValues($this->data()['ID'], array(
			"n" => array(
				"VALUE" => $value,
				"XML_ID" => $xmlId,
			),
		));
	}

	public function addVariants($variants = array()) {
		foreach ($variants as $xmlId => $value) {
			$this->addVariant($xmlId, $value);
		}
	}

	public function delVariant($xmlId) {
		$id = $this->getIdByXmlId($xmlId);
		if (!is_null($id)) {
			$obEnum = new CUserFieldEnum;
			$obEnum->SetEnumValues($this->data()['ID'], array(
				$id => array(
					"DEL" => "Y",
				),
			));
		}
	}

	protected function getIdByXmlId($xmlId) {
		$variants = $this->variants();
		if (isset($variants[$xmlId])) {
			return $variants[$xmlId]['ID'];
		}
		return null;
	}
}