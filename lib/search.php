<?php

namespace TAO;

\CModule::IncludeModule("search");

class Search
{
	protected $search = null;
	protected $query = null;
	protected $queryVar = false;
	protected $perPage = 0;
	protected $pagerVar = false;
	protected $showAll = true;
	protected $sort = array("CUSTOM_RANK" => "DESC", "RANK" => "DESC", "DATE_CHANGE" => "DESC");
	protected $params = false;
	protected $paramsEx = array();//'STEMMING' => false);
	protected $only = false;
	protected $exclude = false;
	protected $site = false;
	protected $url = false;

	public function search()
	{
		if (empty($this->search)) {
			$this->search = new \CSearch();
		}
		return $this->search;
	}

	public function searchPrepared()
	{
		$params = $this->params;
		if (!$params) {
			if (empty($this->query)) {
				throw new NoQueryException("Query is empty");
			}
			$params = array(
				'QUERY' => $this->query,
			);
			if ($this->site) {
				$params['SITE_ID'] = $this->site;
			}
			if ($this->url) {
				$params['URL'] = $this->url;
			}
			if (is_array($this->only) && count($this->only) > 0) {
				$params['MODULE_ID'] = 'iblock';
				$blocks = array();
				$block = false;
				foreach ($this->only as $code) {
					$block = \TAO::infoblock($code)->id();
					$blocks[] = $block;
				}
				$params['PARAM2'] = count($blocks) > 1 ? $blocks : $block;
			}
			if (is_array($this->exclude) && count($this->exclude) > 0) {
				$params['MODULE_ID'] = 'iblock';
				$blocks = array();
				$block = false;
				foreach ($this->exclude as $code) {
					$block = \TAO::infoblock($code)->id();
					$blocks[] = $block;
				}
				$params['!=PARAM2'] = count($blocks) > 1 ? $blocks : $block;
			}
		}
		$search = $this->search();
		$search->Search($params, $this->sort, $this->paramsEx);
		return $search;
	}

	public function query($query)
	{
		$this->search = null;
		$this->query = $query;
		return $this;
	}

	public function url($url)
	{
		$this->url = $url;
		return $this;
	}

	public function queryVar($name)
	{
		$this->queryVar = $name;
		return $this;
	}

	public function pagerVar($name)
	{
		$this->pagerVar = $name;
		return $this;
	}

	public function getQuery()
	{
		if (empty($this->query)) {
			if ($this->queryVar && isset($_GET[$this->queryVar])) {
				$this->query(trim($_GET[$this->queryVar]));
			}
		}
		return $this->query;
	}

	public function site($site)
	{
		$this->site = $site;
		return $this;
	}

	public function only()
	{
		$this->only = func_get_args();
		return $this;
	}

	public function exclude()
	{
		$this->exclude = func_get_args();
		return $this;
	}

	public function perPage($n)
	{
		$this->perPage = $n;
		$this->showAll = ($n == 0);
		return $this;
	}

	public function getCount()
	{
		return $this->searchPrepared()->selectedRowsCount();
	}

	public function getNumPages()
	{
		$search = $this->searchPrepared();
		$search->NavStart($this->perPage, $this->showAll, 1);
		return $search->NavPageCount;
	}

	public function getRows($page = 1)
	{
		$search = $this->searchPrepared();
		$search->NavStart($this->perPage, $this->showAll, $page);
		$rows = array();
		while ($row = $search->GetNext()) {
			$rows[] = new SearchItem($row);
		}
		return $rows;
	}

	public function viewPath($file)
	{
		$dirs = array(\TAO::localDir('views'), \TAO::taoDir('views'));
		$path = \TAO::filePath($dirs, "{$file}.phtml");
		return $path;
	}

	public function render($args = array())
	{
		if (!is_array($args) && (int)$args > 0) {
			$args = array('page' => (int)$args);
		}
		foreach ($args as $k => $v) {
			$$k = $v;
		}

		if (isset($args['query_var'])) {
			$this->queryVar($args['query_var']);
		}

		if (isset($args['pager_var'])) {
			$this->pagerVar($args['pager_var']);
		}

		$query = $this->getQuery();
		if ($query) {

			$count = $this->getCount();
			$numPages = $this->getNumPages();
			$rows = $this->getRows($args);
		} else {
			$count = 0;
			$numPages = 1;
			$rows = array();
		}
		$tpl = $this->viewPath('search');
		$tplRow = $this->viewPath('search-row');

		ob_start();
		include($tpl);
		$content = ob_get_clean();
		return $content;
	}
}

class SearchItem implements \ArrayAccess
{
	protected $data;

	public function __construct($data = array())
	{
		$this->data = $data;
	}

	public function snippet()
	{
		return $this['BODY_FORMATED'];
	}

	public function url()
	{
		return $this['URL_WO_PARAMS'];
	}

	public function time()
	{
		return \TAO::timestamp($this['FULL_DATE_CHANGE']);
	}

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		return $this->data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}
}

class Exception extends \TAOException
{
}

class NoQueryException extends Exception
{
}

