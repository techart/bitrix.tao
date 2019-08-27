<?php
/*
TODO

получить список id пропертей
property code 2 id


*/

namespace TAO;
\CModule::IncludeModule("iblock");

/**
 * Class Infoblock
 * @package TAO
 */
abstract class Infoblock
{

	/**
	 * @var array
	 */
	static $entityClasses = array();
	/**
	 * @var array
	 */
	static $sectionClasses = array();

	/**
	 * @var bool
	 */
	protected $mnemocode = false;
	/**
	 * @var array|bool
	 */
	protected $data = false;

	/**
	 * @var bool
	 */
	protected $processed = false;

	/**
	 * @var int
	 */
	protected $uniqCounter = 0;
	/**
	 * @var
	 */
	protected $editAreaId;

	/**
	 * @var bool
	 */
	protected $entityClassName = false;
	/**
	 * @var bool
	 */
	protected $sectionClassName = false;
	/**
	 * @var
	 */
	protected $_bundle;

	/**
	 * @var array
	 */
	static $classes = array();
	/**
	 * @var array
	 */
	static $code2type = array();

	/**
	 * @var
	 */
	protected $preDescription;
	/**
	 * @var
	 */
	protected $postDescription;

	/**
	 * @var array
	 */
	protected $currentProperties = array();

	/**
	 * @var array
	 */
	protected $currentPropertiesCodes = array();

	/**
	 * @var array
	 */
	protected $propertyKeys;

	/**
	 * @param $type
	 * @param $code
	 * @param $class
	 */
	public static function processSchema($type, $code, $class)
	{
		self::$code2type[$code] = $type;
		self::$classes[$code] = $class;
		$path = \TAO::getClassFile($class);
		if (\TAO::cache()->fileUpdated($path)) {
			$infoblock = new $class($code);
			//var_dump($infoblock);die;
			$infoblock->process();
		}
	}

	/**
	 * @param $code
	 * @return string
	 */
	public static function getClassName($code)
	{
		if (isset(self::$classes[$code])) {
			return self::$classes[$code];
		}
		$code = \TAO::normalizeMnemocode($code);
		$app = '\\App\\Infoblock\\' . $code;
		if (is_file(\TAO::getClassFile($app))) {
			return $app;
		}
		return '\\TAO\\CachedInfoblock\\' . $code;
	}

	/**
	 * Infoblock constructor.
	 * @param $code
	 */
	public function __construct($code)
	{
		$this->setMnemocode($code);
		$this->data = $this->loadData();
		if (!$this->data) {
			$this->addNewInfoblock();
		} else {
		}
	}

	/**
	 * @return bool|mixed
	 */
	public function bundle()
	{
		if (is_null($this->_bundle)) {
			$this->_bundle = false;
			$code = $this->getMnemocode();
			$bundle = \TAO::getOption("infoblock.{$code}.bundle");
			if ($bundle) {
				$this->_bundle = $bundle;
			} else {
				$class = get_class($this);
				if (preg_match('{^(TAO|App)\\\\Bundle\\\\([^\\\\]+)\\\\}', $class, $m)) {
					$name = $m[2];
					$this->_bundle = \TAO::bundle($name);
				}
			}
		}
		return $this->_bundle;
	}

	/**
	 * Ключи массива и их соответствия параметрам CIBlockSection::GetList:
	 * - order - $arOrder (по умолчанию array('left_margin' => 'asc', 'sort' => 'asc'))
	 * - filter - $arFilter
	 * - select - $arSelect
	 * - count - $bIncCnt
	 * - nav - $arNavStartParams
	 * Можно передать так же $args['check_permissions'] - в фильтр добавится
	 * $filter['CHECK_PERMISSIONS'] = $args['check_permissions']
	 * @param array $args
	 * @return \TAO\Section[]
	 */
	public function getSections($args = array())
	{
		$order = isset($args['order']) ? $args['order'] : array('left_margin' => 'asc', 'sort' => 'asc');
		$filter = isset($args['filter']) ? $args['filter'] : array();
		$select = isset($args['select']) ? $args['select'] : array();
		$count = isset($args['count']) ? $args['count'] : false;
		$nav = isset($args['nav']) ? $args['nav'] : false;

		$cp = isset($args['check_permissions']) ? $args['check_permissions'] : false;

		$filter['IBLOCK_ID'] = $this->id();
		if (!isset($filter['CHECK_PERMISSIONS'])) {
			$filter['CHECK_PERMISSIONS'] = $cp;
		}
		$result = \CIBlockSection::GetList($order, $filter, $count, $select, $nav);
		$rows = array();
		while ($row = $result->GetNext()) {
			$rows[$row['ID']] = $this->makeSectionItemByRow($row);
		}
		return $rows;
	}

	/**
	 * @param $id
	 * @return \TAO\Section|null
	 */
	public function getSectionById($id)
	{
		foreach ($this->getSections(array('filter' => array('ID' => $id))) as $section) {
			return $section;
		}
		return null;
	}

	/**
	 * @param $code
	 * @return mixed
	 */
	public function getSectionByCode($code)
	{
		foreach ($this->getSections(array('filter' => array('CODE' => $code))) as $section) {
			return $section;
		}
	}

