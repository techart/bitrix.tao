<?php

namespace TAO;

/**
 * Class Section
 * @package TAO
 */
class Section implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $data = array();
    /**
     * @var array
     */
    public $rawData = array();
    /**
     * @var null
     */
    protected $sub = null;
    /**
     * @var null
     */
    protected $infoblock = null;

    /**
     * @var
     */
    protected $preDescription;
    /**
     * @var
     */
    protected $postDescription;

    /**
     * Section constructor.
     * @param array $data
     */
    public function __construct($data = array())
    {
        foreach (array_keys($data) as $key) {
            if ($key[0] == '~') {
                $this->rawData[substr($key, 1)] = $data[$key];
                unset($data[$key]);
            }
        }
        $this->data = $data;
    }

    /**
     * @return null
     */
    public function id()
    {
        return isset($this->data['ID']) ? $this->data['ID'] : null;
    }

    /**
     * @param $child
     * @return $this
     */
    public function addChild($child)
    {
        if (is_null($this->sub)) {
            $this->sub = array();
        }
        $id = $child->id();
        $this->sub[$id] = $child;
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function add($data)
    {
        if ($data instanceof Section) {
            $this->addChild($data);
        } elseif (is_array($data)) {
            foreach ($data as $child) {
                $this->addChild($child);
            }
        }
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function title()
    {
        return $this['NAME'];
    }

    /**
     * @return mixed
     */
    public function url()
    {
        return $this->infoblock()->sectionUrl($this);
    }

    /**
     * @return int
     */
    public function parentId()
    {
        return (int)$this['IBLOCK_SECTION_ID'];
    }

    /**
     * @return mixed
     */
    public function parent()
    {
        return $this->infoblock()->getSection($this->parentId());
    }

    /**
     * @return null
     */
    public function sub()
    {
        if (is_null($this->sub)) {
            $this->sub = $this->infoblock()->getSections(array(
                'filter' => array('SECTION_ID' => $this->id()),
            ));
        }
        return $this->sub;
    }

    /**
     * @return mixed
     */
    public function navigationItem()
    {
        return $this->infoblock()->navigationSectionItem($this);
    }

    /**
     * @return array|null
     */
    public function navigationSub()
    {
        $sub = $this->sub();
        if (empty($sub)) {
            return null;
        }
        $out = array();
        foreach ($sub as $section) {
            $out[] = $section->navigationItem();
        }
        return $out;
    }

    /**
     * @return File
     */
    public function picture()
    {
        \TAO::load('file');
        return new \TAO\File($this['PICTURE']);
    }

    /**
     * @return mixed|null
     */
    public function infoblock()
    {
        if (!$this->infoblock) {
            $this->infoblock = \TAO::infoblock($this['IBLOCK_ID']);
        }
        return $this->infoblock;
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
                $path = \TAO::taoDir("views/section-{$mode}.phtml");
                if (!is_file($path)) {
                    $path = \TAO::taoDir('views/section-teaser.phtml');
                }
            }
            $this->views[$mode] = $path;
        }
        return $this->views[$mode];
    }

    /**
     *
     */
    protected function genDescriptions()
    {
        $description = $this['DESCRIPTION'];
        list($preDescription, $postDescription) = explode('{{ELEMENTS}}', $description);

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
     * @param array $args
     * @return string
     */
    public function render($args = array())
    {
        if (is_string($args)) {
            $args = array('mode' => $args);
        }
        $mode = isset($args['mode']) ? $args['mode'] : (isset($args['page_mode']) ? $args['page_mode'] : 'section-page');
        $path = $this->viewPath($mode);
        $args['section'] = $this;

        if (!isset($args['page_class'])) {
            $icode = $this->infoblock()->getMnemocode();
            $id = $this->id();
            $code = trim($this['CODE']);
            $args['page_class'] = "infoblock-section-page infoblock-{$icode}-section-page infoblock-{$icode}-section-{$code}-page infoblock-{$icode}-section-{$id}-page section-{$id}-page section-{$code}-page";
        }

        $APPLICATION = \TAO::app();
        ob_start();
        include($path);
        $content = ob_get_clean();
        return $content;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        $ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues(
            $this->infoblock()->id(),
            $this->id()
        );
        return $ipropValues->getValues();
    }

    /**
     * @param array $args
     * @return mixed
     */
    public function getItems($args = array())
    {
        if (!isset($args['filter'])) {
            $args['filter'] = array('ACTIVE' => 'Y');
        }
        $args['filter']['SECTION_ID'] = $this->id();
        return $this->infoblock()->getItems($args);
    }

    /**
     * @param array $args
     */
    public function preparePage($args = array())
    {
        global $APPLICATION;
        $meta = $this->getMeta($args);
        $APPLICATION->SetTitle($this->title());
        if (isset($meta['SECTION_META_TITLE'])) {
            $APPLICATION->SetPageProperty('title', $meta['SECTION_META_TITLE']);
        }
        if (isset($meta['SECTION_META_DESCRIPTION'])) {
            $APPLICATION->SetPageProperty('description', $meta['SECTION_META_DESCRIPTION']);
        }
        if (isset($meta['SECTION_META_KEYWORDS'])) {
            $APPLICATION->SetPageProperty('keywords', $meta['SECTION_META_KEYWORDS']);
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     * @return null
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return mixed
     */
    public function offsetSet($offset, $value)
    {
        return $this->data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
