<?php

namespace TAO\PropertyContainer;

/**
 * Class L
 * @package TAO\PropertyContainer
 */
class L extends \TAO\PropertyContainer
{
	/**
	 * @return array
	 */
	public function variants()
	{
		$props = $this->item->infoblock()->properties();
		if (isset($props[$this->name()])) {
			$prop = $props[$this->name()];
			if (isset($prop['ITEMS'])) {
				return $prop['ITEMS'];
			}
		}
		return array();
	}

	/**
	 * @return array
	 */
	public function xmlIds()
	{
		if ($this->multiple()) {
			if ($vdata = $this->valueData()) {
				$out = array();
				foreach ($vdata as $k => $data) {
					if (isset($data['VALUE_XML_ID'])) {
						$xml_id = trim($data['VALUE_XML_ID']);
						if ($xml_id) {
							$out[$xml_id] = $xml_id;
						}
					}
				}
				return $out;
			}
		}
		return parent::value();
	}

	/**
	 * @param $xmlid
	 * @return $this
	 */
	public function delete($xmlid)
	{
		$xmlid = $this->enumIdToXMLId($xmlid);
		if ($this->multiple()) {
			if ($vdata = $this->valueData()) {
				foreach ($vdata as $k => $data) {
					if (isset($data['VALUE_XML_ID']) && $data['VALUE_XML_ID'] == $xmlid) {
						unset($vdata[$k]);
					}
				}
				$this->valueData($vdata);
			} else {
				if (!is_array($this->valueForAdd)) {
					$this->valueForAdd = array();
				}
				unset($this->valueForAdd[$xmlid]);
			}
		}
		return $this;
	}

	/**
	 * @param $xmlid
	 * @return $this
	 */
	public function add($xmlid)
	{
		$xmlid = $this->enumIdToXMLId($xmlid);
		if ($this->multiple()) {
			if ($vdata = $this->valueData()) {
				$exists = false;
				foreach ($vdata as $k => $data) {
					if (isset($data['VALUE_XML_ID']) && $data['VALUE_XML_ID'] == $xmlid) {
						$exists = true;
						break;
					}
				}
				if (!$exists) {
					$n = count($vdata) + 1;
					$vdata["n{$n}"] = array(
						'VALUE' => $this->checkValue($xmlid),
					);
				}
				$this->valueData($vdata);
			} else {
				if (!is_array($this->valueForAdd)) {
					$this->valueForAdd = array();
				}
				$this->valueForAdd[$xmlid] = $this->checkValue($xmlid);
			}
		}
		return $this;
	}

	/**
	 * @param $value
	 * @return $this|void
	 */
	public function set($value)
	{
		if ($this->multiple()) {
			if (!is_array($value)) {
				$value = array($value);
			}
			if ($vdata = $this->valueData()) {
				$value = array_combine($value, $value);
				$xmlIds = $this->xmlIds();
				foreach ($xmlIds as $v) {
					if (!isset($value[$v])) {
						$this->delete($v);
					}
				}
				foreach ($value as $v) {
					$this->add($v);
				}
			} else {
				$this->valueForAdd = array();
				foreach ($value as $v) {
					$this->add($v);
				}
			}
			return $this;
		}
		return parent::set($value);
	}

}