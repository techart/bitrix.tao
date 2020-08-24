<?php

namespace TAO;

use Bitrix\Seo\SitemapTable;
use Bitrix\Main\SiteTable;
use Bitrix\Main\IO;
use Bitrix\Main\IO\Path;

use NilPortugues\Sitemap\Item\Url\UrlItem;
use NilPortugues\Sitemap\Item\Index\IndexItem;
use NilPortugues\Sitemap\SitemapException;


/**
 * Class Sitemap
 * @package TAO
 */
class Sitemap
{
	/**
	 * @var string
	 */
	protected $name = null;
	/**
	 * @var string
	 */
	protected $childSitemapNamePattern = 'sitemap_#ID#.xml';
	/**
	 * @var string
	 */
	protected $siteId;
	/**
	 * @var string
	 */
	protected $protocol = 'http';
	/**
	 * @var string
	 */
	protected $domain = 'site.com';
	/**
	 * @var array
	 */
	protected $settings = array();
	/**
	 * @var array
	 */
	protected $arrLoc = array();
	/**
	 * @var int
	 */
	protected $urlCountLimit = 50000;
	/**
	 * @var string
	 */
	protected $sitemapPath;
	/**
	 * @var string
	 */
	protected $docRoot;
	/**
	 * @var array
	 */
	protected $addedElementsIds = array();

	/**
	 * @var string
	 */
	protected static $addItemEventName = 'sitemap.add_item';
	/**
	 * @var string
	 */
	protected static $addSectionEventName = 'sitemap.add_section';
	/**
	 * @var string
	 */
	protected static $addNavLinkEventName = 'sitemap.add_nav_link';
	/**
	 * @var string
	 */
	protected static $addEventName = 'sitemap.add';

	/**
	 * Sitemap constructor
	 * @param string $name
	 */
	public function __construct($name = '')
	{
		$name = trim($name);
		if (!empty($name)) {
			$this->name = $name;
		}

		$this->site(SITE_ID);
	}

	/**
	 * @return string
	 */
	public static function getAddItemEventName()
	{
		return self::$addItemEventName;
	}

	/**
	 * @return string
	 */
	public static function getAddSectionEventName()
	{
		return self::$addSectionEventName;
	}

	/**
	 * @return string
	 */
	public static function getAddNavLinkEventName()
	{
		return self::$addNavLinkEventName;
	}

	/**
	 * @return string
	 */
	public static function getAddEventName()
	{
		return self::$addEventName;
	}

	/**
	 * @param $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param $id
	 * @return $this
	 */
	public function site($id)
	{
		$this->siteId = $id;
		$dbSite = SiteTable::getByPrimary($this->siteId);
		if ($siteSettings = $dbSite->fetch()) {
			$this->settings['SITE'] = $siteSettings;
			$this->domain = $this->settings['SITE']['SERVER_NAME'];
		}
		return $this;
	}

	/**
	 * @param $protocol
	 * @return $this
	 */
	public function protocol($protocol)
	{
		$this->protocol = $protocol;
		return $this;
	}

	/**
	 * @param $domain
	 * @return $this
	 */
	public function domain($domain)
	{
		$this->domain = $domain;
		return $this;
	}

	/**
	 * @return string
	 */
	protected function getName()
	{
		if (is_null($this->name)) {
			return $this->name = 'sitemap_' . $this->siteId . '.xml';
		} else {
			return $this->name;
		}
	}

	/**
	 * @return string
	 * @throw SitemapException
	 */
	protected function docRoot()
	{
		if (!$this->docRoot) {
			if (!$this->siteId) {
				throw new SitemapException('Не задан обязательный параметр siteId');
			} else {
				$this->docRoot = SiteTable::getDocumentRoot($this->siteId);
			}
		}
		return $this->docRoot;
	}

	/**
	 * @return string
	 */
	protected function sitemapPath()
	{
		if (!$this->sitemapPath) {
			$this->sitemapPath = $this->docRoot();
		}
		return $this->sitemapPath;
	}

	/**
	 * @param $code
	 * @param array $args
	 * @return $this
	 */
	public function addInfoblockSections($code, $args = array())
	{
		$infoblock = \TAO::infoblock($code);
		foreach ($infoblock->getSections($args) as $section) {
			$arrLoc = $infoblock->sitemapSectionData($section);
			$this->triggerEventAndAdd($this->getAddSectionEventName(), $arrLoc, $section);
		}
		return $this;
	}

	/**
	 * @param $code
	 * @param array $args
	 * @return $this
	 */
	public function addInfoblockElements($code, $args = array())
	{
		$infoblock = \TAO::infoblock($code);
		foreach ($infoblock->getItems($args) as $item) {
			$arrLoc = $infoblock->sitemapElementData($item);
			$this->triggerEventAndAdd($this->getAddItemEventName(), $arrLoc, $item);
		}
		return $this;
	}

