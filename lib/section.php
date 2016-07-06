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
     * @var null
     */
    protected $sub = null;
    /**
     * @var null
     */
    protected $infoblock = null;

    /**
     * Section constructor.
     * @param array $data
     */
    public function __construct($data = array())
    {
        foreach (array_keys($data) as $key) {
            if ($key[0] == '~') {
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
