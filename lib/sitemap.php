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
    protected $name = 'sitemap.xml';
    /**
     * @var string
     */
    protected $childSitemapNamePattern = 'sitemap_#ID#.xml';
    /**
     * @var string
     */
    protected $siteId = SITE_ID;
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
     * @var string
     */
    protected static $add_item_event_name = 'sitemap.on_add_item';
    /**
     * @var string
     */
    protected static $add_section_event_name = 'sitemap.on_add_section';
    /**
     * @var string
     */
    protected static $add_nav_link_event_name = 'sitemap.on_add_nav_link';
    /**
     * @var string
     */
    protected static $add_event_name = 'sitemap.on_add';

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
        if (isset($_SERVER['HTTP_HOST'])) {
            $this->domain = $_SERVER['HTTP_HOST'];
        }
    }
    
    /**
     * @return string
     */
    public static function getAddItemEventName() {
        return self::$add_item_event_name;
    }
    
    /**
     * @return string
     */
    public static function getAddSectionEventName() {
        return self::$add_section_event_name;
    }
    
    /**
     * @return string
     */
    public static function getAddNavLinkEventName() {
        return self::$add_nav_link_event_name;
    }
    
    /**
     * @return string
     */
    public static function getAddEventName() {
        return self::$add_event_name;
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
     * @throw SitemapException
     */
    private function docRoot() {
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
    private function sitemapPath() {
        if (!$this->sitemapPath) {
           $this->sitemapPath = $this->docRoot();
        }
        return $this->sitemapPath;
    }

    /**
     * @return mixed
     * @throw SitemapException
     */
    private function createSitemap($path, $name, $class) {
        $class = '\\NilPortugues\\Sitemap\\' . $class;

        if (class_exists($class)) {
            // nilSitemap требудет, чтобы файла не было. Удаляем его, если он есть
            $tmp_path = rtrim($path, '/') . '/' . ltrim($name);
            if (file_exists($tmp_path)) unlink($tmp_path);

            return new $class($path, $name);
        } else {
             throw new SitemapException('Не получилось создать объект с классом '.$class .'. Класс не объявлен.');
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function prepareData($data) {
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
     * @param $url
     * @param int|false $time
     * @param string|false $priority
     * @param string|false $change_freq
     * @return $this
     */
    public function add($url, $time = false, $priority = false, $change_freq = false)
    {
        $data = array (
            'url' => $url,
            'lastmod' => $time,
            'priority' => $priority,
            'change_freq' => $change_freq
        );
        \TAO\Events::emit($this->getAddEventName(), $data);
        $data = $this->prepareData($data);
        $this->arrLoc[] = $data;
        return $this;
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
            \TAO\Events::emit($this->getAddSectionEventName(), $arrLoc, $section);
            $this->add(
                $arrLoc['url'],
                $arrLoc['lastmod'],
                $arrLoc['priority'],
                $arrLoc['change_freq']
            );
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
            \TAO\Events::emit($this->getAddItemEventName(), $arrLoc, $item);
            $this->add(
                $arrLoc['url'],
                $arrLoc['lastmod'],
                $arrLoc['priority'],
                $arrLoc['change_freq']
            );
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
            \TAO\Events::emit($this->getAddNavLinkEventName(), $arrLoc, $link);
            $this->add(
                $arrLoc['url'],
                $arrLoc['lastmod'],
                $arrLoc['priority'],
                $arrLoc['change_freq']
            );
            if ($link->count() > 0) {
                $link->filter($navigation->getFilter());
                $this->addNavigation($link);
                $link->filter();
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function finish()
    {
        if (count($this->arrLoc) >= $this->urlCountLimit) {
            $index = $this->createSitemap($this->sitemapPath(), $this->name, 'IndexSitemap');
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
            $this->buildSitemap($this->name, $this->arrLoc);
        }

        return $this;
    }


    /**
     * @return $this
     */
    public function buildSitemap($name, $arrLoc) {
        $sitemap = $this->createSitemap($this->sitemapPath(), $name, 'Sitemap');

        foreach($arrLoc as $data) {
            $item = new UrlItem($data['url']);
            $item->setLastMod($data['lastmod']);
            if ($data['priority']) {
                $item->setPriority($data['priority']);
            }
            if ($data['change_freq']) {
                $item->setChangeFreq($data['change_freq']);
            }
            $sitemap->add($item);
        }

        $sitemap->build();

        return $this;
    }

    /**
     * @param int $settingsId
     * @return $this
     */
    private function initSitemapSettings($settingsId) {
        $dbSitemap = SitemapTable::getById($settingsId);
        $this->settings = $dbSitemap->fetch();
        $this->site($this->settings['SITE_ID']);
        $this->settings['SETTINGS'] = unserialize($this->settings['SETTINGS']);

        return $this;
    }

    /**
     * @param int $settingsId
     * @return $this
     */
    public function makeByAdminSettings($settingsId) {
        $this->initSitemapSettings($settingsId);
        $this->sitemapPath = $this->docRoot() . $this->settings['SITE']['DIR'];
        $this->name = $this->settings['SETTINGS']['FILENAME_INDEX'];

        $this->addIblockElementsAndSections();
        $this->addFiles($this->settings['SITE']['DIR']);

        return $this;
    }

    /**
     * @param array $params
     * @return array
     */
    private function getIblockItems($params = array()) {
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

    /**
     * @param array $params
     * @return array
     */
    private function getSectionItems($params = array()) {
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

    private function addIblockElementsAndSections() {
        foreach($this->settings['SETTINGS']['IBLOCK_ACTIVE'] as $iblock_id => $is_active) {
            if ($is_active == 'Y') {
                // элементы инфоблока без привязки к разделам
                if (isset($this->settings['SETTINGS']['IBLOCK_ELEMENT'][$iblock_id]) && $this->settings['SETTINGS']['IBLOCK_ELEMENT'][$iblock_id] == 'Y') {
                    $items = $this->getIblockItems(array(
                        'IBLOCK_ID' => $iblock_id,
                        'ACTIVE' => 'Y',
                        'SECTION_ID' => false
                    ));
                    foreach ($items as $item) {
                        $arrLoc = array (
                            'url' => $item['DETAIL_PAGE_URL'],
                            'lastmod' => \TAO::timestamp($item['TIMESTAMP_X']),
                        );
                        \TAO\Events::emit($this->getAddItemEventName(), $arrLoc, $item);
                        $this->add(
                            $arrLoc['url'],
                            $arrLoc['lastmod'],
                            $arrLoc['priority'],
                            $arrLoc['change_freq']
                        );
                    }
                }

                $tmp = array();
                // элементы инфоблока с привязкой к разделам
                if (isset($this->settings['SETTINGS']['IBLOCK_SECTION_ELEMENT'][$iblock_id])) {
                    foreach ($this->settings['SETTINGS']['IBLOCK_SECTION_ELEMENT'][$iblock_id] as $section_id => $is_add_elements) {
                        if ($is_add_elements == 'Y') {
                            $items = $this->getIblockItems(array(
                                'IBLOCK_ID' => $iblock_id,
                                'ACTIVE' => 'Y',
                                'SECTION_ID' => $section_id
                            ));

                            $tmp[$section_id] = array();
                            foreach ($items as $item) {
                                $arrLoc = array (
                                    'url' => $item['DETAIL_PAGE_URL'],
                                    'lastmod' => \TAO::timestamp($item['TIMESTAMP_X']),
                                );
                                \TAO\Events::emit($this->getAddItemEventName(), $arrLoc, $item);
                                $this->add(
                                    $arrLoc['url'],
                                    $arrLoc['lastmod'],
                                    $arrLoc['priority'],
                                    $arrLoc['change_freq']
                                );
                                $tmp[$section_id][] = $info;
                            }
                        }
                    }
                }

                // разделы инфоблока
                if (isset($this->settings['SETTINGS']['IBLOCK_SECTION_SECTION'][$iblock_id])) {
                    foreach ($this->settings['SETTINGS']['IBLOCK_SECTION_SECTION'][$iblock_id] as $section_id => $is_add_section) {
                        if ($is_add_section == 'Y') {
                            $items = $this->getSectionItems(array(
                                'IBLOCK_ID' => $iblock_id,
                                'ACTIVE' => 'Y',
                                'ID' => $section_id
                            ));

                            foreach ($items as $item) {
                                // lastmod для раздела - самая поздняя дата среди дат модификации раздела и его элементов
                                $lastMod = \TAO::timestamp($item['TIMESTAMP_X']);
                                foreach($tmp[$section_id] as $element) {
                                    $lastMod = max($lastMod, $element['lastmod']);
                                }

                                $arrLoc = array (
                                    'url' => $item['SECTION_PAGE_URL'],
                                    'lastmod' => $lastMod,
                                );

                                \TAO\Events::emit($this->getAddSectionEventName(), $arrLoc, $item);
                                $this->add(
                                    $arrLoc['url'],
                                    $arrLoc['lastmod'],
                                    $arrLoc['priority'],
                                    $arrLoc['change_freq']
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    private function addFiles($dir) {
        $structure = \CSeoUtils::getDirStructure($this->settings['SETTINGS']['logical'], $this->settings['SITE_ID'], $dir);

        foreach($structure as $cur) {
            if ($cur['TYPE'] == 'D') {
               $this->addFiles($cur['DATA']['ABS_PATH']);
            } else {
                $dirKey = "/".ltrim($cur['DATA']['ABS_PATH'], "/");
                $is_dir_active = true;

                foreach($this->settings['SETTINGS']['DIR'] as $tmp_dir => $is_active) {
                    if (strpos($dirKey, $tmp_dir) === 0) {
                        if ($is_active == 'N') {
                            $is_dir_active = false;
                        } else {
                            $is_dir_active = true;
                        }
                    }
                }

                if(($is_dir_active && !isset($this->settings['SETTINGS']['FILE'][$dirKey]))
                    || (isset($this->settings['SETTINGS']['FILE'][$dirKey])
                        && $this->settings['SETTINGS']['FILE'][$dirKey] == 'Y'))
                {
                    if(preg_match($this->settings['SETTINGS']['FILE_MASK_REGEXP'], $cur['FILE']))
                    {
                        $f = new IO\File($cur['DATA']['PATH'], $this->settings['SITE_ID']);
                        $arrLoc = array(
                            'url' => $this->getFileUrl($f),
                            'lastmod' => $f->getModificationTime(),
                        );

                        $this->add(
                            $arrLoc['url'],
                            $arrLoc['lastmod'],
                            $arrLoc['priority'],
                            $arrLoc['change_freq']
                        );
                    }
                }
            }
        }
    }

    public function getFileUrl(IO\File $f)
    {
        static $indexNames;
        if(!is_array($indexNames))
        {
            $indexNames = GetDirIndexArray();
        }
        $documentRoot  = Path::normalize($this->docRoot()).$this->settings['SITE']['DIR'];
        $path = '/'.substr($f->getPath(), strlen($documentRoot));

        $path = Path::convertLogicalToUri($path);

        $path = in_array($f->getName(), $indexNames)
            ? str_replace('/'.$f->getName(), '/', $path)
            : $path;

        return '/'.ltrim($path, '/');
    }

}