	/**
	 * @param \TAO\Navigation|false $navigation
	 * @return $this
	 */
	public function addNavigation($navigation = false)
	{
		if (!$navigation) {
			$navigation = \TAO::navigation();
		}
		foreach ($navigation->links() as $link) {
			$arrLoc = array(
				'url' => $link->url
			);
			$this->triggerEventAndAdd($this->getAddNavLinkEventName(), $arrLoc, $link);
			if ($link->count() > 0) {
				$link->filter($navigation->getFilter());
				$this->addNavigation($link);
				$link->filter();
			}
		}
		return $this;
	}

	/**
	 * @param int $settingsId
	 * @return $this
	 */
	public function makeByAdminSettings($settingsId)
	{
		$this->initSitemapSettings($settingsId);
		if ($this->settings) {
			$this->sitemapPath = $this->docRoot() . $this->settings['SITE']['DIR'];

			$this->addIblockElementsAndSections();
			$this->addFiles($this->settings['SITE']['DIR']);
		}

		return $this;
	}

	/**
	 * @param int $settingsId
	 */
	protected function initSitemapSettings($settingsId)
	{
		$dbSitemap = SitemapTable::getById($settingsId);
		$this->settings = $dbSitemap->fetch();
		if ($this->settings) {
			$this->site($this->settings['SITE_ID']);
			$this->settings['SETTINGS'] = unserialize($this->settings['SETTINGS']);
		}
	}

	protected function addIblockElementsAndSections()
	{
		foreach ($this->settings['SETTINGS']['IBLOCK_ACTIVE'] as $iblockId => $isActive) {
			if ($isActive == 'Y') {
				$iblock = $this->getIblockById($iblockId);

				if (empty($iblock)) {
					continue;
				}

				$this->checkIblockUrlPatterns($iblock);
				if ($this->shouldAddIblockSections($iblockId)) {
					// корневые разделы
					$items = $this->getSectionItems(array(
						'IBLOCK_ID' => $iblockId,
						'ACTIVE' => 'Y',
						'SECTION_ID' => false,
					));

					foreach ($items as $item) {
						$this->addSectionAndItsElements($item, $iblockId);
					}
				}

				if ($this->shouldAddIblockElements($iblockId)) {
					$this->addElements(false, $iblockId);
				}

				if($this->settings['SETTINGS']['IBLOCK_LIST'][$iblockId] == 'Y')
				{
					$iblock['IBLOCK_ID'] = $iblock['ID'];
					$iblock['LANG_DIR'] = $this->settings['SITE']['DIR'];

					$url = \CIBlock::ReplaceDetailUrl($iblock['LIST_PAGE_URL'], $iblock, false, "");

					$arrLoc = array(
						'url' => $url,
						'lastmod' => \TAO::timestamp($iblock['TIMESTAMP_X']),
					);

					$this->add(
						$arrLoc['url'],
						$arrLoc['lastmod']
					);
				}
			}
		}
	}

	/**
	 * @param string $iblockId
	 * @return array
	 */
	protected function getIblockById($iblockId) {
		$iblockResult = \CIBlock::GetList(
			array(),
			array(
				'SITE_ID' => $this->settings['SITE_ID'],
				'ID' => $iblockId
			)
		);

		$iblock = $iblockResult->Fetch();
		if (is_array($iblock)) {
			return $iblock;
		} else {
			return array();
		}
	}

	protected function checkIblockUrlPatterns($iblock)
	{
		if(strlen($iblock['LIST_PAGE_URL']) <= 0)
			$this->settings['SETTINGS']['IBLOCK_LIST'][$iblock['ID']] = 'N';
		if(strlen($iblock['SECTION_PAGE_URL']) <= 0)
			$this->settings['SETTINGS']['IBLOCK_SECTION'][$iblock['ID']] = 'N';
		if(strlen($iblock['DETAIL_PAGE_URL']) <= 0)
			$this->settings['SETTINGS']['IBLOCK_ELEMENT'][$iblock['ID']] = 'N';
	}

	protected function shouldAddIblockSections($iblockId) {
		return (
			array_key_exists($iblockId, $this->settings['SETTINGS']['IBLOCK_SECTION'])
			&& (
				$this->settings['SETTINGS']['IBLOCK_SECTION'][$iblockId] == 'Y'
				|| array_key_exists($iblockId, $this->settings['SETTINGS']['IBLOCK_SECTION_SECTION'])
			)
		);
	}

