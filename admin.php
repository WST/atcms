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
require ATCMS_INCLUDES_PATH . '/atcadmin.inc.php';

$atc = new atcadmin;
$lang = & $atc->lang;

$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
switch($act)
{
	default:
		$atc->admin_index();
	break;
	
	case 'modules':
	case 'module_install':
	case 'module_delete':
		require ATCMS_INCLUDES_PATH . '/atcadmin_modules.inc.php';
		$m = new atcadmin_modules($atc);
		call_user_func(array($m, $act));
	break;
	
	case 'languages':
	case 'language_install':
	case 'language_delete':
		require ATCMS_INCLUDES_PATH . '/atcadmin_languages.inc.php';
		$l = new atcadmin_languages($atc);
		call_user_func(array($l, $act));
	break;
	
	case 'logs':
	case 'truncate_logs':
	require ATCMS_INCLUDES_PATH . '/atcadmin_logs.inc.php';
		$logs = new atcadmin_logs($atc);
		call_user_func(array($logs, $act));
	break;
	
	case 'tags':
	case 'new_tag':
	case 'new_tag_save':
	case 'tag_delete':
	case 'edit_tag':
	case 'edit_tag_save':
		require ATCMS_INCLUDES_PATH . '/atcadmin_tags.inc.php';
		$tags = new atcadmin_tags($atc);
		call_user_func(array($tags, $act));
	break;
	
	case 'structure':
	case 'structure_element_delete':
	case 'structure_element_new':
	case 'structure_element_new_save':
	case 'structure_element_edit':
	case 'structure_element_edit_save':
	case 'structure_element_delete_module':
	case 'structure_element_up':
	case 'structure_element_down':
		require ATCMS_INCLUDES_PATH . '/atcadmin_structure.inc.php';
		$struct = new atcadmin_structure($atc);
		call_user_func(array($struct, $act));
	break;

	case 'settings':
	case 'settings_save':
		require ATCMS_INCLUDES_PATH . '/atcadmin_settings.inc.php';
		$s = new atcadmin_settings($atc);
		call_user_func(array($s, $act));
	break;
	
	case 'users':
	case 'user_delete':
	case 'user_edit':
	case 'user_edit_save':
	case 'new_user':
	case 'new_user_save':
		require ATCMS_INCLUDES_PATH . '/atcadmin_users.inc.php';
		$usr = new atcadmin_users($atc);
		call_user_func(array($usr, $act));
	break;
	
	case 'service':
		require ATCMS_INCLUDES_PATH . '/atcadmin_service.inc.php';
		$s = new atcadmin_service($atc);
		call_user_func(array($s, $act));
	break;
}

$atc->finalization();
?>