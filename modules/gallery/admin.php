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
require '../../includes/atcms.inc.php';

$atc = new atcmain(0);
$atc->session->admin || $atc->general_message(ATCMESSAGE_ACCESS_ADMIN);

function gallery_admin_index()
{
	header('Location: ' . ATCMS_WEB_PATH . '/modules/gallery/admin.php?act=settings');
}

function gallery_admin_settings(& $atc, $error = '')
{
	$settings = $atc->template(GALLERY_LAYOUT . '/settings.htt', true);
	
	$data = array
	(
		'thumbnails_per_row'=>$atc->cfgvar('gallery:thumbnails_per_row'),
		'thumbnail_width'=>$atc->cfgvar('gallery:thumbnail_width'),
		'thumbnail_height'=>$atc->cfgvar('gallery:thumbnail_height'),
		'interpolation'=>$atc->cfgvar('gallery:interpolation'),
		'enable_lightbox'=>$atc->cfgvar('gallery:enable_lightbox'),
		'display_random_photo'=>$atc->cfgvar('gallery:display_random_photo'),
		'act'=>'settings_save'
	);
	
	$form = $atc->forms->create(GALLERY_FORMS . '/settings.ini', true, $atc->modules['gallery']->lang, $data, ATCMS_WEB_PATH . '/modules/gallery/admin.php', 'POST', $error);
	$settings->add_tag('FORM', $form->ret());
	$atc->process_contents($settings->ret(), $atc->modules['news']->lang['settings']);
}

function gallery_admin_settings_save(& $atc)
{
	if(!$atc->forms->validate())
	{
		return gallery_admin_settings($atc, $atc->lang['oops']);
	}
	$set = array();
	$checkboxes = array('interpolation', 'enable_lightbox', 'display_random_photo');
	foreach($checkboxes as $k=>$v)
		$set['gallery:' .$v] =
			(isset($_POST[$v]) && $_POST[$v] == 1) ? 1 : 0;
	
	$params = array
	(
		'thumbnails_per_row',
		'thumbnail_width',
		'thumbnail_height'
	);
	
	foreach($params as $k=>$v)
	{
		if(isset($_POST[$v]))
			$set['gallery:' .$v] = & $_POST[$v];
	}
	
	if(!isset($set['gallery:thumbnails_per_row']) || !preg_match(PCREGEXP_INTEGER_NOZERO, $set['gallery:thumbnails_per_row']))
		$set['gallery:thumbnails_per_row'] = $atc->cfgvar('gallery:thumbnails_per_row');
	if(!isset($set['gallery:thumbnail_width']) || !preg_match(PCREGEXP_INTEGER_NOZERO, $set['gallery:thumbnail_width']))
		$set['gallery:thumbnail_width'] = $atc->cfgvar('gallery:thumbnail_width');
	if(!isset($set['gallery:thumbnail_height']) || !preg_match(PCREGEXP_INTEGER_NOZERO, $set['gallery:thumbnail_height']))
		$set['gallery:thumbnail_height'] = $atc->cfgvar('gallery:thumbnail_height');
	
	foreach($set as $k=>$v)
	{
		$v = $atc->db->db_escape($v);
		$sql = 'UPDATE ' . CONFIGURATION_TABLE . ' SET param_value=\'' . $v . '\' WHERE param_name=\'' . $k . '\'' ;
		$atc->db->db_query($sql);
	}
	
	$sql = 'UPDATE ' . GALLERY_TABLE . ' SET cached=0';
	$atc->db->db_query($sql);
	
	$atc->log_message(ATCEVENT_GENERAL, 'Gallery settings were changed');
	$atc->message($atc->lang['message'], $atc->lang['settings_saved'], ATCMS_WEB_PATH . '/admin.php', $atc->lang['return_to_acp']);
}

switch(@$_REQUEST['act'])
{
	default: gallery_admin_index(); break;
	case 'settings': gallery_admin_settings($atc); break;
	case 'settings_save': gallery_admin_settings_save($atc); break;
}

$atc->finalization();
?>