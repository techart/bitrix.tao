<?php
spl_autoload_register(array('\TAO', 'autoload'));
\CModule::IncludeModule("iblock");


\TAO::load('type');
\TAO::load('infoblock');
\TAO::load('cache');
\TAO::load('entity');
\TAO::load('infoblock_handlers');
\TAO::load('tables_schema');
\TAO::load('urls');
\TAO::load('auth');


/**
 * Class TAO
 */
class TAO
{
    /**
     * @var array
     */
    static $config = array(
        'check_schema' => true,
        'admin_menu_export' => true,
        'pager_class' => '\\TAO\\Pager',
        'navigation_class' => '\\TAO\\Navigation',
        'fs_pages' => true,
        'elements' => true,
        'less_cache' => 'cache/less',
    );

    /**
     * @var array
     */
    static $globals = array();

    /**
     * @var array
     */
    static $infoblocks = array();

    /**
     * @var array
     */
    static $i2code = array();

    /**
     * @var array
     */
    static $bundles = array();

    /**
     * @var string
     */
    static $layout = 'work';

    /**
     * @var \TAO\Assets
     */
    public static $assets;

    /**
     * @return \CMain
     */
    public static function app()
    {
        return $GLOBALS['APPLICATION'];
    }

    /**
     * Возвращает данные сайта по ID (для многосайтовых конфигурация)
     *
     * Если ID сайта не передан, то возвращает данные текущего сайта
     *
     * @param bool|false $id
     * @return mixed
     */
    public static function getSiteData($id = false)
    {
        static $data = array();

        if (!$id) {
            $id = SITE_ID;
        }

        if (!isset($data[$id])) {
            $res = \CSite::GetByID($id);
            $data[$id] = $res->Fetch();
        }

        return $data[$id];
    }

    /**
     * Возвращает код языка сайта
     *
     * Если ID сайта не передан, то возвращает язык текущего сайта
     *
     * @param bool|false $id
     * @return mixed
     */
    public static function getSiteLang($id = false)
    {
        $data = self::getSiteData($id);
        return $data['LANGUAGE_ID'];
    }


    /**
     * Возвращает данные по коду языка
     *
     * Если код языка не передан, то возвращает данные по языку текущего сайта
     *
     * @param bool|false $id
     * @return mixed
     */
    public static function getLangData($id = false)
    {
        static $data = array();

        if (!$id) {
            $id = self::getSiteLang();
        }

        if (!isset($data[$id])) {
            $res = \CLanguage::GetByID($id);
            $data[$id] = $res->Fetch();
        }

        return $data[$id];
    }

    /**
     * Добавляет бандл
     *
     * @param $name
     * @return mixed
     * @throws TAOBundleNotFoundException
     */
    public static function addBundle($name)
    {
        $bundle = \TAO\Bundle::findBundle($name);
        if ($bundle) {
            self::$bundles[$name] = $bundle;
            return $bundle;
        } else {
            throw new TAOBundleNotFoundException("Bundle {$name} not found");
        }
    }

    /**
     * @throws TAOBundleNotFoundException
     */
    public static function addBundles()
    {
        foreach (func_get_args() as $name) {
            self::addBundle($name);
        }
    }

    /**
     * @return array
     */
    public static function bundles()
    {
        return self::$bundles;
    }

    /**
     * @param $name
     * @return mixed
     * @throws TAOBundleNotFoundException
     */
    public static function bundle($name)
    {
        if (isset(self::$bundles[$name])) {
            return self::$bundles[$name];
        }
        return self::addBundle($name);
    }

    /**
     * @return bool
     */
    public static function cache()
    {
        return TAO\Cache::instance();
    }

    /**
     * @return mixed
     */
    public static function navigation()
    {
        static $navigation;
        if (empty($navigation)) {
            return $navigation = new self::$config['navigation_class'];
        }
        return $navigation;
    }

