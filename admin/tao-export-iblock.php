<?php include($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/techart/bitrix.tao/include/prolog_admin.php'); ?>
<?php
$id = $_GET['id'];
$data = CIBlock::GetByID($id)->Fetch();
if ($data) {
	$code = $data['CODE'];
	$name = $data['NAME'];
	?>
	<h2>Экспорт схемы инфоблока "<?= $name ?>"</h2>
	<?php

	\TAO::load('infoblock_export');
	$content = \TAO\InfoblockExport::run($id);

}
?>


<textarea wrap="off"
		  style="width:90%; height: 400px; padding: 10px; border: 1px solid #888; background-color: white; font-family: monospace; font-size: 10px;"><?= str_replace('<', '&lt;', $content) ?></textarea>


<?php include($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/techart/bitrix.tao/include/epilog_admin.php'); ?>
