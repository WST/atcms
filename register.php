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
$lang = & $atc->lang;

if(!$atc->cfgvar('enable_registration'))
{
	$atc->message($lang['message'], $lang['registration_is_disabled'], ATCMS_WEB_PATH . '/index.php', $lang['go_index']);
}

require ATCMS_INCLUDES_PATH . '/user_control.inc.php';

function show_registration_form(& $atc, $error = '')
{
	return user_control::create_user($atc, ATCMS_WEB_PATH . '/register.php', 'registration_save', $error);
}

function registration_save(& $atc)
{
	return user_control::create_user_save($atc, ATCMS_WEB_PATH . '/register.php', 'registration_save', ATCMS_WEB_PATH . '/index.php');
}


$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

switch($act)
{
	default: show_registration_form($atc); break;
	case 'registration_save': registration_save($atc); break;
}

$atc->finalization();
?>