    /**
     * @param string $page
     * @return mixed
     */
    public static function pager($page = 'page')
    {
        $pager = new self::$config['pager_class'];
        if (is_callable($page)) {
            $pager->setCallback($page);
        } else {
            $pager->setVar($page);
        }
        return $pager;
    }

    /**
     * @param $title
     */
    public static function setTitle($title)
    {
        self::app()->SetTitle($title);
    }

    /**
     * @param $class
     * @return bool|string
     */
    public static function getClassFile($class)
    {
        $class = ltrim($class, '\\');
        if (preg_match('{^TAO\\\\CachedInfoblock\\\\([^\\\\]+)$}', $class, $m)) {
            $name = self::unchunkCap($m[1]);
            $path = self::localDir("cache/infoblock/{$name}.php");
            if (!is_file($path)) {
                $id = self::getInfoblockId($name);
                if (!$id) {
                    print "Infoblock {$name} not found!";
                    die;
                }
                $content = \TAO\InfoblockExport::run($id, true);
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($path, $content);
            }
            return $path;
        } elseif (preg_match('{^App\\\\Forms\\\\([^\\\\]+)$}', $class, $m)) {
            $name = $m[1];
            return \TAO\Form::formClassFile($name);
        } elseif (preg_match('{^TAO\\\\Bundle\\\\([^\\\\]+)\\\\(.+)$}', $class, $m)) {
            $bundle = $m[1];
            $name = str_replace('\\', '/', $m[2]);
            $path = self::taoDir("bundles/{$bundle}/lib/{$name}.php");
            return $path;
        } elseif (preg_match('{^App\\\\Bundle\\\\([^\\\\]+)\\\\(.+)$}', $class, $m)) {
            $bundle = $m[1];
            $name = str_replace('\\', '/', $m[2]);
            $path = self::localDir("bundles/{$bundle}/lib/{$name}.php");
            return $path;
        } elseif (preg_match('{^TAO\\\\([^\\\\]+)$}', $class, $m)) {
            $name = self::unchunkCap($m[1]);
            return self::taoDir("lib/{$name}.php");
        } elseif ($class == 'TAO\CLI') {
            return self::taoDir("lib/cli.php");
        }
        return false;
    }

    /**
     * @param $class
     */
    public static function autoload($class)
    {
        $file = self::getClassFile($class);
        if ($file && is_file($file)) {
            include_once($file);
        }
    }

    /**
     * @param $dirs
     * @param $file
     * @param bool|false $extra
     * @return bool|string
     */
    public static function filePath($dirs, $file, $extra = false)
    {
        if (preg_match('{^(.+)\.(css|js|phtml|less|scss)$}', $file, $m)) {
            $base = $m[1];
            $ext = $m[2];
            $site = SITE_ID;
            foreach ($dirs as $dir) {
                if ($extra) {
                    $path = "{$dir}/{$base}-{$extra}-{$site}.{$ext}";
                    if (is_file($path)) {
                        return $path;
                    }
                    $path = "{$dir}/{$base}-{$extra}.{$ext}";
                    if (is_file($path)) {
                        return $path;
                    }
                }
                $path = "{$dir}/{$base}-{$site}.{$ext}";
                if (is_file($path)) {
                    return $path;
                }
                $path = "{$dir}/{$base}.{$ext}";
                if (is_file($path)) {
                    return $path;
                }
            }
        }
        return false;
    }

    /**
     * @param $dirs
     * @param $file
     * @param bool|false $extra
     * @return bool|string
     */
    public static function fileUrl($dirs, $file, $extra = false)
    {
        $path = self::filePath($dirs, $file, $extra);
        if ($path) {
            return substr($path, strlen($_SERVER['DOCUMENT_ROOT']));
        }
        return false;
    }

    /**
     * @param array $filter
     * @return array
     */
    public static function getLangs($filter = array())
    {
        $out = array();
        $result = CLanguage::GetList($by = 'SORT', $order = 'asc', $filter);
        while ($row = $result->Fetch()) {
            $id = $row['LID'];
            $out[$id] = $row;
        }
        return $out;
    }

