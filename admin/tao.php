<?php include($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/techart/bitrix.tao/include/prolog_admin.php'); ?>

<h2>Экспорт текущей схемы инфоблоков</h2>
<ul>
	<?php
	$result = CIBlockType::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N'));
	while ($row = $result->Fetch()) {
		$r = CIBlockType::GetByIDLang($row['ID'], 'ru');
		$name = $r['NAME'];
		$type = $row['ID'];
		?>
		<li><?= $name ?></li>
		<ul>
		<?php
		$iresult = CIBlock::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N', 'TYPE' => $type));
		while ($irow = $iresult->Fetch()) {
			?>
			<li><a href="tao-export-iblock.php?id=<?= $irow['ID'] ?>"><?= $irow['NAME'] ?></a></li><?php
		}
		?>
		</ul><?php
	}
	?>
</ul>

<?php


?>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/local/vendor/techart/bitrix.tao/include/epilog_admin.php'); ?>

