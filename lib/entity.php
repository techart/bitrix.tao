<?php

namespace TAO;

/**
 * Class Entity
 * @package TAO
 */
class Entity implements \ArrayAccess
{
    /**
     * @var array
     */
    public $fieldsData = array();
    /**
     * @var array
     */
    public $propertiesData = array();

    /**
     * @var
     */
    protected $infoblock;

    /**
     * @var array
     */
    protected $containers = array();

    /**
     * @var int
     */
    protected $uniqCounter = 0;
    /**
     * @var
     */
    protected $editAreaId;

    /**
     * @var array
     */
    protected $views = array();

    /**
     * Entity constructor.
     * @param array $fields
     * @param array $properties
     */
    public function __construct($fields = array(), $properties = array())
    {
        $this->fieldsData = $fields;
        $this->propertiesData = $properties;
    }

    /**
     * @param $infoblock
     */
    public function setInfoblock($infoblock)
    {
        $this->infoblock = $infoblock;
        $this->fieldsData['IBLOCK_ID'] = $infoblock->id();
    }

    /**
     * @return int
     */
    public function id()
    {
        return isset($this->fieldsData['ID']) ? $this->fieldsData['ID'] : null;
    }

    /**
     * @return \TAO\Infoblock
     */
    public function infoblock()
    {
        return $this->infoblock;
    }

    /**
     * @return string
     */
    public function title()
    {
        return isset($this->fieldsData['NAME']) ? $this->fieldsData['NAME'] : '';
    }

    /**
     * @return array
     */
    public function generateFieldsData()
    {
        $fieldsData = $this->fieldsData;
        $fieldsData['PROPERTY_VALUES'] = $this->getHumanPropertiesData();
        $fieldsData['FROM_TAO_API'] = true;
        return $fieldsData;
    }

    /**
     * @param array $options
     * @return bool
     */
    public function save($options = array())
    {
        $isInsert = empty($this->id());
        $r = $this->beforeSave();
        if ($r === false) {
            return false;
        }
        if ($isInsert) {
            $r = $this->beforeInsert();
            if ($r === false) {
                return false;
            }
        } else {
            $r = $this->beforeUpdate();
            if ($r === false) {
                return false;
            }
        }

        $wf = isset($options['work_flow']) ? $options['work_flow'] : false;
        $us = isset($options['update_search']) ? $options['update_search'] : true;
        $rp = isset($options['resize_pictures']) ? $options['resize_pictures'] : false;

        $fieldsData = $this->generateFieldsData();

        $o = new \CIBlockElement();

        if ($isInsert) {
            $id = (int)$o->Add($fieldsData, $wf, $us, $rp);
            $this->error = $o->LAST_ERROR;
            if ($id > 0) {
                $this['ID'] = $id;
                $this->afterSave();
                $this->afterInsert();
            }
        } else {
            $o->Update($this->id(), $fieldsData, $wf, $us, $rp);
            $this->error = $o->LAST_ERROR;
            $this->afterSave();
            $this->afterUpdate();
        }
        $this->generateUrls();
    }

    /**
     * @param $name
     * @param $value
     */
    public function saveProperty($name, $value)
    {
        if (empty($this->id())) {
            return;
        }
        $o = new \CIBlockElement;
        $o->SetPropertyValues($this->id(), $this->infoblock()->id(), $value, $name);
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        $ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues(
            $this->infoblock()->id(),
            $this->id()
        );
        return $ipropValues->getValues();
    }

    /**
     * @param string $mode
     */
    public function preparePage($mode = 'full')
    {
        global $APPLICATION;
        $meta = $this->getMeta();
        $APPLICATION->SetTitle($this->title());
        if (isset($meta['ELEMENT_META_TITLE'])) {
            $APPLICATION->SetPageProperty('title', $meta['ELEMENT_META_TITLE']);
        }
        if (isset($meta['ELEMENT_META_DESCRIPTION'])) {
            $APPLICATION->SetPageProperty('description', $meta['ELEMENT_META_DESCRIPTION']);
        }
        if (isset($meta['ELEMENT_META_KEYWORDS'])) {
            $APPLICATION->SetPageProperty('keywords', $meta['ELEMENT_META_KEYWORDS']);
        }
    }

