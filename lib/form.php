<?php

namespace TAO;

	/**
	 * Class Form
	 * @package TAO
	 */
/**
 * Class Form
 * @package TAO
 */
class Form
{
	/**
	 * @var array
	 */
	public $errors = array();
	/**
	 * @var array
	 */
	public $values = array();

	/**
	 * @var array
	 */
	protected static $types = array('S' => 'input', 'N' => 'input', 'L' => 'select', 'L-M' => 'checkboxes', 'E' => 'element', 'E-M' => 'elements', 'G' => 'section', 'G-M' => 'sections', 'F' => 'upload');

	/**
	 * @var mixed
	 */
	protected $infoblock;
	/**
	 * @var bool|string
	 */
	protected $name;
	/**
	 * @var
	 */
	protected $fid;
	/**
	 * @var
	 */
	protected $properties;
	/**
	 * @var
	 */
	protected $preparedFields;
	/**
	 * @var
	 */
	protected $preparedServiceFields;
	/**
	 * @var bool
	 */
	protected $multipart = false;
	/**
	 * @var
	 */
	protected $item;

	/**
	 * @var
	 */
	public $bundle;

	protected $serviceOptions = array();

	/**
	 * @var array
	 */
	protected $options = array(
		'infoblock' => null,
		'ajax' => false,
		'error_title' => 'Ошибка отправки формы',
		'layout' => 'table',
		'return_url' => false,
		'on_ok' => false,
		'on_error' => false,
		'before_submit' => false,
		'ok_message' => 'ajax_form_message_ok',
		'mail_event' => false,
		'post_active' => false,
		'show_labels' => true,
		'show_placeholders' => false,
		'submit_text' => 'Отправить',
	);

	/**
	 * Form constructor.
	 * @param string|false $name
	 */
	public function __construct($name = false)
	{
		foreach ($this->options() as $k => $v) {
			$this->options[$k] = $v;
		}

		if (!$name) {
			$name = \TAO::unchunkCap(str_replace("TAO\\Forms\\", '', get_class($this)));
		}
		$this->name = $name;
		$this->fid = uniqid($this->name);
		$this->serviceOptions['taoform'] = $this->name;
		$this->serviceOptions['lang'] = \TAO::getSiteLang();
		foreach ($this->serviceOptions() as $k => $v) {
			$this->serviceOptions[$k] = $v;
		}

		$infoblock = $this->infoblock();
		if (is_string($infoblock) && \TAO::getInfoblockId($infoblock)) {
			$this->infoblock = \TAO::getInfoblock($infoblock);
		}
	}

	/**
	 * @return array
	 */
	protected function serviceOptions()
	{
		return array();
	}

	/**
	 * @return array
	 */
	protected function options()
	{
		return array();
	}

	/**
	 * @param $name
	 * @return null
	 */
	public function option($name)
	{
		return isset($this->options[$name]) ? $this->options[$name] : null;
	}

