<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/bx_root.php");

if (file_exists($_SERVER["DOCUMENT_ROOT"] . BX_PERSONAL_ROOT . "/html_pages/.enabled")) {
	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/cache_html.php");
	CHTMLPagesCache::startCaching();
}

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
