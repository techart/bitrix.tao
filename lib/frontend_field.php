<?php

namespace TAO;

class FrontendField
{
	protected $form;
	protected $name;
	protected $args;
	protected $data;
	protected $type;
	
	public function __construct($form, $name, $args)
	{
		$this->form = $form;
		$this->name = $name;
		$this->args = is_string($args)? $this->parseArgs($args) : $args;
		$this->data = $this->form->getPreparedField($this->name);
	}
	
	protected function parseArgs($src)
	{
		$out = array(
			'mods' => array(),
		);
		foreach(explode(',', $src) as $item) {
			$item = trim($item);
			if ($item) {
				if ($item == 'disabled') {
					$out['disabled'] = true;
				} elseif (preg_match('{^[a-z0-9_-]+$}', $item)) {
					$out['mods'][] = $item;
				} elseif (preg_match('{^#([a-z0-9_-]+)$}', $item, $m)) {
					$out['input_id'] = $item;
				} elseif (preg_match('{^([a-z0-9_-]+)\s*=\s*(.+)$}', $item, $m)) {
					$name = $m[1];
					$value = $m[2];
					$out[$name] = $value;
				}
			}
		}
		return $out;
	}
	
	public function arg($name, $default = null)
	{
		if (is_array($name)) {
			foreach($name as $item) {
				$value = $this->arg($item);
				if (!is_null($value)) {
					return $value;
				}
			}
			return $default;
		}
		if (isset($this->args[$name])) {
			return $this->args[$name];
		}
		if (isset($this->data[$name])) {
			return $this->data[$name];
		}
		return $default;
	}
	
	public function name()
	{
		return $this->name;
	}
	
	public function placeholder()
	{
		return $this->arg('placeholder', '');
	}
	
	public function requiredErrorMessage()
	{
		$message = $this->arg('required');
		if (is_string($message)) {
			return $message;
		}
		return $this->form->fieldRequired($this->name);
	}
	
	public function isRequired()
	{
		return !empty($this->requiredErrorMessage());
	}
	
	public function emailErrorMessage()
	{
		$message = $this->arg('email');
		if (is_string($message)) {
			return $message;
		}
		if ($this->type == 'email') {
			return 'Incorrect E-Mail';
		}
	}
	
	public function isEmail()
	{
		return !empty($this->emailErrorMessage()) || $this->type == 'email';
	}
	
	public function isDisabled()
	{
		return $this->arg('disabled');
	}
	
	public function inputId()
	{
		return $this->arg('input_id', $this->name);
	}
	
	public function label()
	{
		return $this->arg(['label', 'caption'], $this->name);
	}
	
	public function items()
	{
		$out = array();
		foreach($this->arg('ITEMS', array()) as $value => $item) {
			if (is_array($item)) {
				$item = $item['VALUE'];
			}
			$out[$value] = $item;
		}
		return $out;
	}
	
	public function optionsTags()
	{
		$out = '<option value="NULL"></option>';
		foreach($this->items() as $value => $item) {
			$out .= "<option value=\"{$value}\">{$item}</option>";
		}
		return $out;
	}
	
	public function render()
	{
		
		if (!$this->data) {
			return '';
		}
		
		$processedType = isset($this->data['processed_type'])? $this->data['processed_type'] : false;
		
		if ($processedType == 'select') {
			$vals = implode($this->items());
			if ($vals == 'YN' || $vals == 'NY') {
				$processedType = 'checkbox';
			}
		} elseif ($processedType == 'input') {
			if ($this->name == 'phone') {
				$processedType = 'phone';
			} elseif ($this->name == 'email') {
				$processedType = 'email';
			}
		}
		
		
		$type = isset($this->args['type'])? $this->args['type'] : $processedType;
		if (!$type) {
			return '';
		}
		
		$this->type = $type;
		
		$tpl = $this->form->viewPath("frontend-fields/{$type}");
		
		ob_start();
		include($tpl);
		$content = ob_get_clean();
		return $content;
	}
	
	public function modsClass()
	{
		$mods = $this->arg('mods', array());
		$out = '';
		foreach($mods as $mod) {
			$out .= " b-form__field--{$mod}";
		}
		return $out;
	}
}