	/**
	 * @param $param
	 * @param bool|false $by
	 * @return mixed
	 */
	public function getSection($param, $by = false)
	{
		if ($by === 'id') {
			return $this->getSectionById($param);
		}
		if ($by === 'code') {
			return $this->getSectionByCode($param);
		}
		$section = $this->getSectionByCode($param);
		if (!$section) {
			$section = $this->getSectionById($param);
		}
		return $section;
	}

	/**
	 * @param array $args
	 * @return array
	 */
	public function getSectionsTree($args = array())
	{
		$out = array();
		$all = array();
		$order = isset($args['order']) ? $args['order'] : array('depth_level' => 'asc', 'sort' => 'asc', 'id' => 'asc');
		$args['order'] = $order;
		$sections = $this->getSections($args);
		foreach ($sections as $id => $section) {
			$pid = $section['IBLOCK_SECTION_ID'];
			$all[$section->id()] = $section;
			if (!empty($pid) && isset($all[$pid])) {
				$all[$pid]->addChild($section);
			}
		}
		foreach ($all as $section) {
			if ($section->parentId() == 0) {
				$out[$section->id()] = $section;
			}
		}
		return $out;
	}

	/**
	 * @return File
	 */
	public function picture()
	{
		\TAO::load('file');
		return new \TAO\File($this->data['PICTURE']);
	}

	/**
	 * @param array $args
	 * @return int
	 */
	public function getCount($args = array())
	{
		list($order, $filter, $groupBy, $nav, $fields) = $this->convertArgs($args);
		$groupBy = array();
		return (int)\CIBlockElement::GetList($order, $filter, $groupBy, $nav, $fields);
	}

	/**
	 * @param $row
	 */
	protected function generateDetailUrl(&$row)
	{
		if (isset($row['DETAIL_PAGE_URL'])) {
			$row['DETAIL_PAGE_URL'] = \CIBlock::ReplaceDetailUrl($row['DETAIL_PAGE_URL'], $row, true, 'E');
		}
	}

	/**
	 * @param array $args
	 * @return array
	 */
	public function getRows($args = array())
	{
		list($order, $filter, $groupBy, $nav, $fields) = $this->convertArgs($args);
		$out = array();
		$result = \CIBlockElement::GetList($order, $filter, $groupBy, $nav, $fields);
		while ($row = $result->GetNext(true, false)) {
			$out[] = $row;
		}

		if (is_array($result->arResultAdd)) {
			foreach ($result->arResultAdd as $row) {
				$this->generateDetailUrl($row);
				$out[] = $row;
			}
		}
		return $out;
	}

	public function makeItemByRow($row)
	{
		$properties = array();
		$res = \CIBlockElement::GetProperty($row['IBLOCK_ID'], $row['ID']);
		while ($irow = $res->Fetch()) {
			$pid = $irow['ID'];
			$vid = $irow['PROPERTY_VALUE_ID'];
			if (!isset($properties[$pid])) {
				$properties[$pid] = array();
			}
			$properties[$pid][$vid] = $irow;
		}
		$item = $this->makeItem($row, $properties);

		return $item;
	}

	public function makeSectionItemByRow($row)
	{
		$class = $this->sectionClassName();
		return new $class($row);
	}

	/**
	 * @param array $args
	 * @return \TAO\Entity[]
	 */
	public function getItems($args = array())
	{
		$args['fields'] = array();
		$rows = $this->getRows($args);
		$items = array();
		foreach ($rows as $row) {
			$items[] = $this->makeItemByRow($row);
		}
		return $items;
	}

	/**
	 * @param array $args
	 * @return array
	 */
	public function getItemsForSelect($args = array())
	{
		$out = array();
		foreach ($this->getItems($args) as $item) {
			$out[$item->id()] = $item->title();
		}
		return $out;
	}

	/**
	 * @param $id
	 * @param bool|true $checkPermissions
	 * @param bool|false $by
	 * @return \TAO\Entity
	 */
	public function loadItem($id, $checkPermissions = true, $by = false)
	{
		if (empty($id)) {
			return;
		}
		$param = is_string($by) ? $by : (is_numeric($id) ? 'ID' : 'CODE');

		$items = $this->getItems(array(
			'filter' => array($param => $id),
			'check_permissions' => $checkPermissions,
		));

		if (count($items) == 0) {
			return;
		}

		return array_shift($items);
	}

	/**
	 * @return bool
	 */
	public function userCanRead()
	{
		return \CIBlock::GetPermission($this->id()) >= 'R';
	}

	/**
	 * @return bool
	 */
	public function userCanEdit()
	{
		return \CIBlock::GetPermission($this->id()) >= 'U';
	}

	/**
	 * @param $id
	 * @param bool|true $checkPermissions
	 * @return bool
	 */
	public function deleteItem($id, $checkPermissions = true)
	{
		global $DB;
		if ($checkPermissions) {
			$item = $this->loadItem($id);
			if (!$item) {
				return false;
			}
			if (!$this->accessDelete($item)) {
				return false;
			}
		}
		$DB->StartTransaction();

		if (!\CIBlockElement::Delete($id)) {
			$DB->Rollback();
			return false;
		} else {
			$DB->Commit();
		}
		return true;
	}

	/**
	 * @return mixed
	 */
	public function getPermission()
	{
		return \CIBlock::GetPermission($this->getId());
	}

