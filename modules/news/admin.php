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

$atc = new atcmain(0, 'news', false);
$atc->session->admin || $atc->general_message(ATCMESSAGE_ACCESS_ADMIN);

function news_admin_index()
{
	header('Location: ' . ATCMS_WEB_PATH . '/modules/news/admin.php?act=settings');
}

function news_admin_comments_order($ord)
{
	global $atc;
	return '<option value="ASC"' . ( $ord == 'ASC' ? ' selected': '' ) . '>' . $atc->modules['news']->lang['order_asc'] . '</option>' . 
		'<option value="DESC"' . ( $ord == 'DESC' ? ' selected': '' ) . '>' . $atc->modules['news']->lang['order_desc'] . '</option>';
}

function news_admin_settings(& $atc, $error = '')
{
	$settings = $atc->modules['news']->template('settings');
	$data = array
	(
		'news_per_page'=>$atc->cfgvar('news:news_per_page'),
		'allow_comments'=>$atc->cfgvar('news:allow_comments'),
		'comments_per_page'=>$atc->cfgvar('news:comments_per_page'),
		'news_in_rss'=>$atc->cfgvar('news:news_in_rss'),
		'comments_order'=>news_admin_comments_order($atc->cfgvar('news:comments_order')),
		'act'=>'settings_save'
	);
	
	$form = $atc->forms->create(NEWS_FORMS . '/settings.ini', true, $atc->modules['news']->lang, $data, ATCMS_WEB_PATH . '/modules/news/admin.php', 'POST', $error);
	$settings->add_tag('FORM', $form->ret());
	$atc->process_contents($settings->ret(), $atc->modules['news']->lang['settings']);
}

function news_admin_settings_save(& $atc)
{
	isset($_POST['news_per_page'])
		&& preg_match(PCREGEXP_INTEGER, $_POST['news_per_page'])
			|| $_POST['news_per_page'] =
				$atc->modules['news']->cfgvar('news_per_page');
	
	isset($_POST['comments_per_page'])
		&& preg_match(PCREGEXP_INTEGER, $_POST['comments_per_page'])
			|| $_POST['comments_per_page'] =
				$atc->modules['news']->cfgvar('comments_per_page');
	
	isset($_POST['news_in_rss'])
		&& preg_match(PCREGEXP_INTEGER, $_POST['news_in_rss'])
			|| $_POST['news_in_rss'] =
				$atc->modules['news']->cfgvar('news_in_rss');
	
	$_POST['allow_comments'] =
		(isset($_POST['allow_comments']) && $_POST['allow_comments'] == 1) ?
			'1' : '0';
	
	$_POST['comments_order'] =
		(isset($_POST['comments_order']) && $_POST['comments_order'] == 'DESC') ?
			'DESC' : 'ASC';
	
	foreach(array('news_per_page', 'comments_per_page', 'news_in_rss', 'allow_comments', 'comments_order') as $k=>$v)
	{
		$sql = 'UPDATE ' . CONFIGURATION_TABLE . ' SET param_value=\'' . $_POST[$v] . '\' WHERE param_name=\'news:' . $v . '\'';
		$atc->db->db_query($sql);
	}
	
	$atc->message($atc->lang['message'], $atc->modules['news']->lang['settings_saved'], ATCMS_WEB_PATH . '/admin.php', $atc->lang['return_to_acp']);
}

switch(@$_REQUEST['act'])
{
	default: news_admin_index(); break;
	case 'settings': news_admin_settings($atc); break;
	case 'settings_save': news_admin_settings_save($atc); break;
}

$atc->finalization();
?>