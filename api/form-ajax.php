<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

$result = \TAO::processForm();

if (is_array($result)) {
	$form = $result['form'];
	unset($result['form']);
	unset($result['item']);
	unset($result['values']);

	$result['return_url'] = $form->option('return_url');
	$result['on_ok'] = $form->option('on_ok');
	$result['on_error'] = $form->option('on_error');
	$result['ok_message'] = $form->option('ok_message');

	print json_encode($result);
} elseif (is_string($result)) {
	print $result;
	die;
} else {
	var_dump($result);
	die;
}
