<?php

namespace TAO;

use Bitrix\Seo\SitemapRuntime;


/**
 * Class Sitemap
 * @package TAO
 */
class Sitemap
{
    /**
     * @var string
     */
    protected $path;
    /**
     * @var
     */
    protected $file;
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
     * Sitemap constructor.
     * @param string $path
     */
    public function __construct($path = '')
    {
        $path = trim($path);
        if (empty($path)) {
            $path = 'sitemap.xml';
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            $this->domain = $_SERVER['HTTP_HOST'];
        }
        $this->path = $path;
    }

    /**
     * @param $id
     */
    public function siteId($id)
    {
        $this->siteId = $id;
        return $this;
    }

    /**
     * @param $protocol
     */
    public function protocol($protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @param $domain
     */
    public function domain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @return SitemapRuntime
     */
    public function file()
    {
        if (!$this->file) {
            $this->file = new SitemapRuntime(1, $this->path, array(
                'SITE_ID' => $this->siteId,
                'PROTOCOL' => $this->protocol,
                'DOMAIN' => $this->domain,
            ));
            $this->file->addHeader();
        }
        return $this->file;
    }

    /**
     * @param $url
     * @param bool|false $time
     * @return $this
     */
    public function addEntry($url, $time = false)
    {
        if (!$time) {
            $time = time();
        }
        if (is_numeric($time)) {
            $dt = new \DateTime();
            $time = $dt->setTimestamp($time)->format(DATE_W3C);
        }
        if (preg_match('{^//}', $url)) {
            $url = $this->protocol . $url;
        }
        if ($url[0] == '/') {
            $url = "{$this->protocol}://{$this->domain}{$url}";
        }
        $this->file()->addEntry(array('XML_LOC' => $url, 'XML_LASTMOD' => $time));
        return $this;
    }

    /**
     * @param $code
     * @param array $args
     * @return $this
     */
    public function addInfoblockSections($code, $args = array())
    {
        \TAO::infoblock($code)->sitemapSections($this, $args);
        return $this;
    }

    /**
     * @param $code
     * @param array $args
     * @return $this
     */
    public function addInfoblockElements($code, $args = array())
    {
        \TAO::infoblock($code)->sitemapElements($this, $args);
        return $this;
    }

    /**
     * @param bool|false $navigation
     * @return $this
     */
    public function addNavigation($navigation = false)
    {
        if (!$navigation) {
            $navigation = \TAO::navigation();
        }
        $navigation->sitemap($this);
        return $this;
    }

    /**
     * @return $this
     */
    public function close()
    {
        $this->file()->finish();
        return $this;
    }

    /**
     * @return Sitemap
     */
    public function finish()
    {
        return $this->close();
    }

    /**
     * @param $name
     * @param $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        if (method_exists($this->file(), $name)) {
            return call_user_func_array(array($this->file(), $name), $args);
        }
        return $this;
    }

}