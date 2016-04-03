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
require ATCMS_INCLUDES_PATH . '/user_control.inc.php';

$atc = new atcmain(0);
$atc->session->ok || $atc->general_message(ATCMESSAGE_ACCESS_USER);

function profile_edit(& $atc, $error = '')
{
	//public static function profile_edit(& $atc, $id_user, $target, $act, $error = '')
	return user_control::profile_edit($atc, $atc->session->id_user, ATCMS_WEB_PATH . '/profile.php', 'profile_edit_save', $error);
}

function profile_edit_save(& $atc)
{
	//public static function profile_edit_save(& $atc, $id_user, $target, $act, $return_to)
	return user_control::profile_edit_save($atc, $atc->session->id_user, ATCMS_WEB_PATH . '/profile.php', 'profile_edit_save', ATCMS_WEB_PATH . '/index.php');
}

$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
switch($act)
{
	default: profile_edit($atc); break;
	case 'profile_edit_save': profile_edit_save($atc); break;
}

$atc->finalization();

?>