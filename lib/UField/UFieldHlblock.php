<?php

namespace TAO\UField;

class UFieldHlblock extends AbstractUField
{
	protected $HBValue = null;

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
	 * @param boolean $useCache
	 * @param array $cacheParams
	 *
	 * @return HBEntity[]|HBEntity|null
	 *
	 */
	public function value($useCache = false, $cacheParams = ['ttl' => 86400])
	{
		if (empty($this->value)) {
			return null;
		}

		if (empty($this->settings['HLBLOCK_ID'])) {
			return null;
		}

		if (!$this->HBValue) {
			$hlBlock = \TAO\highloadBlockRepository::getById($this->settings['HLBLOCK_ID']);

			if (!$this->isMultiple()) {
				$this->HBValue = $hlBlock->loadItem($this->value, $useCache, $cacheParams);
			} else {
				$params = [
					'filter' => [
						'ID' => $this->value,
					],
				];
				if ($useCache) {
					$params['cache'] = $cacheParams;
				}

				$selectedRows = $hlBlock->getRows($params);
				$this->HBValue = $selectedRows;
			}
		}

		return $this->HBValue;
	}
}
