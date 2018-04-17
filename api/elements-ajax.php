<?php
if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
	die('This page is for XMLHttpRequest only!');
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
$args = $_GET;
$infoblock = false;
if (isset($args['infoblock'])) {
	$code = htmlspecialcharsbx(trim($args['infoblock']));
	if (!\TAO::getOption("infoblock.{$code}.elements.ajax")) {
		die('Access denied!');
	}
	if (!empty($code)) {
		$infoblock = \TAO::infoblock($code);
	}
}

if (empty($infoblock)) {
	print "Infoblock {$code} not found!";
} else {
	$innerMode = 'ajax-inner';
	if (isset($args['list_mode_inner'])) {
		$innerMode = trim($args['list_mode_inner']);
	}
	$args['list_mode'] = $innerMode;
	print $infoblock->render($args);
}
