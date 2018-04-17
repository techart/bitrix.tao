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
	 * @var array
	 */
	static $flags = array();

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
	 * @var array
	 */
	protected $filter = array();
	/**
	 * @var null
	 */
	protected $flag = null;
	/**
	 * @var null
	 */
	protected $selected = null;
	/**
	 * @var null
	 */
	protected $match = null;

	/**
	 * @var string
	 */
	protected $defaultTemplate = 'simple';

	/**
	 * @var null
	 */
	protected $route = null;

	/**
	 * @var string
	 */
	protected $delimiter = '::';

	/**
	 * @var bool
	 */
	protected $isRoute = false;

	/**
	 * Navigation constructor.
	 * @param bool|false $data
	 */
	public function __construct($data = false)
	{
		static $counter = 0;
		$counter++;

		if ($data === 'route') {
			$this->defaultTemplate = 'route';
			$this->isRoute = true;
			return;
		}

		if (!$data) {
			$data = 'navigation';
		}

		if (is_string($data)) {
			return $this->initRoot($data);
		}

		if (is_array($data)) {
			if (!isset($data['id'])) {
				$data['id'] = 'default' . $counter;
			}
			if (isset($data['url']) && isset($data['title'])) {
				$this->id = $data['id'];
				$this->url = $data['url'];
				$this->title = $data['title'];
				$this->parent = $data['parent'];
				$this->level = $this->parent->level + 1;

				if (isset($data['flag'])) {
					$this->flag = $data['flag'];
					unset($data['flag']);
				}

				if (isset($data['match'])) {
					$this->match = $data['match'];
					unset($data['match']);
				}

				if (isset($data['selected'])) {
					$this->selected = $data['selected'];
					unset($data['selected']);
				}

				unset($data['id']);
				unset($data['url']);
				unset($data['title']);
				unset($data['parent']);
				self::$byIds[$this->id] = $this;
				if (isset($data['sub'])) {
					$sub = $data['sub'];
					if (\TAO::isIterable($sub)) {
						$this->addArray($sub);
					} elseif (is_string($sub)) {
						if (preg_match('{^(infoblock|bundle|sections):(.+)$}', $sub, $m)) {
							$object = $m[1];
							$code = trim($m[2]);
							$method = 'navigationTree';
							if (preg_match('{^(.+):(.+)$}', $code, $m)) {
								$code = trim($m[1]);
								$method = trim($m[2]);
							}
							if ($object == 'sections') {
								$object = 'infoblock';
								$method = 'navigationTreeSections';
							}
							if ($object == 'infoblock') {
								$this->addArray(\TAO::infoblock($code)->$method($this, $data));
							} elseif ($object == 'bundle') {
								$this->addArray(\TAO::bundle($code)->$method($this, $data));
							}
						}
					} elseif (is_callable($sub)) {
						$this->addArray(call_user_func($sub, $this, $data));
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
	 * @return null|Navigation
	 */
	public function route()
	{
		if (empty($this->route)) {
			$this->route = new self('route');
			$node = $this->selectedNode();
			while ($node) {
				$this->route->add($node);
				$node = $node->selectedNode();
			}
		}
		return $this->route;
	}

	/**
	 * @param $name
	 * @return $this
	 */
	public function flag($name)
	{
		self::$flags[$name] = true;
		return $this;
	}

	/**
	 * @param $value
	 * @return $this
	 */
	public function delimiter($value)
	{
		$this->delimiter = $value;
		return $this;
	}

	/**
	 * @param $name
	 * @return $this
	 */
	public function unsetFlag($name)
	{
		unset(self::$flags[$name]);
		return $this;
	}

	/**
	 * @param $flag
	 * @return bool
	 */
	public function isFlag($flag)
	{
		return isset(self::$flags[$flag]) && self::$flags[$flag];
	}

	/**
	 *
	 */
	protected function initRoot($name = 'navigation')
	{
		$path = \TAO::localDir(".{$name}.php");
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
		if ($data instanceof \TAO\Navigation) {
			$node = $data;
		} else {
			$data['parent'] = $this;
			$node = new self($data);
		}
		$this->sub[$node->id] = $node;
		return $this;
	}

	/**
	 * @param $struct
	 */
	public function addArray($struct)
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
		$links = array();
		foreach ($this->sub as $link) {
			$valid = true;
			foreach ($this->filter as $p) {
				if (!$link->checkFilter($p)) {
					$valid = false;
					break;
				}
			}
			if ($valid) {
				$links[] = $link;
			}
		}
		return $links;
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
	 * @return $array
	 */
	public function getFilter()
	{
		return $this->filter;
	}

	/**
	 * @return $this
	 */
	public function filter()
	{
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		}
		$this->filter = $args;
		return $this;
	}

	/**
	 * @param $p
	 * @return bool
	 */
	public function checkFilter($p)
	{
		if (is_string($p) && $p != '') {
			if ($p[0] == '!') {
				$p = substr($p, 1);
				return !isset($this->data[$p]) || $this->data[$p] === false;
			}
			return isset($this->data[$p]) && $this->data[$p] !== false;
		}
		return true;
	}

	/**
	 * @return bool|null
	 */
	public function isSelected()
	{
		if (!is_null($this->selected)) {
			if (is_callable($this->selected)) {
				return call_user_func($this->selected, $this);
			}
			return $this->selected;
		}
		if (!empty($this->flag)) {
			if (\TAO::isIterable($this->flag)) {
				foreach ($this->flag as $flag) {
					if ($this->isFlag($flag)) {
						return $this->selected = true;
					}
				}
			} elseif (is_string($this->flag) && $this->isFlag($this->flag)) {
				return $this->selected = true;
			}
		}
		if (\TAO\Urls::isCurrent($this->url)) {
			return $this->selected = true;
		}
		if (!empty($this->match) && is_string($this->match)) {
			if ($this->match == '*') {
				if (\TAO\Urls::isCurrentStartsWith($this->url)) {
					return $this->selected = true;
				}
			} elseif (mb_substr($this->match, mb_strlen($this->match) - 1) == '*') {
				$m = mb_substr($this->match, 0, mb_strlen($this->match) - 1);
				if (\TAO\Urls::isCurrentStartsWith($m)) {
					return $this->selected = true;
				}
			}
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
	public function isCurrent()
	{
		return \TAO\Urls::isCurrent($this->url);
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
	public function render($tpl = false, $args = array())
	{
		$tpl = $tpl ? $tpl : $this->defaultTemplate;
		$links = $this->links();
		$path = $this->viewPath("{$tpl}.phtml");
		ob_start();
		include($path);
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * @param bool|true $hlSelected
	 * @return string
	 */
	public function renderLink($hlSelected = true)
	{
		$class = array();
		if ($hlSelected && $this->isSelected()) {
			$class['selected'] = 'selected';
		}
		$class = empty($class) ? '' : ' class="' . implode(' ', $class) . '"';
		return "<a href=\"{$this->url}\"{$class}>{$this->title}</a>";
	}
}