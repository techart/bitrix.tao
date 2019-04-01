<?php

namespace TAO\UField;

class UFieldHlblock extends AbstractUField
{
	public function type()
	{
		return 'hlblock';
	}

	public function value()
	{
		if (empty($this->settings['HLBLOCK_ID'])) {
			return null;
		}
		$hlBlock = \TAO\highloadBlockRepository::getById($this->settings['HLBLOCK_ID']);
		$selectedRows = $hlBlock->getRows([
			'filter' => [
				'ID' => $this->value,
			],
		]);
		return $selectedRows;
	}
}
