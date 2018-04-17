<?php

namespace TAO\UField;

class UFieldIblockElement extends AbstractUField
{

	public function __construct($code, $name, $data)
	{
		parent::__construct($code, $name, $data);
		if ($data['SETTINGS']['IBLOCK_ID'] !== '') {
			$this->settings['IBLOCK_ID'] = $data['SETTINGS']['IBLOCK_ID'];
		}
	}

	public function type()
	{
		return 'iblock_element';
	}

	public function setIblockId($id)
	{
		$this->settings['IBLOCK_ID'] = $id;
		return $this;
	}

	public function getIblockId()
	{
		return $this->settings['IBLOCK_ID'];
	}

	public function value()
	{
		if (is_null($this->valueRaw())) {
			return null;
		}

		if ($this->isMultiple()) {
			return $this->multipleValue();
		} else {
			if ($this->settings['IBLOCK_ID'] !== '') {
				return \TAO::infoblock($this->settings['IBLOCK_ID'])->loadItem($this->valueRaw());
			} else {
				$result = \CIBlockElement::getList(
					[],
					['ID' => $this->valueRaw()],
					false,
					false,
					[]
				);

				while ($row = $result->getNext(true, false)) {
					$res = \CIBlockElement::GetProperty($row['IBLOCK_ID'], $row['ID']);
					while ($irow = $res->Fetch()) {
						$pid = $irow['ID'];
						$vid = $irow['PROPERTY_VALUE_ID'];
						if (!isset($properties[$pid])) {
							$properties[$pid] = array();
						}
						$properties[$pid][$vid] = $irow;
					}
					return \TAO::infoblock($row['IBLOCK_ID'])->makeItem($row, $properties);
				}
			}
		}
	}

	protected function multipleValue()
	{
		if ($this->settings['IBLOCK_ID'] !== '') {
			$elementList = [];
			$infoblock = \TAO::infoblock($this->settings['IBLOCK_ID']);
			foreach ($this->valueRaw() as $value) {
				$elementList[$value] = $infoblock->loadItem($value);
			}
		} else {
			$result = \CIBlockElement::getList(
				[],
				['ID' => $this->valueRaw()],
				false,
				false,
				[]
			);
			while ($row = $result->getNext()) {
				$res = \CIBlockElement::GetProperty($row['IBLOCK_ID'], $row['ID']);
				while ($irow = $res->Fetch()) {
					$pid = $irow['ID'];
					$vid = $irow['PROPERTY_VALUE_ID'];
					if (!isset($properties[$pid])) {
						$properties[$pid] = array();
					}
					$properties[$pid][$vid] = $irow;
				}
				$elementList[$row['ID']] = \TAO::infoblock($row['IBLOCK_ID'])->makeItem($row, $properties);
			}
		}
		return $elementList;
	}
}
