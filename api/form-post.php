<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

$result = \TAO::processForm();

if (is_array($result)) {
	$form = $result['form'];
	if ($form->isValid()) {
		$url = $form->option('return_url');
		if (!$url) {
			$url = '/local/vendor/techart/bitrix.tao/api/form-ok.php';
		}
		\LocalRedirect($url);
	} else {
		require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
		$APPLICATION->SetTitle($form->option('error_title'));
		print $form->render();
		require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
	}
} elseif (is_string($r)) {
	print $r;
	die;
} else {
	var_dump($r);
	die;
}
