<?php
$uri = $_SERVER['REQUEST_URI'];
$clean_uri = $uri;

$p = strpos($uri, '?');
if ($p > 0) {
    $uri = substr($uri, 0, $p);
    $clean_uri = $uri;
}

if ($uri[strlen($uri) - 1] == '/') {
    $uri .= 'index.php';
}

if (preg_match('{^/bitrix/admin/(tao.*\.php)$}', $uri, $m)) {
    $uri = '/local/vendor/techart/bitrix.tao/admin/' . $m[1];
}

$_tao_path = $_SERVER['DOCUMENT_ROOT'] . $uri;
if (preg_match('{^/bitrix/admin/(.+)$}', $uri, $m)) {
    $_admin_path = $_SERVER['DOCUMENT_ROOT'] . '/local/admin/' . $m[1];
    if (is_file($_admin_path)) {
        $_tao_path = $_admin_path;
    }
}

if (is_file($_tao_path)) {
    $_SERVER['SCRIPT_FILENAME'] = $_tao_path;
    $_SERVER['SCRIPT_NAME'] = $_SERVER['DOCUMENT_URI'] = $_SERVER['PHP_SELF'] = $uri;
    chdir(dirname($_tao_path));
    unset($uri);
    unset($clean_uri);
    unset($p);
    unset($m);
    include($_tao_path);
    die;
}

if (isset($GLOBALS['tao_urlrewrited'])) {
    die;
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/techart/bitrix.tao/include/prolog_before.php');
$GLOBALS['tao_urlrewrited'] = true;

$uri = $_SERVER['REQUEST_URI'];
$script = $_SERVER['PHP_SELF'];
chdir($_SERVER['DOCUMENT_ROOT']);


\TAO\Urls::processVars();

$content = \TAO\Bundle::routeBundles();
if (is_string($content)) {
    if (is_string(\TAO::$layout)) {
        $prolog = $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_after.php";
        $epilog = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php";

        if (\TAO::$layout == 'admin') {
            $prolog = $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin.php";
            $epilog = $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog_admin.php";
        }

        require($prolog);

        if (\TAO::$compositeContent) {
            $frame = \TAO::compositeFrame(\TAO::$compositeContent);
            $stub = trim(\TAO::$compositeStub);
            $stub = strlen($stub) > 0 ? $stub : \TAO::t('composite_loading');
            $frame->begin($stub);
        }

        print $content;

        if (\TAO::$compositeContent) {
            $frame->end();
        }

        require($epilog);
        die;
    }
    print $content;
    die;
}

if ($clean_uri!='' && substr($clean_uri, count($clean_uri)-1)!='/') {
    $r = \TAO::getOption('redirect_if_no_slash');
    $r = is_null($r)? true : $r;
    if ($r) {
        $clean_uri .= '/';
        $q = trim($_SERVER['QUERY_STRING']);
        if ($q!='') {
            $clean_uri .= '?' . $q;
        }
        header("HTTP/1.1 301 Moved Permanently"); 
        header("Location: {$clean_uri}");
    }
    unset($r);
}


$nativeScript = '/bitrix/urlrewrite.php';
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . $nativeScript;
$_SERVER['SCRIPT_NAME'] = $_SERVER['DOCUMENT_URI'] = $_SERVER['PHP_SELF'] = $nativeScript;

chdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix');
unset($script);
unset($nativeScript);
unset($content);
unset($clean_uri);
unset($uri);
include($_SERVER['SCRIPT_FILENAME']);

