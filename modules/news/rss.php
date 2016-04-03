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

header('Content-type: application/rss+xml;charset=utf-8');

$rss = $atc->modules['news']->template('rss_channel');
$rss->add_tag('ITEMS', '');
$rss->add_tag('CHANNEL_DESCRIPTION', 'Created by AT CMS RSS generator');
$rss->add_tag('CHANNEL_TITLE', htmlspecialchars($atc->cfgvar('site_title')));
$rss->add_tag('CHANNEL_LINK', 'http://' . $_SERVER['SERVER_NAME'] . ATCMS_WEB_PATH . '/index.php');
$rss->add_tag('CHANNEL_LANGUAGE', LANGUAGE);
$rss->add_tag('CHANNEL_LASTBUILDDATE', $atc->date('r', CURRENT_TIMESTAMP));

if(isset($_GET['id_element']))
{
	$sql = 'SELECT language, title, module FROM ' . STRUCTURE_TABLE . ' WHERE id_element=' . (int) $_GET['id_element'];
	$e = $atc->db->db_query($sql);
	$e_res = $atc->db->db_fetchassoc($e);
	$atc->db->db_freeresult($e);
	
	$datetime = '';
	
	if(is_array($e_res))
	{
		$rss->add_tag('CHANNEL_LINK', 'http://' . $_SERVER['SERVER_NAME'] . ATCMS_WEB_PATH . '/index.php?go=' . (int) $_GET['id_element']);
		$rss->add_tag('CHANNEL_TITLE', htmlspecialchars($e_res['title']));
		
		$item = $atc->modules['news']->template('rss_item');
		
		$sql = 'SELECT * FROM ' . NEWS_TABLE . ' WHERE id_element=' . $_GET['id_element'] . ' ORDER BY datetime DESC ' . $atc->db->db_limit(0, $atc->modules['news']->cfgvar('news_in_rss'));
		for($n = $atc->db->db_query($sql); $n_res = $atc->db->db_fetchassoc($n); true)
		{
			$item_dt = $atc->date('r', $n_res['datetime']);
			if(! (bool) $datetime) $datetime = $item_dt;
			$item->add_tag('ITEM_TITLE', htmlspecialchars($n_res['title']));
			$item->add_tag('ITEM_PUBDATE', $item_dt);
			$item->add_tag('ITEM_GUID', 'http://' . $_SERVER['SERVER_NAME'] . ATCMS_WEB_PATH . '/index.php?go=' . (int) $_GET['id_element'] . '&amp;act=show_message&amp;id_message=' . $n_res['id_message']);
			$item->add_tag('ITEM_DESCRIPTION', '<![CDATA[' . $atc->process_text($n_res['message'], $n_res['type'], true) . ']]>');
			
			$rss->ext_tag('ITEMS', $item->ret());
		}
		$atc->db->db_freeresult($n);
	}
	
	$rss->add_tag('CHANNEL_LASTBUILDDATE', $datetime);
}

$rss->out();

$atc->finalization();
?>