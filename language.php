<?php

/**
* AT CMS version 2.0.0
* Система управления содержимым веб-сайта
* © 2007–2008 группа разработчиков AT CMS
* ----------------------------------------------------------------
* Главный разработчик: Илья “WatchRooster” Аверков
* Интерпретатор BBML от Алексея “shade” Золотова
* Многие идеи заимствованы из проекта ArigatoCMS,
* разработанного Алексеем “Arigato” Акатовым в 2006 г.
* Выражаю также благодарность следующим лицам:
* MayKoPSKiy, Erik, Вася Триллер
* ----------------------------------------------------------------
* http://shamangrad.net/project.php?act=view&prj=atcms2
* ----------------------------------------------------------------
* Лицензировано на условиях GNU GPL v 2.0,
* за более подробной информацией смотрите COPYING.
* ----------------------------------------------------------------
*
*/

define('ATCMS', 'yes');
require './includes/atcms.inc.php';

$atc = new atcmain(0);

if(isset($_POST['language']) && $atc->id_language_installed( (int)$_POST['language'] ))
{
	setcookie(ATCSESSION_LANGUAGE_COOKIE_NAME, (int)$_POST['language'], 0x7FFFFFFF);
	/// Erik просил убрать...
	//$atc->general_message(ATCMESSAGE_LANGUAGE_SET);
	header('Location: ' . ATCMS_WEB_PATH . '/index.php');
}
else
{
	$atc->general_message(ATCMESSAGE_GENERAL_ERROR);
}

$atc->finalization();
?>
