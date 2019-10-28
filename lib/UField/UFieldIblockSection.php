<?php

namespace TAO\UField;

class UFieldIblockSection extends AbstractUField
{

	public function __construct($code, $name, $data)
	{
		parent::__construct($code, $name, $data);
		if ($data['SETTINGS']['IBLOCK_ID'] !== '') {
			$this->settings['IBLOCK_ID'] = $data['SETTINGS']['IBLOCK_ID'];
		}
	}

	public function setIblockId($id)
	{
		$this->settings['IBLOCK_ID'] = $id;
		return $this;
	}

	public function getIblockID()
	{
		return $this->settings['IBLOCK_ID'];
	}

	public function type()
	{
		return 'iblock_section';
	}

	public function value()
	{
		if ($this->isMultiple()) {
			return $this->multipleValue();
		} else {
			if ($this->settings['IBLOCK_ID'] !== '') {
				return \TAO::infoblock($this->settings['IBLOCK_ID'])->getSectionById($this->valueRaw());
			} else {
				$result = \CIBlockSection::getByID($this->valueRaw());
				if ($row = $result->getNext()) {
					return new \TAO\Section($row);
				}
			}
		}
	}

	protected function multipleValue()
	{
		if ($this->settings['IBLOCK_ID'] !== '') {
			return \TAO::infoblock($this->settings['IBLOCK_ID'])->getSections([
				'filter' => ['ID' => $this->valueRaw()]
			]);
		} else {
			$sectionList = [];
			foreach ($this->valueRaw() as $value) {
				$result = \CIBlockSection::getByID($value);
				if ($row = $result->getNext()) {
					$sectionList[] = new \TAO\Section($row);
				}
			}

			return $sectionList;
		}
	}
}