	/**
	 * @param $name
	 * @return null
	 */
	public function serviceOption($name)
	{
		return isset($this->serviceOptions[$name]) ? $this->serviceOptions[$name] : null;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return $this
	 */
	public function setOption($name, $value)
	{
		$this->options[$name] = $value;
		return $this;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return $this
	 */
	public function setServiceOption($name, $value)
	{
		$this->serviceOptions[$name] = $value;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function infoblock()
	{
		return $this->options['infoblock'];
	}

	/**
	 * @return mixed
	 */
	public function ajax()
	{
		return $this->options['ajax'];
	}

	/**
	 * @return array
	 */
	public function disabledFields()
	{
		return array();
	}

	/**
	 * @return $this
	 */
	public function disableFields()
	{
		foreach (func_get_args() as $f) {
			$f = trim($f);
			$this->setOption("disable_field_{$f}", true);
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	public function enableFields()
	{
		foreach (func_get_args() as $f) {
			$f = trim($f);
			$this->setOption("disable_field_{$f}", false);
		}
		return $this;
	}


	/**
	 * @return array
	 */
	public function properties()
	{
		if ($this->infoblock) {
			$properties = $this->infoblock->properties();
			foreach ($this->disabledFields() as $f) {
				$this->setOption("disable_field_{$f}", true);
			}
			foreach (array_keys($properties) as $f) {
				if ($this->option("disable_field_{$f}")) {
					unset($properties[$f]);
				}
			}
			return $properties;
		}
		return array();
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function propertyData($name)
	{
		if (is_null($this->properties)) {
			$this->properties = $this->properties();
		}
		if (isset($this->properties[$name])) {
			return $this->properties[$name];
		}
	}

	/**
	 * @param $name
	 * @return string
	 */
	public static function formClassName($name)
	{
		return "App\\Forms\\{$name}";
	}

	/**
	 * @param $name
	 * @return string
	 */
	public static function formClassFile($name)
	{
		return $_SERVER['DOCUMENT_ROOT'] . "/local/forms/{$name}.php";
	}

	/**
	 * @return array
	 */
	public function styles()
	{
		return array();
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function fieldStyle($name)
	{
		$method = 'get' . \TAO::unchunkCap($name) . 'Style';
		if (method_exists($this, $method)) {
			return $this->$method();
		}
		$styles = $this->styles();
		if (is_array($styles) && isset($styles[$name])) {
			return $styles[$name];
		}
		$data = $this->propertyData($name);
		if (isset($data['style'])) {
			return $data['style'];
		}
	}

	/**
	 * @param $name
	 * @return bool|string
	 */
	public function fieldType($name)
	{
		$data = $this->propertyData($name);
		if (isset($data['type'])) {
			$type = $data['type'];
		} elseif (isset($data['PROPERTY_TYPE'])) {
			$type = $data['PROPERTY_TYPE'];
			if (isset($data['MULTIPLE']) && $data['MULTIPLE'] == 'Y') {
				$type .= '-M';
			}
			if ($type == 'S' && isset($data['ROW_COUNT']) && (int)$data['ROW_COUNT'] > 1) {
				return 'textarea';
			}
			$type = isset(self::$types[$type]) ? self::$types[$type] : false;
		} else {
			$type = 'input';
		}
		if ($type == 'upload') {
			$this->multipart = true;
		}
		return $type;
	}

	public function fieldService($name, &$data) {
		$tpl = $this->viewPath('fields/'.$data['type']);
		$value = $data['value'];
		ob_start();
		include $tpl;
		$data['input'] = ob_get_clean();
	}

	/**
	 * @param $name
	 * @param $data
	 */
	public function fieldInput($name, &$data)
	{
		$data['code'] = $name;

		if (isset($data['input'])) {
			return;
		}

		if (isset($data['USER_TYPE'])) {
			$ut = \CIBlockProperty::GetUserType($data['USER_TYPE']);
			if (is_array($ut) && isset($ut['GetPublicEditHTML']) && is_callable($ut['GetPublicEditHTML'])) {
				$data['input'] = call_user_func($ut['GetPublicEditHTML'], $data, array('VALUE' => ''), array('VALUE' => $name));
			}
			$type = strtolower($data['USER_TYPE']);
			if ($type == 'html') {
				$type = 'textarea';
			}
			$data['processed_type'] = $type;
		} else {
			$type = $this->fieldType($name);
			if ($type) {
				$data['processed_type'] = $type;
				$tpl = $this->viewPath("fields/{$type}");
				if ($tpl) {
					$data['required'] = $this->fieldRequired($name);

					$style = $this->fieldStyle($name);
					$errorClass = isset($this->errors[$name]) ? ' tao-error-field' : '';

					$prep = "{$type}TypePreprocess";
					if (method_exists($this, $prep)) {
						$this->$prep($data);
					}

					$prep = "{$name}Preprocess";
					if (method_exists($this, $prep)) {
						$this->$prep($data);
					}
					$tagStyle = empty($style) ? '' : " style=\"{$style}\"";
					$value = isset($this->values[$name]) ? $this->values[$name] : null;
					if (isset($data['value'])) {
						$value = $data['value'];
					}

					$required = $data['required'];
					$placeholder = isset($data['placeholder']) ? $data['placeholder'] : false;
					if (!$placeholder && $this->option('show_placeholders')) {
						$placeholder = $data['NAME'];
					}

					ob_start();
					include $tpl;
					$data['input'] = ob_get_clean();
					$data['error_class'] = $errorClass;
				}
			}
		}
	}

	/**
	 * @param $data
	 */
	protected function elementTypePreprocess(&$data)
	{
		$infoblock = false;
		if (isset($data['LINK_IBLOCK_ID'])) {
			$infoblock = \TAO::getInfoblock((int)$data['LINK_IBLOCK_ID']);
		} elseif (isset($data['LINK_IBLOCK_CODE'])) {
			$infoblock = \TAO::getInfoblock($data['LINK_IBLOCK_CODE']);
		}
		if ($infoblock) {
			$items = array();
			foreach ($infoblock->getRows() as $row) {
				$items[$row['ID']] = $row['NAME'];
			}
			$data['ITEMS'] = $items;
		}
	}

	/**
	 * @param $data
	 */
	protected function sectionTypePreprocess(&$data)
	{
		$infoblock = false;
		if (isset($data['LINK_IBLOCK_ID'])) {
			$infoblock = \TAO::getInfoblock((int)$data['LINK_IBLOCK_ID']);
		} elseif (isset($data['LINK_IBLOCK_CODE'])) {
			$infoblock = \TAO::getInfoblock($data['LINK_IBLOCK_CODE']);
		}
		if ($infoblock) {
			$items = array();
			$args = array(
				'filter' => array(
					'ACTIVE' => 'Y'
				)
			);
			foreach ($infoblock->getSections($args) as $row) {
				$items[$row['ID']] = $row['NAME'];
			}
			$data['ITEMS'] = $items;
		}
	}

	/**
	 * @param $data
	 */
	protected function sectionsTypePreprocess(&$data)
	{
		return $this->sectionTypePreprocess($data);
	}

	/**
	 * @param $data
	 */
	protected function elementsTypePreprocess(&$data)
	{
		return $this->elementTypePreprocess($data);
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	public function fieldCaption($name)
	{
		$data = $this->propertyData($name);
		return isset($data['caption']) ? $data['caption'] : $data['NAME'];;
	}

	/**
	 * @return array
	 */
	protected function prepareFields()
	{
		if (is_array($this->preparedFields)) {
			return $this->preparedFields;
		}
		$fields = array();


		foreach ($this->properties() as $field => $data) {
			$this->fieldInput($field, $data);
			if (!isset($data['input'])) {
				continue;
			}
			$data['caption'] = $this->fieldCaption($field);
			$fields[$field] = $data;
		}

		\TAO::sort($fields);
		$this->preparedFields = $fields;
		return $fields;
	}

	protected function prepareServiceFields() {
		if (is_array($this->preparedServiceFields)) {
			return $this->preparedServiceFields;
		}
		$fields = array();
		foreach($this->serviceOptions as $optionName => $value) {
			$data = array(
				'type' => 'service',
				'value' => $value,
			);
			$this->viewPath("fields/service");
			$this->fieldService($optionName, $data);
			$fields[$optionName] = $data;
		}
		$this->preparedServiceFields = $fields;
		return $fields;
	}
	
	public function getPreparedField($name)
	{
		$fields = $this->prepareFields();
		return isset($fields[$name])? $fields[$name] : null;
	}

	/**
	 * @return bool
	 */
	public function isValid()
	{
		return count($this->errors) == 0;
	}

	/**
	 * @return string
	 */
	public function subdir()
	{
		return \TAO::unchunkCap($this->name);
	}

	/**
	 * @param $dir
	 * @return array
	 */
	protected function fileDirs($dir)
	{
		$dirs = array();
		$sub = $this->subdir();
		if ($this->bundle) {
			if ($sub) {
				$dirs[] = $this->bundle->localPath("{$dir}/forms/{$sub}");
			}
			$dirs[] = $this->bundle->localPath("{$dir}/forms");
		}
		if ($sub) {
			$dirs[] = \TAO::localDir("{$dir}/forms/{$sub}");
		}
		$dirs[] = \TAO::localDir("{$dir}/forms");
		$dirs[] = \TAO::taoDir("{$dir}/forms");
		return $dirs;
	}

	/**
	 * @param $file
	 * @return mixed
	 */
	public function viewPath($file)
	{
		return \TAO::filePath($this->fileDirs('views'), "{$file}.phtml");
	}

	/**
	 * @param $file
	 * @return mixed|string
	 */
	protected function styleUrl($file)
	{
		return \TAO::fileUrl($this->fileDirs('styles'), "{$file}.css");
	}

	/**
	 * @param $file
	 * @return mixed|string
	 */
	protected function scriptUrl($file)
	{
		return \TAO::fileUrl($this->fileDirs('scripts'), "{$file}.js");
	}

	/**
	 * @return string
	 */
	public function formUrl()
	{
		$params = array (
			'name' => $this->name,
			'options' => $this->options,
		);
		return '/local/vendor/techart/bitrix.tao/api/form.php?'.http_build_query($params);
	}

	/**
	 *
	 */
	public function useStyles()
	{
		$css = $this->styleUrl("form");
		if ($css) {
			\TAO::useStyle($css);
		}
	}

	/**
	 *
	 */
	public function useScripts()
	{
		if ($this->ajax()) {
			$js = $this->scriptUrl("jquery.form");
			\TAO::useScript($js);
			$js = $this->scriptUrl("form");
			\TAO::useScript($js);
		}
	}

	public function renderByParams() {
		$name = $_GET['name'];
		$options = $_GET['options'];
		$form = \TAO::form($name);
		foreach ($options as $name => $value) {
			$form->setOption($name, $value);
		}
		return $form->render();
	}

	/**
	 * @return string
	 */
	public function render()
	{
		
		$name = $this->name;
		$sname = \TAO::unchunkCap($name);

		$fields = $this->prepareFields();
		$serviceFields = $this->prepareServiceFields();

		$layout = $this->option('layout');

		$templateForm = $this->viewPath('form');
		$templateLayout = $this->viewPath("layout-{$layout}");

		$action = '/local/vendor/techart/bitrix.tao/api/' . ($this->ajax() ? 'form-ajax.php' : 'form-post.php');

		if ($this->option('type') == 'frontend') {
			return $this->frontendRender(array(
				'fields' => $fields,
				'serviceFields' => $serviceFields,
				'action' => $action,
				'form' => $this,
			));
		}


		$this->useStyles();
		$this->useScripts();

		ob_start();
		include($templateForm);
		$content = ob_get_clean();
		return $content;
	}
	
	protected function frontendRender($content)
	{
		$name = $this->name;
		$name = \TAO::unchunkCap($name);
		$block = "common/form-{$name}";
		if (!\TAO::frontend()->exists($block)) {
			$block = "common/form-default";
		}
		
		$body = \TAO::frontend()->render($block, $context);
		$body = $this->replaceInsertions($body);
		
		
		$templateForm = $this->viewPath('frontend');
		
		ob_start();
		include($templateForm);
		$content = ob_get_clean();
		return $content;
	}
	
	protected function replaceInsertions($body)
	{
		$body = preg_replace_callback('{%form-([a-z0-9_-]+)\{(.*?)\}}sm', function($m) {
			$func = $m[1];
			$args = trim($m[2]);
			
			if ($func == 'title') {
				return $this->frontendFormTitle($args);
			} elseif ($func == 'subtitle') {
				return $this->frontendFormSubtitle($args);
			} elseif ($func == 'group') {
				return $this->frontendFormGroup($args);
			} elseif ($func == 'endgroup') {
				return '</div>';
			} elseif ($func == 'fields') {
				return $this->frontendAllFields();
			} elseif (preg_match('{^field-(.+)$}', $func, $m)) {
				$name = $m[1];
				return $this->frontendField($name, $args);
			}
			
		}, $body);
		return $body;
	}
	
	protected function frontendFormGroup($mods)
	{
		$classes = 'b-form__field';
		foreach(explode(',', $mods) as $mod) {
			$mod = trim($mod);
			if ($mod) {
				$classes .= " b-form__field--{$mod}";
			}
		}
		return "<div class=\"{$classes}\">";
	}
	
	protected function frontendFormTitle($title)
	{
		return "<div class=\"b-form__title\">{$title}</div>";
	}
	
	protected function frontendFormSubtitle($title)
	{
		return "<div class=\"b-form__subtitle\">{$title}</div>";
	}
	
	protected function frontendAllFields()
	{
		$out = '';
		$fields = $this->prepareFields();
		foreach($fields as $name => $data) {
			$out .= $this->frontendField($name);
		}
		return $out;
	}
	
	protected function frontendField($name, $args = array())
	{
		$field = new FrontendField($this, $name, $args);
		return $field->render();
	}

	/**
	 * @return array
	 */
	public function required()
	{
		return array();
	}

	/**
	 * @param $name
	 * @return string
	 */
	public function fieldRequired($name)
	{
		$data = $this->propertyData($name);
		if (isset($data['required'])) {
			return $data['required'];
		}
		$r = $this->required();
		if (isset($r[$name])) {
			return $r[$name];
		}
		if (isset($data['IS_REQUIRED']) && $data['IS_REQUIRED'] == 'Y') {
			$caption = $this->fieldCaption($name);
			return "Заполните поле \"{$caption}\"";
		}
	}

	/**
	 * @param $values
	 * @return array
	 */
	public function validate($values)
	{
		$errors = array();
		$fill = array();
		foreach ($this->properties() as $name => $data) {
			$fill[$name] = false;
			if (isset($values[$name])) {
				$fill[$name] = is_string($values[$name]) ? trim($values[$name]) != '' : !empty($values[$name]);
			}
			if ($s = $this->fieldRequired($name)) {
				if (!$fill[$name]) {
					$errors[$name] = $s;
				}
			}
		}
		foreach ($this->required() as $names => $message) {
			$m = explode(',', $names);
			if (count($m) > 1) {
				$valid = false;
				$fields = array();
				foreach ($m as $name) {
					$name = trim($name);
					if ($name != '') {
						$fields[$name] = $name;
						if ($fill[$name]) {
							$valid = true;
						}
					}
				}
				if (!$valid) {
					foreach ($fields as $field) {
						$errors[$field] = $message;
						$message = '';
					}
				}
			}
		}
		return $errors;
	}

	/**
	 * @param $name
	 * @return string
	 */
	public function uploadPath($name)
	{
		return "tao/form/{$this->name}";
	}

	/**
	 * @return array
	 */
	protected function mailEventArgs()
	{
		$args = array();
		foreach ($this->prepareFields() as $name => $data) {
			$value = $this->values[$name];
			$type = $this->fieldType($name);
			if ($type == 'select' || $type == 'element' || $type == 'checkboxes' || $type == 'elements') {
				$items = $data['ITEMS'];
				if (!is_array($value)) {
					$value = array($value);
				}
				$values = array();
				foreach ($value as $n) {
					if (isset($items[$n])) {
						$values[$n] = is_array($items[$n]) ? $items[$n]['VALUE'] : $items[$n];
					}
				}
				$value = implode(', ', $values);
			}
			if ($type == 'upload') {
				$args['_attaches'][] = $value;
			}
			$args[$name] = $value;
		}
		return $args;
	}

	/**
	 *
	 */
	protected function mailEvent()
	{
		$eventType = $this->option('mail_event');
		if (empty($eventType)) {
			return;
		}
		$args = $this->mailEventArgs();
		if (!$args['_attaches']) {
			$args['_attaches'] = array();
		}
		\CEvent::Send($eventType, SITE_ID, $args, 'N', '', $args['_attaches']);
	}

	/**
	 *
	 */
	protected function afterProcess()
	{
	}

	/**
	 * @param $args
	 * @return mixed|string
	 */
	public function postTitle()
	{
		$title = '';
		if (isset($this->values['name'])) {
			$title = trim($this->values['name']);
		}
		if (isset($this->values['email'])) {
			$title .= $title != '' ? ' / ' : '';
			$title .= trim($this->values['email']);
		}
		if (empty($title)) {
			$title = 'Post ' . date('d.m.Y - H:i');
		}
		return $title;

	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function formObject($name, $check = true)
	{
		$path = \TAO::localDir("forms/{$name}.php");
		if (is_file($path)) {
			include_once($path);
			$class = "\\App\\Form\\{$name}";
			return new $class($name);
		}
		foreach (\TAO::bundles() as $bundle) {
			$class = $bundle->hasClass("Form\\{$name}");
			if ($class) {
				$object = new $class($name);
				$object->bundle = $bundle;
				return $object;
			}
		}
		if ($check) {
			print "Unknown form '{$name}'";
			die;
		}
	}

	/**
	 * @return array
	 */
	public function process()
	{
		$fields = $this->prepareFields();
		$serviceFields = $this->prepareServiceFields();
		$values = array();
		$item = $this->infoblock ? $this->infoblock->makeItem() : false;
		$errors = array();
		$uploads = array();

		foreach ($fields as $name => $data) {
			$value = null;
			$type = $this->fieldType($name);
			if ($type == 'upload') {
				if (isset($_FILES[$name])) {
					$value = $_FILES[$name];
					$uploads[$name] = $value;
				}
			} else {
				if (isset($_POST[$name])) {
					$value = $_POST[$name];
				}
				if ($item) {
					$item[$name] = $value;
				}
			}
			$values[$name] = $value;
		}
		$this->values = $values;

		foreach ($serviceFields as $name => $data) {
			if (isset($_POST['service'][$name])) {
				$this->setServiceOption($name, $_POST['service'][$name]);
			}
		}

		$v = $this->validate($values);
		if (is_array($v)) {
			foreach ($v as $field => $message) {
				$errors[$field] = $message;
			}
		}

		if (count($errors) == 0) {
			if ($item) {
				foreach ($uploads as $name => $value) {
					$value = \CFile::SaveFile($value, $this->uploadPath($name));
					$item[$name] = $value;
					$this->values[$name] = $value;
				}
				$item['ACTIVE'] = $this->option('post_active') ? 'Y' : 'N';
				$item['NAME'] = $this->postTitle();
				$datetime = new \Bitrix\Main\Type\DateTime;
				$item['DATE_ACTIVE_FROM'] = (string)$datetime;
				$item->save();
				if (is_string($item->error) && trim($item->error) != '') {
					foreach (explode('<br>', $item->error) as $e) {
						$e = trim($e);
						if ($e != '') {
							$errors[] = $e;
						}
					}
				}
			}
		}

		if (count($errors) == 0) {
			$this->item = $item;
			$this->afterProcess();
			$this->mailEvent();
		}


		$this->errors = $errors;
		$this->preparedFields = null;

		return array(
			'name' => $this->name,
			'form' => $this,
			'item' => $item,
			'values' => $values,
			'result' => count($errors) == 0 ? 'ok' : 'error',
			'errors' => $errors,
		);
	}

	/**
	 * @return array|string
	 */
	public static function processPost()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			return 'ERROR: Invalid request!';
		}
		if (!isset($_POST['service']['taoform']) && !isset($_POST['taoform'])) {
			return 'ERROR: Form not defined!';
		}

		$name = trim($_POST['service']['taoform']);
		if((string) $name == '') {
			$name = trim($_POST['taoform']);
		}

		$form = \TAO::form($name);
		if (!$form) {
			return 'ERROR: Unknown form!';
		}
		return $form->process();
	}
}
