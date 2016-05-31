<?php

namespace TAO;

/**
 * Class Navigation
 * @package TAO
 */
class Navigation
{
    /**
     * @var array
     */
    static $byIds = array();

    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $url = '/';
    /**
     * @var string
     */
    public $title = 'Index';
    /**
     * @var int
     */
    public $level = 0;
    /**
     * @var null
     */
    public $parent = null;
    /**
     * @var array|bool
     */
    public $data = array();

    /**
     * @var
     */
    protected $sub;
    /**
     * @var bool
     */
    protected $filter = false;
    /**
     * @var null
     */
    protected $selected = null;

    /**
     * Navigation constructor.
     * @param bool|false $data
     */
    public function __construct($data = false)
    {
        if (!$data) {
            return $this->initRoot();
        }
        if (is_array($data)) {
            if (!isset($data['id'])) {
                $data['id'] = md5(serialize($data) . rand(111111, 999999));
            }
            if (isset($data['url']) && isset($data['title'])) {
                $this->id = $data['id'];
                $this->url = $data['url'];
                $this->title = $data['title'];
                $this->parent = $data['parent'];
                $this->level = $this->parent->level + 1;
                unset($data['id']);
                unset($data['url']);
                unset($data['title']);
                unset($data['parent']);
                self::$byIds[$this->id] = $this;
                if (isset($data['sub'])) {
                    $sub = $data['sub'];
                    if (\TAO::isIterable($sub)) {
                        $this->addArray($sub);
                    }
                    unset($data['sub']);
                }
                $this->data = $data;
                return;
            }
        }
        print 'Invalid navigation node<hr>';
        var_dump($data);
        die();
    }

    /**
     *
     */
    protected function initRoot()
    {
        $path = \TAO::localDir('.navigation.php');
        $struct = include($path);
        $this->sub = new \ArrayObject();
        if (\TAO::isIterable($struct)) {
            $this->addArray($struct);
        }
    }

    /**
     * @param $data
     */
    public function add($data)
    {
        if (is_null($this->sub)) {
            $this->sub = new \ArrayObject();
        }
        $data['parent'] = $this;
        $node = new self($data);
        $this->sub[$node->id] = $node;
    }

    /**
     * @param $struct
     */
    protected function addArray($struct)
    {
        $count = 0;
        foreach ($struct as $k => $data) {
            $count++;
            if (is_string($k) && is_string($data)) {
                $data = array(
                    'url' => $k,
                    'title' => $data,
                );
                $k = $count;
            }
            if (!isset($data['url']) || !isset($data['title'])) {
                continue;
            }
            if (is_string($k) && !isset($data['id'])) {
                $data['id'] = $k;
            }
            $this->add($data);
        }
    }

    /**
     * @return array
     */
    public function links()
    {
        if (empty($this->sub)) {
            return array();
        }
        return $this->sub;
    }

    /**
     * @return int
     */
    public function count()
    {
        if (empty($this->sub)) {
            return 0;
        }
        return count($this->sub);
    }

    /**
     * @return bool|null
     */
    public function isSelected()
    {
        if (!is_null($this->selected)) {
            return $this->selected;
        }
        if (\TAO\Urls::isCurrent($this->url)) {
            return $this->selected = true;
        }
        foreach ($this->links() as $link) {
            if ($link->isSelected()) {
                return $this->selected = true;
            }
        }
        return $this->selected = false;
    }

    /**
     * @return bool
     */
    public function selectedNode()
    {
        foreach ($this->links() as $link) {
            if ($link->isSelected()) {
                return $link;
            }
        }
        return false;
    }

    /**
     * @return $this
     */
    public function topNode()
    {
        foreach ($this->links() as $link) {
            if ($link->isSelected()) {
                return $link->topNode();
            }
        }
        return $this;
    }

    /**
     * @param $n
     * @return $this|bool
     */
    public function level($n)
    {
        if ($n < 1) {
            return $this;
        }
        $node = $this->selectedNode();
        if ($node) {
            if ($n > 1) {
                return $node->level($n - 1);
            }
            return $node->count() == 0 ? false : $node;
        }
        return false;
    }

    /**
     * @param $file
     * @return bool|string
     */
    public function viewPath($file)
    {
        return \TAO::filePath(array(\TAO::localDir("views/navigation"), \TAO::taoDir("views/navigation")), $file);
    }

    /**
     * @param $style
     */
    protected function useStyle($style)
    {
        return \TAO::useStyle($style);
    }

    /**
     * @param $script
     */
    protected function useScript($script)
    {
        return \TAO::useStyle($script);
    }

    /**
     * @param string $tpl
     * @param array $args
     * @return string
     */
    public function render($tpl = 'simple', $args = array())
    {
        $path = $this->viewPath("{$tpl}.phtml");
        ob_start();
        include($path);
        $content = ob_get_clean();
        return $content;
    }

    /**
     *
     */
    public function renderLink()
    {
        $class = array();
        if ($this->isSelected()) {
            $class['selected'] = 'selected';
        }
        $class = empty($class) ? '' : ' class="' . implode(' ', $class) . '"';
        print "<a href=\"{$this->url}\"{$class}>{$this->title}</a>";
    }
}