<?php

namespace TAO\UField;

class UFieldHlblock extends AbstractUField
{
	public function type()
	{
		return 'hlblock';
	}

	public function setHlblockId($id)
	{
		$this->settings['HLBLOCK_ID'] = $id;
		return $this;
	}

	public function getHlblockId()
	{
		return $this->settings['HLBLOCK_ID'];
	}

	public function setDisplayField($fieldName)
	{
		$fieldId = $this->getFieldIdByName($fieldName);
		$this->settings['HLFIELD_ID'] = $fieldId;
		return $this;
	}

	protected function getFieldIdByName($fieldName) {
		if (empty($this->settings['HLBLOCK_ID'])) {
			return 0;
		}

		$hlBlock = \TAO\highloadBlockRepository::getById($this->settings['HLBLOCK_ID']);
		$fields = $hlBlock->getFields();
		if (isset($fields[$fieldName])) {
			return intval($fields[$fieldName]['ID']);
		}

		return 0;
	}

	/**
	 * @return HBEntity[]|HBEntity|null
	 *
	 */
	public function value()
	{
		if (empty($this->value)) {
			return null;
		}

		if (empty($this->settings['HLBLOCK_ID'])) {
			return null;
		}

		$hlBlock = \TAO\highloadBlockRepository::getById($this->settings['HLBLOCK_ID']);

		if (!$this->isMultiple()) {
			return $hlBlock->loadItem($this->value);
		}

		$selectedRows = $hlBlock->getRows([
			'filter' => [
				'ID' => $this->value,
			],
		]);
		return $selectedRows;
	}
}
