<?php include($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/techart/bitrix.tao/include/prolog_admin.php'); ?>
<?php
$code = htmlspecialcharsbx($_GET['id']);
$langs = \TAO::getLangs();
$data = CIBlockType::GetByID($code)->Fetch();
$langData = array();
$name = false;
foreach ($langs as $lang => $ldata) {
	if ($r = CIBlockType::GetByIDLang($code, $lang, false)) {
		$langData[$lang] = $r;
		if (!$name) {
			$name = $r['NAME'];
		}
	}
}

?>

<h2>Экспорт типа "<?= $name ?>"</h2>
Мнемокод: <b><?= $code ?></b>, файл: <b>local/schema/types/<?= $code ?>.php</b><br>

<?php

$className = \TAO::chunkCap($code);

ob_start();
include(\TAO::taoDir() . '/views/template-type.phtml');
$content = "<?php\n" . ob_get_clean();
?>
<textarea wrap="off"
		  style="width:90%; height: 400px; padding: 10px; border: 1px solid #888; background-color: white; font-family: monospace; font-size: 10px;"><?= str_replace('<', '&lt;', $content) ?></textarea>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/techart/bitrix.tao/include/epilog_admin.php'); ?>