    /**
     * @param string $mode
     * @return string
     */
    public function url($mode = 'full')
    {
        $id = $this->id();
        $code = $this->infoblock()->getMnemocode();
        $urls = $this->infoblock()->urls();
        if (isset($urls[$mode])) {
            $url = trim($this["url_{$mode}"]->value());
            if ($url != '') {
                return $url;
            }
            $data = $urls[$mode];
            if (isset($data['default'])) {
                $url = $data['default'];
                $url = str_replace('{id}', $id, $url);
                return $url;
            }
        }

        if (isset($this->fieldsData['DETAIL_PAGE_URL'])) {
            $url = trim($this->fieldsData['DETAIL_PAGE_URL']);
            if (!empty($url)) {
                return $url;
            }
        }

        return "/local/vendor/techart/bitrix.tao/api/item.php?infoblock={$code}&id={$id}&mode={$mode}";
    }

    /**
     * @param string $mode
     * @return mixed
     */
    public function viewPath($mode = 'teaser')
    {
        if (!isset($this->views[$mode])) {
            $path = $this->infoblock()->viewPath("{$mode}.phtml");
            if (!$path) {
                $path = \TAO::taoDir("views/item/{$mode}.phtml");
                if (!is_file($path)) {
                    $path = \TAO::taoDir('views/default.phtml');
                }
            }
            $this->views[$mode] = $path;
        }
        return $this->views[$mode];
    }

    /**
     * @param array $args
     * @return string
     */
    public function render($args = array())
    {
        if (is_string($args)) {
            $args = array('mode' => $args);
        }
        $mode = isset($args['mode']) ? $args['mode'] : 'teaser';
        $path = $this->viewPath($mode);

        $APPLICATION = \TAO::app();
        ob_start();
        include($path);
        $content = ob_get_clean();
        return $content;
    }

    /**
     * @return array
     */
    public function getHumanPropertiesData()
    {
        $out = array();
        $props = $this->infoblock()->loadProperties();
        if (empty($this->id())) {
            foreach ($props as $data) {
                $code = $data['CODE'];
                $out[$code] = $this->property($code)->value(true);
            }
        } else {
            foreach ($props as $data) {
                $id = $data['ID'];
                $code = $data['CODE'];
                $mul = $data['MULTIPLE'] == 'Y';
                if (isset($this->propertiesData[$id])) {
                    $vdata = $this->propertiesData[$id];
                    if ($mul) {
                        $value = array();
                        foreach ($vdata as $k => $m) {
                            $value[$k] = $m['VALUE'];
                        }
                    } else {
                        $m = current($vdata);
                        $value = $m['VALUE'];
                    }
                } else {
                    $value = $mul ? array() : null;
                }
                $out[$code] = $value;
            }
        }
        return $out;
    }