	/**
	 * @param null $item
	 * @return bool
	 */
	public function accessUpdate($item = null)
	{
		return $this->getPermission() >= 'W';
	}

	/**
	 * @param null $item
	 * @return bool
	 */
	public function accessDelete($item = null)
	{
		return $this->accessUpdate($item);
	}

	/**
	 * @return bool
	 */
	public function accessInsert()
	{
		return $this->getPermission() >= 'W';
	}

	/**
	 * @param null $item
	 * @return bool
	 */
	public function accessRead($item = null)
	{
		return $this->getPermission() >= 'R';
	}

	/**
	 * @return string
	 */
	public function description()
	{
		return trim($this->data['DESCRIPTION']);
	}

	/**
	 * @param array $row
	 * @param array $properties
	 * @return \TAO\Entity
	 */
	public function makeItem($row = array(), $properties = array())
	{
		$className = $this->entityClassName();
		$entity = new $className($row, $properties);
		$entity->setInfoblock($this);
		return $entity;
	}

	/**
	 * @param string $name
	 * @param int $parentId
	 * @param array $fields
	 * @return mixed
	 * @throws InfoblockException
	 */
	public function addSection($name, $parentId = 0, $fields = array())
	{
		$fields['NAME'] = $name;
		$fields['IBLOCK_ID'] = $this->getId();

		if ($parentId) {
			$section = $this->getSectionById($parentId);
			if (empty($section)) {
				throw new InfoblockException('There is no section with id = ' . $parentId);
			}
			$fields['IBLOCK_SECTION_ID'] = $parentId;
		}
		$blockSection = new \CIBlockSection;
		$sectionId = $blockSection->add($fields);
		return $sectionId;
	}

	/**
	 * @return bool|null
	 */
	public function getMnemocodeForPaths()
	{
		$code = $this->getMnemocode();
		$pathsCode = \TAO::getOption("infoblock.{$code}.paths_code");
		return empty($pathsCode) ? $code : $pathsCode;
	}

	/**
	 * @param $sub
	 * @return array
	 */
	public function dirs($sub)
	{
		$dirs = array();
		$bundle = $this->bundle();
		$code = $this->getMnemocodeForPaths();
		if ($bundle) {
			$dirs[] = $bundle->localPath("{$sub}/{$code}");
			$dirs[] = $bundle->taoPath("{$sub}/{$code}");
			$dirs[] = $bundle->localPath($sub);
			$dirs[] = $bundle->taoPath($sub);
		}
		$dirs[] = \TAO::localDir("{$sub}/{$code}");
		$dirs[] = \TAO::localDir($sub);
		$dirs[] = \TAO::taoDir($sub);
		return $dirs;
	}

	/**
	 * @param $file
	 * @return mixed
	 */
	public function viewPath($file, $extra = false)
	{

		$dirs = $this->dirs('views');
		$path = \TAO::filePath($dirs, $file, $extra);
		return $path;
	}

	/**
	 * @param $file
	 * @return mixed|string
	 */
	public function styleUrl($file, $extra = false)
	{
		$dirs = $this->dirs('styles');
		$url = \TAO::fileUrl($dirs, $file, $extra);
		return $url;
	}

	/**
	 * @param $file
	 * @return mixed|string
	 */
	public function scriptUrl($file, $extra = false)
	{
		$dirs = $this->dirs('scripts');
		$url = \TAO::fileUrl($dirs, $file, $extra);
		return $url;
	}