	protected function shouldAddIblockElements($iblockId) {
		return (
			array_key_exists($iblockId, $this->settings['SETTINGS']['IBLOCK_ELEMENT'])
			&& (
				$this->settings['SETTINGS']['IBLOCK_ELEMENT'][$iblockId] == 'Y'
				|| array_key_exists($iblockId, $this->settings['SETTINGS']['IBLOCK_SECTION_ELEMENT'])
			)
		);
	}

	protected function addSectionAndItsElements($section, $iblockId) {
		if ($this->shouldAddSectionElements($section['ID'], $iblockId)) {
			$this->addElements($section['ID'], $iblockId);
		}

		$subSections = $this->getSectionItems(array(
			'IBLOCK_ID' => $iblockId,
			'ACTIVE' => 'Y',
			'SECTION_ID' => $section['ID'],
		));

		foreach ($subSections as $item) {
			$this->addSectionAndItsElements($item, $iblockId);
		}

		if ($this->shouldAddSubsections($section['ID'], $iblockId)) {
			$arrLoc = array(
				'url' => $section['SECTION_PAGE_URL'],
				'lastmod' => \TAO::timestamp($section['TIMESTAMP_X']),
			);

			$this->triggerEventAndAdd($this->getAddSectionEventName(), $arrLoc, $item);
		}
	}

	protected function shouldAddSectionElements($sectionId, $iblockId) {
		return (
			$this->shouldAddIblockElements($iblockId)
			&& (
				!array_key_exists($sectionId, $this->settings['SETTINGS']['IBLOCK_SECTION_ELEMENT'][$iblockId])
				|| $this->settings['SETTINGS']['IBLOCK_SECTION_ELEMENT'][$iblockId][$sectionId] == 'Y'
			)
		);
	}

	protected function shouldAddSubsections($sectionId, $iblockId) {
		return (
			!array_key_exists($sectionId, $this->settings['SETTINGS']['IBLOCK_SECTION_SECTION'][$iblockId])
			|| $this->settings['SETTINGS']['IBLOCK_SECTION_SECTION'][$iblockId][$sectionId] == 'Y'
		);
	}

	protected function addElements($sectionId, $iblockId) {
		$items = $this->getInfoblockItems(array(
			'IBLOCK_ID' => $iblockId,
			'ACTIVE' => 'Y',
			'SECTION_ID' => $sectionId
		));

		foreach ($items as $item) {
			if (!in_array($item['ID'], $this->addedElementsIds)) {
				$infoblock = \TAO::infoblock($iblockId);
				$arrLoc = $infoblock->sitemapElementData($item);
				$this->triggerEventAndAdd($this->getAddItemEventName(), $arrLoc, $item);

				$this->addedElementsIds[] = $item['ID'];
			}
		}
	}

	/**
	 * @param array $params
	 * @return array
	 */
	protected function getSectionItems($params = array())
	{
		if (isset($params['IBLOCK_ID'])) {
			$code = \TAO::getInfoblockCode($params['IBLOCK_ID']);
			if ($code) {
				return \TAO::infoblock($code)->getSections(array('filter' => $params));
			}
		}

		$items = array();
		$result = \CIBlockSection::GetList(array(), $params);
		while ($row = $result->GetNext(true, false)) {
			$items[] = $row;
		}

		return $items;
	}

	/**
	 * @param array $params
	 * @return array
	 */
	protected function getInfoblockItems($params = array())
	{
		if (isset($params['IBLOCK_ID'])) {
			$code = \TAO::getInfoblockCode($params['IBLOCK_ID']);
			if ($code) {
				return \TAO::infoblock($code)->getItems(array('filter' => $params));
			}
		}

		$items = array();
		$result = \CIBlockElement::GetList(array(), $params);
		while ($row = $result->GetNext(true, false)) {
			$items[] = $row;
		}

		return $items;
	}

	protected function addFiles($dir)
	{
		$structure = \CSeoUtils::getDirStructure($this->settings['SETTINGS']['logical'], $this->settings['SITE_ID'], $dir);

		foreach ($structure as $cur) {
			if ($cur['TYPE'] == 'D') {
				$this->addFiles($cur['DATA']['ABS_PATH']);
			} else {
				$dirKey = "/" . ltrim($cur['DATA']['ABS_PATH'], "/");
				$isDirActive = true;

				foreach ($this->settings['SETTINGS']['DIR'] as $tmpDir => $isActive) {
					if (strpos($dirKey, $tmpDir) === 0) {
						if ($isActive == 'N') {
							$isDirActive = false;
						} else {
							$isDirActive = true;
						}
					}
				}

				if (($isDirActive && !isset($this->settings['SETTINGS']['FILE'][$dirKey]))
					|| (isset($this->settings['SETTINGS']['FILE'][$dirKey])
						&& $this->settings['SETTINGS']['FILE'][$dirKey] == 'Y')) {
					if (preg_match($this->settings['SETTINGS']['FILE_MASK_REGEXP'], $cur['FILE'])) {
						$f = new IO\File($cur['DATA']['PATH'], $this->settings['SITE_ID']);
						$arrLoc = array(
							'url' => $this->getFileUrl($f),
							'lastmod' => $f->getModificationTime(),
						);

						$this->add(
							$arrLoc['url'],
							$arrLoc['lastmod']
						);
					}
				}
			}
		}
	}

