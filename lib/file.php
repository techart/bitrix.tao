<?php

namespace TAO;

/**
 * Class File
 * @package TAO
 */
class File
{
	/**
	 * @var array|bool
	 */
	public $fieldsData = false;
	/**
	 * @var int|bool
	 */
	public $id = false;
	/** @var string */
	private $name = '';
	protected $filters;

	/**
	 * File constructor.
	 * @param int|File $id
	 */
	public function __construct($id)
	{
		if ($id instanceof File) {
			$this->id = $id->id;
			$this->fieldsData = $id->fieldsData;
			return null;
		}
		$res = \CFile::GetById($id);
		while ($row = $res->Fetch()) {
			$this->fieldsData = $row;
			$this->id = $id;
			return null;
		}
	}

	/**
	 * @param $id
	 * @return array
	 */
	public static function getData($id)
	{
		$res = \CFile::GetById($id);
		while ($row = $res->Fetch()) {
			return $row;
		}
	}

	/**
	 * @param $path
	 * @param string $dir
	 * @return File
	 */
	public static function make($path, $dir = 'iblock')
	{
		$m = \CFile::MakeFileArray($path);
		if (is_array($m)) {
			$id = \CFile::SaveFile($m, $dir);
			return new self($id);
		}
	}

	/**
	 * @param $how
	 * @return array
	 */
	public function parseImageResize($how)
	{
		$type = 'fit';
		if (preg_match('{^(fit|crop)(.+)$}', $how, $m)) {
			$type = $m[1];
			$how = $m[2];
		}
		$size = explode('x', $how);
		$w = (int)$size[0];
		$w = $w > 0 ? $w : 999999;
		$h = (int)$size[1];
		$h = $h > 0 ? $h : 999999;
		return array($w, $h, $type);
	}

	/**
	 * @return mixed
	 */
	public function contentType()
	{
		return $this->fieldsData['CONTENT_TYPE'];
	}

	/**
	 * @return bool
	 */
	public function isImage()
	{
		return strpos($this->contentType(), 'image/') === 0;
	}

	/**
	 * @return string
	 */
	public function path()
	{
		return \TAO::rootDir(\CFile::GetPath($this->id));
	}

	/**
	 * @return mixed
	 */
	public function url()
	{
		return \CFile::GetPath($this->id);
	}

	/**
	 * Позволяет получить имя файла с расширением
	 *
	 * @return string
	 */
	public function name()
	{
		return $this->name ?: $this->name = pathinfo($this->path(), PATHINFO_BASENAME);
	}

	public function setFilters($filters)
	{
		$this->filters = $filters;
		return $this;
	}

	/**
	 * @param $how
	 * @return mixed
	 */
	public function resizedImage($how)
	{
		list($w, $h, $type) = $this->parseImageResize($how);
		$data = \CFile::ResizeImageGet(
			$this->fieldsData,
			array('width' => $w, 'height' => $h),
			$type == 'fit' ? BX_RESIZE_IMAGE_PROPORTIONAL : BX_RESIZE_IMAGE_EXACT,
			true,
			$this->filters
		);
		return $data['src'] ? $data['src'] : $this->url();
	}

	/**
	 * @param $how
	 * @param array $args
	 * @return string
	 */
	public function showImage($how = false, $args = array())
	{
		$image = $this->url();
		$w = 0;
		$h = 0;
		if ($how) {
			list($w, $h) = $this->parseImageResize($how);
			$image = $this->resizedImage($how);
		}

		$url = isset($args['url']) ? $args['url'] : '';
		$popup = isset($args['popup']) ? $args['popup'] : false;
		$title = isset($args['title']) ? $args['title'] : false;
		$extra = isset($args['extra']) ? $args['extra'] : 'border="0"';
		return \CFile::ShowImage($image, $w, $h, $extra, $url, $popup, $title);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		if ($this->id) {
			return \CFile::GetPath($this->id);
		}
		return 'File error!';
	}
}