    /**
     * @param $data
     * @return PropertyContainer
     */
    protected function containerFor($data)
    {
        return new PropertyContainer($data, $this);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function property($name)
    {
        if (isset($this->containers[$name])) {
            return $this->containers[$name];
        }
        $iblock = $this->infoblock();
        if ($data = $iblock->propertyData($name)) {
            $this->containers[$name] = $this->containerFor($data);
            return $this->containers[$name];
        }
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        switch ($name) {
            case 'id':
                return $this->id();
                break;
            case 'infoblock':
                return $this->infoblock;
                break;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if ($container = $this->property($offset)) {
            return true;
        }

        return isset($this->fieldsData[$offset]);
    }

    /**
     * @param mixed $offset
     * @return string
     */
    public function offsetGet($offset)
    {
        if ($container = $this->property($offset)) {
            return $container;
        }
        return $this->fieldsData[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if ($container = $this->property($offset)) {
            $container->set($value);
        }
        $this->fieldsData[$offset] = $value;
    }

    /**
     * @return File
     */
    public function previewPicture()
    {
        \TAO::load('file');
        return new \TAO\File($this['PREVIEW_PICTURE']);
    }

    /**
     * @return File
     */
    public function detailPicture()
    {
        \TAO::load('file');
        return new \TAO\File($this['DETAIL_PICTURE']);
    }

    /**
     *
     */
    public function generateUrls()
    {
        global $DB;

        $id = $this->id();
        if (empty($id)) {
            return;
        }
        $DB->Query("DELETE FROM tao_urls WHERE item_id='{$id}'");
        $site = '';
        $sites = $this->infoblock()->sites();
        if (count($sites) == 1) {
            $site = array_pop($sites);
        }
        $icode = $this->infoblock()->getMnemocode();
        $time = time();

        foreach ($this->infoblock()->urls() as $mode => $data) {
            $url = trim($this["url_{$mode}"]->value());
            if (empty($url)) {
                if (isset($data['generate'])) {
                    $generate = $data['generate'];
                    if (is_callable($generate)) {
                        $url = call_user_func($generate, $this, $mode);
                    } else {
                        $url = str_replace('{id}', $this->id(), $generate);
                        $url = str_replace('{title}', strtolower(\TAO::translit($this->title())), $url);
                    }
                }
                if (!empty($url)) {
                    $this->saveProperty("url_{$mode}", $url);
                }
            }
            if (!empty($url)) {
                $DB->Query("INSERT INTO tao_urls SET url='{$url}', infoblock='{$icode}', item_id={$id}, mode='{$mode}', site='{$site}', time_update='{$time}'");
            }
        }
    }

    /**
     * @return string
     */
    public function getEditAreaId()
    {
        $this->uniqCounter++;
        $code = $this->infoblock()->getMnemocode();
        $id = $this->id();
        $this->editAreaId = "bx_tao_entity_{$code}_{$id}_{$this->uniqCounter}";
        if (!empty($id)) {
            $buttons = \CIBlock::GetPanelButtons($this->infoblock()->getId(), $id, 0, array("SECTION_BUTTONS" => false, "SESSID" => false));
            $editUrl = $buttons["edit"]["edit_element"]["ACTION_URL"];
            $deleteUrl = $buttons["edit"]["delete_element"]["ACTION_URL"] . '&' . bitrix_sessid_get();
            $messages = $this->infoblock()->messages();
            $editTitle = isset($messages['ELEMENT_EDIT']) ? $messages['ELEMENT_EDIT'] : 'Редактировать';
            $deleteTitle = isset($messages['ELEMENT_DELETE']) ? $messages['ELEMENT_DELETE'] : 'Удалить';

            $editPopup = \TAO::app()->getPopupLink(array('URL' => $editUrl, "PARAMS" => array('width' => 780, 'height' => 500)));

            $btn = array(
                'URL' => "javascript:{$editPopup}",
                'TITLE' => $editTitle,
                'ICON' => 'bx-context-toolbar-edit-icon',
            );

            \TAO::app()->SetEditArea($this->editAreaId, array($btn));

            $btn = array(
                'URL' => 'javascript:if(confirm(\'' . \CUtil::JSEscape("{$deleteTitle}?") . '\')) jsUtils.Redirect([], \'' . \CUtil::JSEscape($deleteUrl) . '\');',
                'TITLE' => $deleteTitle,
                'ICON' => 'bx-context-toolbar-delete-icon',
            );

            \TAO::app()->SetEditArea($this->editAreaId, array($btn));

        }
        return $this->editAreaId;
    }

    /**
     * @return bool
     */
    public function codeFromTitle()
    {
        return false;
    }

    /**
     * @return string
     */
    public function generateCode()
    {
        if ($this->codeFromTitle()) {
            return strtolower(\TAO::translit($this->title()));
        }
        return '';
    }

    /**
     *
     */
    public function beforeSaveInner()
    {
        $code = trim($this['CODE']);
        if ($code == '') {
            $this['CODE'] = $this->generateCode();
        }
    }

    /**
     * @param string $mode
     * @return array
     */
    public function buildMenuItem($mode = 'full')
    {
        return array(
            $this->title(),
            $this->url($mode),
            array(),
            array(),
        );
    }

    /**
     * @param bool|false $f
     * @return bool|int|string
     */
    public function date($f = false)
    {
        return $this->dateFrom($f);
    }

    /**
     * @param bool|false $f
     * @return bool|int|string
     */
    public function dateFrom($f = false)
    {
        return \TAO::date($this['DATE_ACTIVE_FROM'], $f);
    }

    /**
     * @param bool|false $f
     * @return bool|int|string
     */
    public function dateTo($f = false)
    {
        return \TAO::date($this['DATE_ACTIVE_TO'], $f);
    }

    /**
     *
     */
    public function dump()
    {
        include(\TAO::taoDir() . '/views/entity-dump.phtml');
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
    }

    /**
     *
     */
    public function beforeSave()
    {
    }

    /**
     *
     */
    public function beforeInsert()
    {
    }

    /**
     *
     */
    public function beforeUpdate()
    {
    }

    /**
     *
     */
    public function afterSave()
    {
    }

    /**
     *
     */
    public function afterInsert()
    {
    }

    /**
     *
     */
    public function afterUpdate()
    {
    }

    /**
     *
     */
    public function beforeDelete()
    {
    }

    /**
     *
     */
    public function afterDelete()
    {
    }
}


class PropertyContainer
{
    /**
     * @var array
     */
    static $enumIds = array();
    /**
     * @var array
     */
    static $enumXMLIds = array();

    /**
     * @var
     */
    protected $data;
    /**
     * @var
     */
    protected $item;

    /**
     * @var
     */
    protected $valueForAdd;

    /**
     * PropertyContainer constructor.
     * @param $data
     * @param $item
     */
    public function __construct($data, $item)
    {
        $this->data = $data;
        $this->item = $item;
    }

    /**
     * @return int
     */
    public function id()
    {
        return (int)$this->data['ID'];
    }

    /**
     * @return bool
     */
    public function multiple()
    {
        return $this->data['MULTIPLE'] == 'Y';
    }

    /**
     * @return bool
     */
    public function isList()
    {
        return $this->data['PROPERTY_TYPE'] == 'L';
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->data['CODE'];
    }

    /**
     *
     */
    protected function checkEnumIds()
    {
        $pid = $this->id();
        if (!isset(self::$enumIds[$pid])) {
            self::$enumIds[$pid] = array();
            self::$enumXMLIds[$pid] = array();
            $res = \CIBlockPropertyEnum::GetList(array(), array('PROPERTY_ID' => $pid, 'CHECK_PERMISSIONS' => 'N'));
            while ($row = $res->Fetch()) {
                $id = $row['ID'];
                $xmlId = $row['XML_ID'];
                self::$enumIds[$pid][$xmlId] = $id;
                self::$enumXMLIds[$pid][$id] = $xmlId;
            }
        }
    }

    /**
     * @param $v
     * @return mixed
     */
    public function enumIdToXMLId($v)
    {
        $this->checkEnumIds();
        $pid = $this->id();
        if (isset(self::$enumXMLIds[$pid][$v])) {
            return self::$enumXMLIds[$pid][$v];
        }
        return $v;
    }

    /**
     * @param $v
     * @return mixed
     */
    public function enumXMLIdToId($v)
    {
        $this->checkEnumIds();
        $pid = $this->id();
        if (isset(self::$enumIds[$pid][$v])) {
            return self::$enumIds[$pid][$v];
        }
        return $v;
    }

    /**
     * @param null $data
     */
    protected function valueData($data = null)
    {
        $id = $this->id();
        if (is_null($data)) {
            if (!isset($this->item->propertiesData[$id])) {
                return;
            }
            return $this->item->propertiesData[$id];
        }
        $this->item->propertiesData[$id] = $data;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function singleValue($value, $inner = false)
    {
        if ($inner) {
            return $value;
        }
        if ($this->isList()) {
            return $this->enumIdToXMLId($value);
        }
        return $value;
    }

    /**
     * @return array
     */
    public function value($inner = false)
    {
        if ($vdata = $this->valueData()) {
            if ($this->multiple()) {
                $out = array();
                foreach ($vdata as $k => $value) {
                    if (!empty($k)) {
                        $out[$k] = $this->singleValue($value['VALUE'], $inner);
                    }
                }
                return $out;
            }
            $m = current($vdata);
            return $this->singleValue($m['VALUE'], $inner);
        } else {
            if (is_null($this->valueForAdd)) {
                if ($this->multiple()) {
                    $this->valueForAdd = array();
                } else {
                    if (isset($this->data['DEFAULT_VALUE'])) {
                        $this->valueForAdd = $this->data['DEFAULT_VALUE'];
                    }
                }
            }
            return $this->valueForAdd;
        }
    }

    /**
     * @param $data
     * @param bool|false $inner
     * @return mixed
     */
    public function valueFromData($data, $inner = false)
    {
        return $this->singleValue($data['VALUE'], $inner);
    }

    /**
     * @param $vaue
     * @return mixed
     */
    public function checkValue($value)
    {
        if ($this->isList()) {
            return $this->enumXMLIdToId($value);
        }
        return $value;
    }

    /**
     * @param $value
     */
    public function set($value)
    {
        if ($vdata = $this->valueData()) {
            if ($this->multiple()) {
                if (is_array($value)) {
                } else {
                }
            } else {
                foreach (array_keys($vdata) as $k) {
                    $vdata[$k]['VALUE'] = $this->checkValue($value);
                }
            }
        } else {
            if ($this->multiple()) {
                if (is_array($value)) {
                    $this->valueForAdd = array();
                    foreach ($value as $v) {
                        $this->valueForAdd[] = $this->checkValue($v);
                    }
                } else {
                    if (!is_array($this->valueForAdd)) {
                        $this->valueForAdd = array();
                    }
                    $this->valueForAdd[] = $this->checkValue($value);
                }
            } else {
                $this->valueForAdd = $this->checkValue($value);
            }
        }
        $this->valueData($vdata);
    }

    /**
     * @return mixed
     */
    public function type()
    {
        $type = $this->data['PROPERTY_TYPE'];
        if (isset($this->data['USER_TYPE'])) {
            $type = $this->data['USER_TYPE'];
        }
        return $type;
    }

    /**
     * @param $args
     * @param bool|false $name
     * @return bool|string
     */
    public function templateForArgs($args, $name = false)
    {
        $tpl = isset($args['template']) ? $args['template'] : false;
        if (!$tpl || !is_file($tpl)) {
            $tpl = isset($args['mode']) ? $args['mode'] : false;
        }
        if (!$tpl || !is_file($tpl)) {
            if ($name) {
                $tpl = \TAO::localDir("views/{$name}.phtml");
            }
        }
        if (!is_file($tpl)) {
            if ($name) {
                $tpl = \TAO::taoDir("views/{$name}.phtml");
            }
        }
        return $tpl;
    }

    /**
     * @param $data
     * @param array $args
     * @return string
     */
    public function renderOne_F($data, $args = array())
    {
        $file = new \TAO\File($data['VALUE']);
        if (!$file->id) {
            return '';
        }
        $description = trim($data['DESCRIPTION']);
        $path = $file->path();
        $url = $file->url();
        $tname = $file->isImage() ? 'imagevalue' : 'filevalue';
        $tpl = $this->templateForArgs($args, $tname);
        $content = '';
        if (is_file($tpl)) {
            $icode = $this->item->infoblock->getMnemocode();
            $code = $data['CODE'];
            $classes = array($tname, "{$tname}-{$icode}", "{$tname}-{$icode}-{$code}");
            $preview = isset($args['preview']) ? $args['preview'] : 'crop100x100';
            ob_start();
            include($tpl);
            $content = ob_get_clean();
        }
        return $content;
    }

    /**
     * @param $data
     * @param array $args
     * @return mixed|string
     */
    public function renderOne_HTML($data, $args = array())
    {
        $tpl = $this->templateForArgs($args, 'htmlvalue');
        $type = $data['VALUE']['TYPE'];
        $text = $data['VALUE']['TEXT'];
        $description = trim($data['DESCRIPTION']);

        if ($type == 'text') {
            $text = str_replace("\n", '<br>', $text);
        }
        if (is_file($tpl)) {
            $icode = $this->item->infoblock->getMnemocode();
            $code = $data['CODE'];
            $classes = array('htmlvalue', "htmlvalue-{$icode}", "htmlvalue-{$icode}-{$code}");
            ob_start();
            include($tpl);
            $text = ob_get_clean();
        }
        return $text;
    }

    /**
     * @param $data
     * @param array $args
     * @return string
     */
    public function renderOne_E($data, $args = array())
    {
        $ib = \TAO::getInfoblock($this->data['LINK_IBLOCK_ID']);
        if ($ib) {
            $value = $this->valueFromData($data);
            $item = $ib->loadItem($value);
            if ($item) {
                $mode = isset($args['mode']) ? $args['mode'] : 'teaser';
                return $item->render($mode);
            }
        }
        return '';
    }

    /**
     * @param $data
     * @param array $args
     * @return mixed
     */
    public function renderOne($data, $args = array())
    {
        $type = $this->type();
        $method = "renderOne_{$type}";
        if (method_exists($this, $method)) {
            return $this->$method($data, $args);
        }
        return $this->valueFromData($data);
    }

    /**
     * @param array $args
     * @return array
     */
    public function renderArray($args = array())
    {
        if (is_string($args)) {
            $args = array('mode' => $args);
        }
        $out = array();
        foreach ($this->valueData() as $v) {
            $out[] = $this->renderOne($v, $args);
        }
        return $out;
    }

    /**
     * @param array $args
     * @return string
     */
    public function render($args = array())
    {
        if (is_string($args)) {
            $args = array('mode' => $args);
        }
        return implode('', $this->renderArray($args));
    }

    /**
     * @return $this
     */
    public function item()
    {
        if ($this->type() == 'E' && !$this->multiple()) {
            $ib = \TAO::getInfoblock($this->data['LINK_IBLOCK_ID']);
            if ($ib) {
                return $ib->loadItem($this->value());
            }

        }
        return $this;
    }

    /**
     * @return array
     */
    public function getItemsForSelect()
    {
        if ($this->multiple() && isset($this->data['LINK_IBLOCK_ID'])) {
            $ib = \TAO::getInfoblock($this->data['LINK_IBLOCK_ID']);
            if ($ib) {
                $out = array();
                foreach ($this->valueData() as $data) {
                    $value = $this->valueFromData($data);
                    $item = $ib->loadItem($value);
                    if ($item) {
                        $out[$item->id()] = $item->title();
                    }
                }
                return $out;
            }
        }
        return array();
    }

    /**
     * @return int
     */
    public function count()
    {
        if ($this->multiple()) {
            return count($this->value());
        }
        return 1;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->render();
    }
}