	/**
	 * @param array $args
	 * @return string
	 */
	public function render($args = array())
	{
		$code = $this->getMnemocode();
		$itemMode = isset($args['item_mode']) ? $args['item_mode'] : 'teaser';
		$listMode = isset($args['list_mode']) ? $args['list_mode'] : 'default';
		$listClass = isset($args['list_class']) ? $args['list_class'] : "tao-list-{$code}";

		$pagerTop = isset($args['pager_top']) ? $args['pager_top'] : false;
		$pagerBottom = isset($args['pager_bottom']) ? $args['pager_bottom'] : false;

		$count = $this->getCount($args);
		$items = $this->getItems($args);

		if ($count == 0) {
			$path = $this->viewPath("list-empty.phtml", $listMode);
		} else {
			$path = $this->viewPath("list.phtml", $listMode);
		}

		list($order, $filter, $groupBy, $nav, $fields, $other) = $this->convertArgs($args);

		if (isset($other['page']) && isset($other['per_page'])) {
			$pagerVar = $other['pager_var'];
			$page = (int)$other['page'];
			$perPage = (int)$other['per_page'];
			$numPages = ceil($count / $perPage);
			if ($numPages < 2) {
				$pagerTop = false;
				$pagerBottom = false;
			}
		}

		if (isset($args['ajax_url'])) {
			$ajaxUrl = $args['ajax_url'];
		} else {
			$ajaxUrl = '/local/vendor/techart/bitrix.tao/api/elements-ajax.php';
			$urlArgs = $args;
			$urlArgs['infoblock'] = $this->getMnemocode();
			$ajaxUrl = \TAO\Urls::url($ajaxUrl, $urlArgs);
		}

		ob_start();
		include($path);
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * @param array $args
	 * @param null $sections
	 * @return string
	 */
	public function renderSectionsList($args = array(), $sections = null)
	{
		if (is_null($sections)) {
			$sections = $this->getSectionsTree($args);
		}
		if (empty($sections)) {
			return '';
		}
		$level = isset($args['level']) ? (int)$args['level'] : 0;
		if (!isset($args['sections_list_type'])) {
			$args['sections_list_type'] = 'ul';
		}
		$type = $args['sections_list_type'] == 'ul' ? 'ul' : 'ol';
		$code = $this->getMnemocode();
		$out = "\n<{$type} class=\"infoblock-sections-list infoblock-{$code}-sections-list level-{$level}\">";
		$mode = isset($args['section_mode']) ? $args['section_mode'] : 'teaser';
		foreach ($sections as $section) {
			$out .= "\n<li>";
			$out .= $section->render(array('mode' => "section-{$mode}"));
			$subargs = $args;
			$subargs['level'] = $level + 1;
			$out .= $this->renderSectionsList($subargs, $section->sub());
			$out .= "</li>";
		}
		$out .= "\n</{$type}>";
		return $out;
	}

	/**
	 * @param $args
	 * @return string
	 */
	public function renderElementsPage($args)
	{
		if (is_string($args)) {
			$args = array('page_mode' => $args);
		}
		$mode = isset($args['page_mode']) ? $args['page_mode'] : 'elements-page';
		$path = $this->viewPath("{$mode}.phtml");

		if (!isset($args['page_class'])) {
			$code = $this->getMnemocode();
			$args['page_class'] = "infoblock-elements-page infoblock-{$code}-elements-page";
		}
		$APPLICATION = \TAO::app();
		$this->prepareElementsPage($args);

		ob_start();
		include($path);
		$content = ob_get_clean();
		return $content;

	}

	/**
	 * @param $args
	 * @return string
	 */
	public function renderSectionsPage($args)
	{
		if (is_string($args)) {
			$args = array('page_mode' => $args);
		}
		$mode = isset($args['page_mode']) ? $args['page_mode'] : 'sections-page';
		$path = $this->viewPath("{$mode}.phtml");

		if (!isset($args['page_class'])) {
			$code = $this->getMnemocode();
			$args['page_class'] = "infoblock-sections-page infoblock-{$code}-sections-page";
		}
		$APPLICATION = \TAO::app();
		$this->prepareSectionsPage($args);

		ob_start();
		include($path);
		$content = ob_get_clean();
		return $content;

	}

	/**
	 * @param array $args
	 */
	public function prepareElementsPage($args = array())
	{
		\TAO::app()->SetTitle($this->title());
	}

	/**
	 * @param array $args
	 */
	public function prepareSectionsPage($args = array())
	{
		\TAO::app()->SetTitle($this->title());
	}

	/**
	 *
	 */
	protected function genDescriptions()
	{
		$description = $this->description();
		$description = str_replace('{{ELEMENTS}}', '{{DELIMITER}}', $description);
		$description = str_replace('{{SECTIONS}}', '{{DELIMITER}}', $description);
		list($preDescription, $postDescription) = explode('{{DELIMITER}}', $description);

		$this->preDescription = trim($preDescription);
		$this->postDescription = trim($postDescription);
	}

	/**
	 * @return mixed
	 */
	public function preDescription()
	{
		if (is_null($this->preDescription)) {
			$this->genDescriptions();
		}
		return $this->preDescription;
	}

	/**
	 * @return mixed
	 */
	public function postDescription()
	{
		if (is_null($this->postDescription)) {
			$this->genDescriptions();
		}
		return $this->postDescription;
	}

	/**
	 * @return string
	 */
	public function renderPreDescription()
	{
		$v = $this->preDescription();
		if (!empty($v)) {
			return "<div class=\"description-pre\">{$v}</div>";
		}
		return '';
	}

	/**
	 * @return string
	 */
	public function renderPostDescription()
	{
		$v = $this->postDescription();
		if (!empty($v)) {
			return "<div class=\"description-post\">{$v}</div>";
		}
		return '';
	}

	/**
	 * @param $page
	 * @param $numPages
	 * @param string $pagerVar
	 * @param string $type
	 * @return string
	 */
	public function renderPageNavigator($page, $numPages, $pagerVar = 'page', $type = 'common')
	{
		return \TAO::pager($pagerVar)->setType($type)->setUrl($_SERVER['REQUEST_URI'])->render($page, $numPages);
	}

	/**
	 * @param $code
	 * @param $class
	 */
	public static function setEntityClass($code, $class)
	{
		self::$entityClasses[$code] = $class;
	}

	/**
	 * @return string
	 */
	public function entityClassName()
	{
		$code = $this->getMnemocode();
		if (isset(self::$entityClasses[$code])) {
			return self::$entityClasses[$code];
		}
		if (!$this->entityClassName) {
			$path = \TAO::localDir("entity/{$code}.php");
			if (is_file($path)) {
				include_once($path);
				$this->entityClassName = '\\App\\Entity\\' . $code;
			} else {
				if ($bundle = $this->bundle()) {
					if ($className = $bundle->getEntityClassName($code)) {
						$this->entityClassName = $className;
					}
				}
			}
			if (!$this->entityClassName) {
				$this->entityClassName = '\\TAO\\Entity';
			}
		}
		return $this->entityClassName;
	}

	/**
	 * @return string
	 */
	public function sectionClassName()
	{
		$code = $this->getMnemocode();
		if (isset(self::$sectionClasses[$code])) {
			return self::$sectionClasses[$code];
		}
		if (!$this->sectionClassName) {
			$path = \TAO::localDir("section/{$code}.php");
			if (is_file($path)) {
				include_once($path);
				$this->sectionClassName = '\\App\\Section\\' . $code;
			} else {
				if ($bundle = $this->bundle()) {
					if ($className = $bundle->getSectionClassName($code)) {
						$this->sectionClassName = $className;
					}
				}
			}
			if (!$this->sectionClassName) {
				$this->sectionClassName = '\\TAO\\Section';
			}
		}
		return $this->sectionClassName;
	}

	/**
	 * @param $args
	 * @return array
	 */
	protected function convertArgs($args)
	{
		$order = isset($args['order']) ? $args['order'] : array('SORT' => 'ASC', 'NAME' => 'ASC');
		$filter = isset($args['filter']) ? $args['filter'] : array('ACTIVE' => 'Y');
		$groupBy = isset($args['group_by']) ? $args['group_by'] : false;
		$nav = isset($args['nav']) ? $args['nav'] : false;
		$fields = isset($args['fields']) ? $args['fields'] : array();
		$other = array();

		$filter['IBLOCK_ID'] = $this->getId();

		if ((isset($args['page']) || isset($args['pager_var'])) && isset($args['per_page'])) {
			$page = 1;
			$per_page = (int)$args['per_page'];
			$per_page = $per_page > 0 ? $per_page : 1;
			$var = 'page';
			if (isset($args['pager_var'])) {
				$var = $args['pager_var'];
				if (isset($_GET[$var])) {
					$page = (int)$_GET[$var];
				}
			} else {
				$page = (int)$args['page'];
			}
			$page = $page < 1 ? 1 : $page;
			$nav['iNumPage'] = $page;
			$nav['nPageSize'] = $per_page;
			$other['page'] = $page;
			$other['per_page'] = $per_page;
			$other['pager_var'] = $var;
		}

		if (isset($args['limit'])) {
			$limit = (int)$args['limit'];
			if ($limit > 0) {
				$offset = (int)$args['offset'];
				$nav['iNumPage'] = $offset + 1;
				$nav['nPageSize'] = 1;
				$nav['iNavAddRecords'] = $limit - 1;
			}
		}

		if (isset($args['section'])) {
			$filter['SECTION_ID'] = is_object($args['section']) ? $args['section']->id() : $args['section'];
		}

		if (isset($args['include_subsections']) && $args['include_subsections']) {
			$filter['INCLUDE_SUBSECTIONS'] = 'Y';
			$filter['SECTION_ACTIVE'] = 'Y';
		}

		if (isset($args['check_permissions'])) {
			$checkPermissions = $args['check_permissions'];
			if (is_bool($checkPermissions)) {
				$checkPermissions = $checkPermissions ? 'Y' : 'N';
			}
			$filter['CHECK_PERMISSIONS'] = $checkPermissions;
		}

		$properties = $this->properties();

		$_filter = array();
		foreach ($filter as $k => $v) {
			if (isset($properties[$k])) {
				$k = "PROPERTY_{$k}";
			}
			$_filter[$k] = $v;
		}

		$_order = array();
		foreach ($order as $k => $v) {
			if (isset($properties[$k])) {
				$k = "PROPERTY_{$k}";
			}
			$_order[$k] = $v;
		}

		return array($_order, $_filter, $groupBy, $nav, $fields, $other);
	}

	/**
	 * @param $mnemocode
	 * @return $this
	 */
	public function setMnemocode($mnemocode)
	{
		$this->mnemocode = $mnemocode;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getMnemocode()
	{
		return $this->mnemocode;
	}

	/**
	 * @return array|bool
	 */
	public function getData($name = false)
	{
		if (!$name) {
			return $this->data;
		}
		return isset($this->data[$name]) ? $this->data[$name] : null;
	}

	/**
	 * @return array
	 */
	protected function loadData()
	{
		$result = \CIBlock::GetList(array('SORT' => 'ASC'), array('CODE' => $this->getMnemocode(), 'CHECK_PERMISSIONS' => 'N'));
		return $result->Fetch();
	}

	/**
	 * @return bool
	 */
	public function title()
	{
		return $this->getMnemocode();
	}

	/**
	 * @return bool
	 */
	public function isActive()
	{
		return true;
	}

	/**
	 * @return int
	 */
	public function sort()
	{
		return 500;
	}

	/**
	 * @return array
	 */
	public function sites()
	{
		$by = 'sort';
		$order = 'asc';
		$result = \CSite::GetList($by, $order);
		$out = array();
		foreach ($result->arResult as $site) {
			$out[] = $site['LID'];
		}
		return $out;
	}

	/**
	 * @return array
	 */
	public function access()
	{
		return array(2 => 'R');
	}

	/**
	 * @return array
	 */
	protected function data()
	{
		return array();
	}

	/**
	 * @return array
	 */
	protected function generateData()
	{
		$data = array(
			'CODE' => $this->getMnemocode(),
			'IBLOCK_TYPE_ID' => self::$code2type[$this->getMnemocode()],
			'NAME' => $this->title(),
			'ACTIVE' => $this->isActive() ? 'Y' : 'N',
			'SORT' => $this->sort(),
			'SITE_ID' => $this->sites(),
			'GROUP_ID' => $this->access(),
		);
		return array_merge($data, $this->data());
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		if (is_array($this->data) && isset($this->data['ID'])) {
			return $this->data['ID'];
		}
	}

	/**
	 * @return mixed
	 */
	public function id()
	{
		return $this->getId();
	}

	/**
	 * Возвращает URL страницы информационного блока
	 * @return string|null
	 */
	public function listUrl()
	{
		$url = false;

		if (array_key_exists('LIST_PAGE_URL', $this->data)) {
			$url = $this->data['LIST_PAGE_URL'];
		}

		if ($url) {
			$params = array();
			$params["IBLOCK_ID"] = $this->data["ID"];
			$params["IBLOCK_CODE"] = $this->data["CODE"];
			$params["IBLOCK_EXTERNAL_ID"] = $this->data["EXTERNAL_ID"];

			return \CIBlock::ReplaceDetailUrl($url, $params, true, false);
		}

		return null;
	}

	/**
	 *
	 */
	public function addNewInfoblock()
	{
		$data = $this->generateData();
		$o = new \CIBlock;
		$id = $o->Add($data);
		$data['ID'] = $id;
		$this->data = $data;
		$this->process();
	}

	/**
	 *
	 */
	public function update()
	{
		$id = $this->getId();
		if ($id) {
			$o = new \CIBlock;
			$o->Update($id, $this->data);
		}
	}

	/**
	 * @return array
	 */
	public function properties()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public function fields()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public function messages()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public function loadProperties($byCode = false)
	{
		if (!$this->currentProperties && $id = $this->getId()) {
			$args = array('IBLOCK_ID' => $id, 'CHECK_PERMISSIONS' => 'N');
			if (is_string($byCode)) {
				$args['CODE'] = $byCode;
			}
			$result = \CIBlockProperty::GetList(array(), $args);
			while ($row = $result->Fetch()) {
				$code = trim($row['CODE']);
				if ($code == '') {
					$code = 'PROP_' . $row['ID'];
				}
				$this->currentProperties[$code] = $row;
			}
		}
		return $this->currentProperties;
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function propertyData($name)
	{
		$properties = $this->loadProperties();
		return isset($properties[$name]) ? $properties[$name] : array();
	}

	/**
	 * @return array
	 */
	public function propertiesCodes()
	{
		if (!$this->currentPropertiesCodes) {
			foreach ($this->loadProperties() as $name => $data) {
				if (isset($data['ID'])) {
					$this->currentPropertiesCodes[$data['ID']] = $name;
				}
			}
		}
		return $this->currentPropertiesCodes;
	}

	/**
	 * @param $name
	 * @return int
	 */
	public function propertyId($name)
	{
		if ($propertyData = $this->propertyData($name)) {
			if (isset($propertyData['ID'])) {
				return (int)$propertyData['ID'];
			}
		}
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public function propertyCode($id)
	{
		$codes = $this->propertiesCodes();
		if (isset($codes[$id])) {
			return $codes[$id];
		}
	}

	/**
	 * @param $code
	 * @return array
	 */
	public function enumData($code)
	{
		$pid = $this->propertyId($code);
		static $ids = array();
		static $xids = array();
		if (!isset($ids[$pid])) {
			$ids[$pid] = array();
			$xids[$pid] = array();
			$res = \CIBlockPropertyEnum::GetList(array(), array('PROPERTY_ID' => $pid, 'CHECK_PERMISSIONS' => 'N'));
			while ($row = $res->Fetch()) {
				$id = $row['ID'];
				$xid = $row['XML_ID'];
				$ids[$pid][$xid] = $id;
				$xids[$pid][$id] = $xid;
			}
		}
		return array($ids[$pid], $xids[$pid]);
	}

	/**
	 * @param $code
	 * @param $xmlId
	 * @return null
	 */
	public function enumId($code, $xmlId)
	{
		list($ids, $xids) = $this->enumData($code);
		return isset($ids[$xmlId]) ? $ids[$xmlId] : null;
	}

	/**
	 * @param $code
	 * @param $id
	 * @return null
	 */
	public function enumXMLId($code, $id)
	{
		list($ids, $xids) = $this->enumData($code);
		return isset($xids[$id]) ? $xids[$id] : null;
	}

	/**
	 * @return array
	 */
	public function urls()
	{
		return array();
	}

	/**
	 * @return array
	 */
	protected function urlsProps()
	{
		$out = array();
		$sort = 0;
		foreach ($this->urls() as $code => $data) {
			$sort++;
			$label = 'Адрес страницы';
			$label = isset($data['caption']) ? $data['caption'] : $label;
			$label = isset($data['label']) ? $data['label'] : $label;
			$out["url_{$code}"] = array(
				'NAME' => $label,
				'SORT' => trim($sort),
				'PROPERTY_TYPE' => 'S',
				'IS_REQUIRED' => 'N',
				'DEFAULT_VALUE' => '',
				'COL_COUNT' => '50',
			);
		}
		return $out;
	}

	/**
	 *
	 */
	public function process()
	{
		if ($this->processed) {
			return;
		}

		$this->processed = true;

		$doDel = \TAO::getOption("infoblock.schema.delete", true);;
		$code = $this->getMnemocode();
		$d = \TAO::getOption("infoblock.{$code}.schema.delete", "+");
		if (is_bool($d)) {
			$doDel = $d;
		}

		foreach ($this->generateData() as $k => $v) {
			$this->data[$k] = $v;
		}
		$this->update();

		$o = new \CIBlock();
		$mesages = $this->messages();
		$o->SetMessages($this->getId(), $mesages);
		$fields = $this->fields();
		$o->SetFields($this->getId(), $fields);


		$props = $this->loadProperties();
		$newProps = $this->properties();
		foreach ($this->urlsProps() as $key => $data) {
			$newProps[$key] = $data;
		}

		$o = new \CIBlockProperty();

		if ($doDel) {
			foreach ($props as $prop => $data) {
				if (!isset($newProps[$prop])) {
					$o->Delete($data['ID']);
				}
			}
		}

		foreach ($newProps as $prop => $data) {
			$data['CODE'] = $prop;
			if ($data['PROPERTY_TYPE'] == 'E' || $data['PROPERTY_TYPE'] == 'G') {
				if (!isset($data['LINK_IBLOCK_ID'])) {
					if (isset($data['LINK_IBLOCK_CODE'])) {
						$data['LINK_IBLOCK_ID'] = self::codeToId($data['LINK_IBLOCK_CODE']);
					}
					if (isset($data['LINK_TO'])) {
						$data['LINK_IBLOCK_ID'] = \TAO::infoblock($data['LINK_TO'])->getId();
					}
				}
			}
			if (isset($props[$prop])) {
				$id = $props[$prop]['ID'];
				$o->Update($id, $data);
			} else {
				$data['IBLOCK_ID'] = $this->getId();
				$id = $o->Add($data);
			}
			if ($data['PROPERTY_TYPE'] == 'L' && isset($data['ITEMS']) && is_array($data['ITEMS'])) {
				$items = array();
				$newItems = $data['ITEMS'];
				$res = \CIBlockPropertyEnum::GetList(array(), array('PROPERTY_ID' => $id, 'CHECK_PERMISSIONS' => 'N'));
				while ($row = $res->Fetch()) {
					$iid = $row['ID'];
					$eid = $row['EXTERNAL_ID'];
					if (!isset($newItems[$eid])) {
						\CIBlockPropertyEnum::Delete($iid);
					} else {
						$items[$eid] = $row;
					}
				}
				$eo = new \CIBlockPropertyEnum();
				foreach ($newItems as $eid => $edata) {
					if (is_string($edata)) {
						$edata = array('VALUE' => $edata);
					}
					$edata['PROPERTY_ID'] = $id;
					$edata['EXTERNAL_ID'] = $eid;
					$edata['XML_ID'] = $eid;
					if (isset($items[$eid])) {
						$eo->Update($items[$eid]['ID'], $edata);
					} else {
						$eo->Add($edata);
					}
				}
			}
		}
	}

	/**
	 * @param $code
	 * @return null
	 */
	public function getProperty($code)
	{
		$props = $this->loadProperties($code);
		return isset($props[$code]) ? $props[$code] : null;
	}

	/**
	 * @param $data
	 * @param bool|true $update
	 * @return $this|void
	 * @throws InfoblockException
	 */
	public function setProperty($data, $update = true)
	{
		if (!is_array($data)) {
			throw new InfoblockException('Invalid property data');
		}
		if (!isset($data['CODE'])) {
			throw new InfoblockException('CODE not found in property data');
		}
		if (isset($data['LINK_IBLOCK_CODE'])) {
			$data['LINK_IBLOCK_ID'] = self::codeToId($data['LINK_IBLOCK_CODE']);
		}
		if (isset($data['LINK_TO'])) {
			$data['LINK_IBLOCK_ID'] = \TAO::infoblock($data['LINK_TO'])->getId();
		}
		$code = $data['CODE'];
		$old = $this->getProperty($code);
		$exists = is_array($old);
		if ($exists && !$update) {
			return;
		}
		$o = new \CIBlockProperty();
		if ($exists) {
			$id = $old['ID'];
			$o->Update($id, $data);
		} else {
			$data['IBLOCK_ID'] = $this->getId();
			$o->add($data);
		}
		return $this;
	}

	/**
	 * @param $data
	 * @return $this|Infoblock|void
	 * @throws InfoblockException
	 */
	public function setPropertyIfNotExists($data)
	{
		return $this->setProperty($data, false);
	}

	public function setPropertyEnum($code, $data, $update = true)
	{
		$prop = $this->getProperty($code);
		if ($prop) {
			$data['PROPERTY_ID'] = $prop['ID'];
			$res = \CIBlockPropertyEnum::GetList(array(), array('PROPERTY_ID' => $prop['ID'], 'CHECK_PERMISSIONS' => 'N'));
			$found = null;
			while ($row = $res->Fetch()) {
				$iid = $row['ID'];
				$eid = $row['EXTERNAL_ID'];
				$xid = $row['XML_ID'];
				if (isset($data['EXTERNAL_ID']) && $eid == $data['EXTERNAL_ID']) {
					$found = $iid;
					break;
				}
				if (isset($data['XML_ID']) && $xid == $data['XML_ID']) {
					$found = $iid;
					break;
				}
			}
			if ($found && !$update) {
				return;
			}
			$o = new \CIBlockPropertyEnum();
			if ($found) {
				$o->Update($found, $data);
			} else {
				$o->Add($data);
			}
		}
		return $this;
	}

	public function setPropertyEnumIfNotExists($code, $data)
	{
		return $this->setPropertyEnum($code, $data, false);
	}

	/**
	 * @param $code
	 * @return false|string
	 */
	public static function codeToId($code)
	{
		$result = \CIBlock::GetList(array('SORT' => 'ASC'), array('CODE' => $code, 'CHECK_PERMISSIONS' => 'N'));
		$row = $result->Fetch();
		return $row ? $row['ID'] : false;
	}

	/**
	 * @param string $property
	 * @return bool
	 */
	public function propertyExists($property)
	{
		$properties = $this->properties();
		return isset($properties[$property]);
	}

	/**
	 * @param string $mode
	 * @param array $args
	 * @return array
	 */
	public function buildMenu($mode = 'full', $args = array())
	{
		$out = array();
		foreach ($this->getItems($args) as $item) {
			$menuItem = $item->buildMenuItem($mode);
			if ($menuItem) {
				$out[] = $menuItem;
			}
		}
		return $out;
	}

	/**
	 * @param $item
	 * @return string
	 */
	public function sectionUrl($item)
	{
		$url = $item['SECTION_PAGE_URL'];
		if (empty($url)) {
			$code = $this->getMnemocode();
			$id = $item->id();
			return "/{$code}/section-{$id}/";
		}
		return $url;
	}

	/**
	 * @param $item
	 * @return array
	 */
	public function navigationItem($item)
	{
		$code = $this->getMnemocode();
		return array(
			'id' => "{$code}" . $item->id(),
			'flag' => "{$code}" . $item->id(),
			'url' => $item->url(),
			'title' => $item->title(),
		);
	}

	/**
	 * @return array
	 */
	public function navigationTree()
	{
		$out = array();
		foreach ($this->getItems() as $item) {
			if ($navItem = $this->navigationItem($item)) {
				$out[] = $navItem;
			}
		}
		return $out;
	}

	/**
	 * @param $item
	 * @return array
	 */
	public function navigationSectionItem($item)
	{
		$code = $this->getMnemocode();
		$data = array(
			'id' => "{$code}_section_" . $item->id(),
			'flag' => "{$code}_section_" . $item->id(),
			'url' => $item->url(),
			'title' => $item->title(),
		);
		if ($sub = $item->navigationSub()) {
			$data['sub'] = $sub;
		}
		return $data;
	}

	/**
	 * @return array
	 */
	public function navigationTreeSections()
	{
		$out = array();
		$tree = $this->getSectionsTree();
		foreach ($tree as $section) {
			if ($navItem = $section->navigationItem()) {
				$out[] = $navItem;
			}
		}
		return $out;
	}

	/**
	 * @return string
	 */
	public function getEditAreaId()
	{
		$this->uniqCounter++;
		$code = $this->getMnemocode();

		$this->editAreaId = "bx_tao_iblockj_{$code}_{$this->uniqCounter}";
		$buttons = \CIBlock::GetPanelButtons($this->getId(), 0, 0, array("SECTION_BUTTONS" => false, "SESSID" => false));
		$addUrl = $buttons["edit"]["add_element"]["ACTION_URL"];
		$messages = $this->messages();
		$addTitle = isset($messages['ELEMENT_ADD']) ? $messages['ELEMENT_ADD'] : 'Добавить';

		$addPopup = \TAO::app()->getPopupLink(array('URL' => $addUrl, "PARAMS" => array('width' => 780, 'height' => 500)));

		$btn = array(
			'URL' => "javascript:{$addPopup}",
			'TITLE' => $addTitle,
			'ICON' => 'bx-context-toolbar-add-icon',
		);

		\TAO::app()->SetEditArea($this->editAreaId, array($btn));

		return $this->editAreaId;
	}

	/**
	 * @return int
	 */
	public function rebuildElementsUrls()
	{
		$c = 0;
		foreach ($this->getItems() as $item) {
			$c++;
			$item->generateUrls();
		}
		return $c;
	}

	/**
	 *
	 */
	public static function cliRebuildUrls()
	{
		foreach (\TAO::getOptions() as $k => $v) {
			if (preg_match('{^infoblock\.([a-z0-9_]+)\.route_detail}', $k, $m)) {
				$code = $m[1];
				if ($v) {
					print "Rebuild elements for {$code}...";
					$c = \TAO::infoblock($code)->rebuildElementsUrls();
					print "{$c}\n";
				}
			}
		}
	}

	public function sitemapSectionData($section)
	{
		return array(
			'url' => $section->url(),
			'lastmod' => \TAO::timestamp($section['TIMESTAMP_X'])
		);
	}

	public function sitemapElementData($item)
	{
		return array(
			'url' => $item->url(),
			'lastmod' => \TAO::timestamp($item['TIMESTAMP_X'])
		);
	}
}

class InfoblockException extends \TAOException
{
}
