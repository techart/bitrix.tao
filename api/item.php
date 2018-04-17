<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$code = trim($_GET['infoblock']);
$mode = isset($_GET['mode']) ? trim($_GET['mode']) : 'full';
$id = (int)$_GET['id'];
$infoblock = TAO::getInfoblock($code);

$item = $infoblock->loadItem($id);

if ($item) {
	$item->preparePage($mode);
	print $item->render($mode);
} else {
	LocalRedirect('/404.php');
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