	protected function getFileUrl(IO\File $f)
	{
		static $indexNames;
		if (!is_array($indexNames)) {
			$indexNames = GetDirIndexArray();
		}
		$documentRoot = Path::normalize($this->docRoot()) . $this->settings['SITE']['DIR'];
		$path = '/' . substr($f->getPath(), strlen($documentRoot));

		$path = Path::convertLogicalToUri($path);

		$path = in_array($f->getName(), $indexNames)
			? str_replace('/' . $f->getName(), '/', $path)
			: $path;

		return '/' . ltrim($path, '/');
	}

	protected function triggerEventAndAdd($eventName, $arrLoc, $item = null) {
		$shouldAdd = true;
		\TAO\Events::emit($eventName, $arrLoc, $item, $shouldAdd);
		if ($shouldAdd) {
			$this->add(
				$arrLoc['url'],
				$arrLoc['lastmod'],
				$arrLoc['priority'],
				$arrLoc['changefreq']
			);
		}
	}

	/**
	 * @param $url
	 * @param int|false $time
	 * @param string|false $priority
	 * @param string|false $changefreq
	 * @return $this
	 */
	public function add($url, $time = false, $priority = false, $changefreq = false)
	{
		$data = array(
			'url' => $url,
			'lastmod' => $time,
			'priority' => $priority,
			'changefreq' => $changefreq
		);
		$shouldAdd = true;
		\TAO\Events::emit($this->getAddEventName(), $data, $shouldAdd);
		if ($shouldAdd) {
			$data = $this->prepareData($data);
			$this->arrLoc[] = $data;
		}
		return $this;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public function prepareData($data)
	{
		if (!$data['lastmod']) {
			$data['lastmod'] = time();
		}
		if (is_numeric($data['lastmod'])) {
			$data['lastmod'] = date('c', $data['lastmod']);
		}
		if (preg_match('{^//}', $data['url'])) {
			$data['url'] = $this->protocol . $data['url'];
		}

		if ($data['url'][0] == '/' || !preg_match('{^http}', $data['url'])) {
			$data['url'] = $this->protocol . '://' . rtrim($this->domain, '/') . '/' . ltrim($data['url'], '/');
		}

		return $data;
	}

	/**
	 * @return $this
	 */
	public function finish()
	{
		if (count($this->arrLoc) >= $this->urlCountLimit) {
			$index = $this->createSitemap($this->sitemapPath(), $this->getName(), 'IndexSitemap');
			$arrLocChunks = array_chunk($this->arrLoc, $this->urlCountLimit);

			foreach ($arrLocChunks as $i => $arrLoc) {
				$fileName = str_replace('#ID#', $i, $this->childSitemapNamePattern);
				$this->buildSitemap($fileName, $arrLoc);

				$data = array(
					'url' => $fileName
				);
				$data = $this->prepareData($data);
				$item = new IndexItem($data['url']);
				$index->add($item);
			}

			$index->build();
		} else {
			$this->buildSitemap($this->getName(), $this->arrLoc);
		}

		return $this;
	}

	/**
	 * @return mixed
	 * @throw SitemapException
	 */
	protected function createSitemap($path, $name, $class)
	{
		$class = '\\NilPortugues\\Sitemap\\' . $class;

		if (class_exists($class)) {
			// nilSitemap требудет, чтобы файла не было. Удаляем его, если он есть
			$tmpPath = rtrim($path, '/') . '/' . ltrim($name);
			if (file_exists($tmpPath)) unlink($tmpPath);

			return new $class($path, $name);
		} else {
			throw new SitemapException('Не получилось создать объект с классом ' . $class . '. Класс не объявлен.');
		}
	}


	/**
	 * @return $this
	 */
	protected function buildSitemap($name, $arrLoc)
	{
		$sitemap = $this->createSitemap($this->sitemapPath(), $name, 'Sitemap');

		foreach ($arrLoc as $data) {
			$item = new UrlItem($data['url']);
			$item->setLastMod($data['lastmod']);
			if ($data['priority']) {
				$item->setPriority($data['priority']);
			}
			if ($data['changefreq']) {
				$item->setChangeFreq($data['changefreq']);
			}
			$sitemap->add($item);
		}

		$sitemap->build();

		return $this;
	}
}
