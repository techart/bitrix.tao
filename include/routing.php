<?php
$content = \TAO\Bundle::routeBundles();
if (is_string($content)) {
	if (is_string(\TAO::$layout)) {
		if (\TAO::$layout == 'admin') {
			$prolog = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/prolog_admin.php';
			$epilog = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin.php';
		} else {
			$prolog = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/prolog_after.php';
			$epilog = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . "/footer.php";
		}

		require($prolog);

		if (\TAO::$compositeContent) {
			$frame = \TAO::compositeFrame(\TAO::$compositeContent);
			$stub = trim(\TAO::$compositeStub);
			$stub = strlen($stub) > 0 ? $stub : \TAO::t('composite_loading');
			$frame->begin($stub);
		}

		echo $content;

		if (\TAO::$compositeContent) {
			$frame->end();
		}

		require($epilog);
		die;
	}
	die($content);
} else {
	unset($content);
}
