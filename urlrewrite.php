<?php
$uri = $_SERVER['REQUEST_URI'];

$p = strpos($uri, '?');
if ($p>0) {
    $uri = substr($uri, 0, $p);
}

if ($uri[strlen($uri)-1] == '/') {
    $uri .= 'index.php';
}

if (preg_match('{^/bitrix/admin/(tao.*\.php)$}', $uri, $m)) {
    $uri = '/local/vendor/techart/bitrix.tao/admin/'.$m[1];
}

$path = $_SERVER['DOCUMENT_ROOT']. $uri;
if (is_file($path)) {
    $_SERVER['SCRIPT_FILENAME'] = $path;
    $_SERVER['SCRIPT_NAME'] = $_SERVER['DOCUMENT_URI'] = $_SERVER['PHP_SELF'] = $uri;
    chdir(dirname($path));
    include($path);
    die;
}

if (isset($GLOBALS['tao_urlrewrited'])) {
    die;
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
$GLOBALS['tao_urlrewrited'] = true;

$uri = $_SERVER['REQUEST_URI'];
$script = $_SERVER['PHP_SELF'];
chdir($_SERVER['DOCUMENT_ROOT']);


\TAO\Urls::processVars();

$content = \TAO\Bundle::routeBundles();
if (is_string($content)) {
    if (is_string(\TAO::$layout)) {
        require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
        print $content;
        require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
        die;
    }
    print $content;
    die;
}

$nativeScript = '/bitrix/urlrewrite.php';
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . $nativeScript;
$_SERVER['SCRIPT_NAME'] = $_SERVER['DOCUMENT_URI'] = $_SERVER['PHP_SELF'] = $nativeScript;

chdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix');
include($_SERVER['SCRIPT_FILENAME']);