    /**
     * @param $from
     * @param $to
     */
    public static function symlink($from, $to)
    {
        if (is_link($to)) {
            return;
        }
        if (is_dir($to)) {
            DeleteDirFilesEx($to);
        }
        if (is_file($to)) {
            unlink($to);
        }
        symlink($from, $to);
        chmod($to, 0777);
    }

    /**
     * @param bool|false $sub
     * @return string
     */
    public static function rootDir($sub = false)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'];
        if ($sub) {
            $sub = trim($sub, '/');
            $dir .= "/{$sub}";
        }
        return $dir;
    }

    /**
     * @return string
     */
    public static function taoDir($sub = false)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/techart/bitrix.tao';
        if ($sub) {
            $sub = trim($sub, '/');
            $dir .= "/{$sub}";
        }
        return $dir;
    }

    /**
     * @return string
     */
    public static function localDir($sub = false)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/local';
        if ($sub) {
            $sub = trim($sub, '/');
            $dir .= "/{$sub}";
        }
        return $dir;
    }

    /**
     * @param $class
     */
    public static function load($class)
    {
        $path = self::taoDir() . "/lib/{$class}.php";
        include_once($path);
    }

    /**
     *
     */
    public static function initAdmin()
    {
        AddEventHandler("main", "OnBuildGlobalMenu", function (&$admin, &$module) {
            global $USER;
            if (!$USER->IsAdmin()) {
                return;
            }
            if (\TAO::$config['admin_menu_export']) {
                $module[] = array(
                    'parent_menu' => 'global_menu_settings',
                    'section' => 'TAO',
                    'sort' => 5000,
                    'url' => '/bitrix/admin/tao.php',
                    'text' => 'TAO',
                    "icon" => "iblock_menu_icon",
                    "page_icon" => "iblock_page_icon",
                    'items_id' => 'tao',
                );
            }
        });
    }

    /**
     * @param $name
     * @param $value
     */
    public static function setOption($name, $value = true)
    {
        self::$config[$name] = $value;
    }

    /**
     * @param $name
     * @return null
     */
    public static function getOption($name)
    {
        return isset(self::$config[$name]) ? self::$config[$name] : null;
    }

    public static function getOptions()
    {
        return self::$config;
    }

    /**
     * @param array $cfg
     */
    public static function init($cfg = array())
    {
        foreach ($cfg as $k => $v) {
            self::$config[$k] = $v;
        }

        if (isset($GLOBALS['TAO_INITED'])) {
            return;
        }
        $GLOBALS['TAO_INITED'] = true;

        self::initAdmin();

        if (self::$config['fs_pages']) {
            self::addBundle('FSPages');
        }

        if (self::$config['elements']) {
            self::addBundle('Elements');
        }

        \TAO\Auth::init();

        self::$assets = new \TAO\Assets(\TAO\Environment::getInstance()->getName());

        AddEventHandler("main", "OnBeforeProlog", function () {
        });

    }

    public static function CLI()
    {
        \TAO\CLI::run();
    }

    /**
     * @param $code
     * @return \TAO\Infoblock
     * @throws TAONoInfoblockFileException
     */
    public static function getInfoblock($code)
    {
        if (is_numeric($code)) {
            $code = self::getInfoblockCode($code);
        }

        if (!isset(self::$infoblocks[$code])) {
            $name = \TAO\Infoblock::getClassName($code);
            $e = new $name($code);
            self::$infoblocks[$code] = $e;
            foreach ($e->urls() as $mode => $data) {
                if (isset($data['default']) && isset($data['page'])) {
                    $url = $data['default'];
                    $re = '{^' . str_replace('{id}', '(?<id>\d+)', $url) . '$}';
                    \TAO\Urls::addDefaultUrl($re, array(
                        'infoblock' => $code,
                        'mode' => $mode,
                        'default_url' => $url,
                        'page' => $data['page'],
                    ));
                }
            }
        }
        return self::$infoblocks[$code];
    }

    /**
     * @param $code
     * @return mixed
     */
    public static function infoblock($code)
    {
        return self::getInfoblock($code);
    }

    /**
     * @param $code
     * @return bool
     */
    public static function getInfoblockId($code)
    {
        return \TAO\Infoblock::codeToId($code);
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function getInfoblockCode($id)
    {
        if (!isset(self::$i2code[$id])) {
            $o = new \CIBlock();
            $res = $o->GetList(array(), array('ID' => $id, 'CHECK_PERMISSIONS' => false));
            $code = false;
            while ($row = $res->Fetch()) {
                $code = $row['CODE'];
            }
            if ($code) {
                self::$i2code[$id] = $code;
            }
        }
        return self::$i2code[$id];
    }

    /**
     * @param $code
     * @param $class
     */
    public static function setEntityClass($code, $class)
    {
        \TAO\Infoblock::setEntityClass($code, $class);
    }

    /**
     * @param $name
     * @return string
     */
    public static function chunkCap($name)
    {
        $s = '';
        foreach (explode('_', $name) as $chunk) {
            $s .= ucfirst(strtolower(trim($chunk)));
        }
        return $s;
    }

    /**
     * @param $name
     * @return string
     */
    public static function unchunkCap($name)
    {
        $name = preg_replace('{([A-Z]+)}', '_\\1', $name);
        $name = trim($name, '_');
        $name = strtolower($name);
        return $name;
    }

    /**
     * @return string
     */
    public static function schemaDir()
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/local/schema';
    }

    /**
     * @param $style
     * @return string
     * @throws Exception
     */
    public static function styleUrl($style)
    {
        if (strpos($style, '/') === false) {
            $style = self::filePath(
                array(
                    self::localDir('styles'),
                    self::taoDir('styles'),
                ),
                $style
            );
        }
        if (preg_match('{\.less$}', $style)) {
            $path = self::rootDir($style);
            if (is_file($path)) {
                $options = array(
                    'cache_dir' => self::localDir(self::getOption('less_cache')),
                );
                $css = \Less_Cache::Get(array($path => ''), $options);
                if ($css) {
                    return '/local/' . self::getOption('less_cache') . '/' . $css;
                }
            }
        }
        return $style;
    }

    /**
     * @param $style
     */
    public static function useStyle($style)
    {
        $style = self::styleUrl($style);
        if ($style) {
            return self::app()->SetAdditionalCSS($style);
        }
    }

    /**
     *
     */
    public static function useStyles()
    {
        foreach (func_get_args() as $style) {
            self::useStyle($style);
        }
    }

    /**
     * @param $script
     */
    public static function useScript($script)
    {
        return self::app()->AddHeadScript($script);
    }

    /**
     *
     */
    public static function useScripts()
    {
        foreach (func_get_args() as $script) {
            self::useScript($script);
        }
    }

    /**
     * @param $s
     * @return mixed|string
     */
    public static function translit($s, $space = '-')
    {
        $s = preg_replace('{&[a-z0-9#]+;}i', ' ', $s);
        $replace = array("А" => "A", "а" => "a", "Б" => "B", "б" => "b", "В" => "V", "в" => "v", "Г" => "G", "г" => "g", "Д" => "D", "д" => "d",
            "Е" => "E", "е" => "e", "Ё" => "E", "ё" => "e", "Ж" => "Zh", "ж" => "zh", "З" => "Z", "з" => "z", "И" => "I", "и" => "i",
            "Й" => "I", "й" => "i", "К" => "K", "к" => "k", "Л" => "L", "л" => "l", "М" => "M", "м" => "m", "Н" => "N", "н" => "n", "О" => "O", "о" => "o",
            "П" => "P", "п" => "p", "Р" => "R", "р" => "r", "С" => "S", "с" => "s", "Т" => "T", "т" => "t", "У" => "U", "у" => "u", "Ф" => "F", "ф" => "f",
            "Х" => "Kh", "х" => "kh", "Ц" => "Tc", "ц" => "tc", "Ч" => "Ch", "ч" => "ch", "Ш" => "Sh", "ш" => "sh", "Щ" => "Shch", "щ" => "shch",
            "Ы" => "Y", "ы" => "y", "Э" => "E", "э" => "e", "Ю" => "Iu", "ю" => "iu", "Я" => "Ia", "я" => "ia", "ъ" => "", "ь" => "");
        $s = strtr($s, $replace);
        $s = trim($s);
        $s = preg_replace('{[^a-z0-9]+}i', $space, $s);
        $s = trim($s, $space);
        return $s;
    }

    /**
     * @param $m
     */
    public static function sort(&$m)
    {
        uasort($m, function ($m1, $m2) {
            $sort1 = (is_array($m1) && isset($m1['SORT'])) ? (int)$m1['SORT'] : 500;
            $sort2 = (is_array($m2) && isset($m2['SORT'])) ? (int)$m2['SORT'] : 500;
            if ($sort1 < $sort2) {
                return -1;
            }
            if ($sort1 > $sort2) {
                return 1;
            }
            return 0;
        });
    }

    /**
     * @param $name
     * @return mixed
     */
    public function form($name, $check = true)
    {
        return \TAO\Form::formObject($name, $check);
    }

    /**
     * @return array|string
     */
    public function processForm()
    {
        return \TAO\Form::processPost();
    }

    /**
     * @param $args
     * @param $extra
     * @return mixed
     */
    public static function mergeArgs($args, $extra)
    {
        foreach ($extra as $k => $v) {
            if (!isset($args[$k])) {
                $args[$k] = $v;
            } else {
                if (is_array($args[$k]) && is_array($v)) {
                    $args[$k] = self::mergeArgs($args[$k], $v);
                } else {
                    $args[$k] = $v;
                }
            }
        }
        return $args;
    }

    /**
     * @param $date
     * @return int
     */
    public static function timestamp($date)
    {
        if ($m = \ParseDateTime($date)) {
            return mktime($m['HH'], $m['MI'], $m['SS'], $m['MM'], $m['DD'], $m['YYYY']);
        }
    }

    /**
     * @param $date
     * @param bool|false $format
     * @return bool|int|string
     */
    public static function date($date, $format = false)
    {
        $t = self::timestamp($date);
        return $format ? date($format, $t) : $t;
    }

    /**
     * @param $name
     * @param bool|false $additional
     */
    public static function frontendCss($name, $additional = false)
    {
        self::$assets->css($name, $additional);
    }

    /**
     * @param $name
     * @param bool|false $additional
     */
    public static function frontendJs($name, $additional = false)
    {
        self::$assets->js($name, $additional);
    }

    /**
     * @param $path
     * @return string
     */
    public static function frontendUrl($path)
    {
        return self::$assets->url($path);
    }

    /**
     * @param $var
     * @return bool
     */
    public static function isIterable(&$var)
    {
        return is_array($var) || $var instanceof Iterable || $var instanceof IteratorAggregate;
    }

}


/**
 * Class TAOException
 */
class TAOException extends Exception
{
}

/**
 * Class TAONoTypeFileException
 */
class TAONoTypeFileException extends TAOException
{
}

/**
 * Class TAONoInfoblockFileException
 */
class TAONoInfoblockFileException extends TAOException
{
}

/**
 * Class TAOAddTypeException
 */
class TAOAddTypeException extends TAOException
{
}

/**
 * Class TAOUpdateTypeException
 */
class TAOUpdateTypeException extends TAOException
{
}

/**
 * Class TAOBundleNotFoundException
 */
class TAOBundleNotFoundException extends TAOException
{
}
