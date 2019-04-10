<?php

namespace TAO\UField;

class UFieldHlblock extends AbstractUField
{
	public function type()
	{
		return 'hlblock';
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
