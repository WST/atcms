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

function modules_result($str, $method)
//Возвращает массив записей с результатами поиска
{
	global $atc;
	$retval = array();
	foreach($atc->modules as $k=>$v)
	{
		if(method_exists($v, 'search')) $retval[$k] = $v->search($str, $method);
	}
	return $retval;
}

function get_structure_element_info($id_article)
{
	global $atc;
	$retval = array();
	$sql = 'SELECT title, id_element FROM ' . STRUCTURE_TABLE . ' WHERE article=' . $id_article;
	$t = $atc->db->db_query($sql);
	$retval['title'] = $atc->db->db_result($t, 0, 'title');
	$retval['id_element'] = $atc->db->db_result($t, 0, 'id_element');
	$atc->db->db_freeresult($t);
	
	return $retval;
}

function articles_result($str, $method)
{
	global $atc, $lang;
	$re = atcms_regexp_prepare($str);
	switch($method)
	{
		case ATCSEARCH_OR:
			$re = explode(' ', $re);
			$re = '(' . implode('|', $re) . ')';
			$re = $atc->db->db_escape($re);
			$str = $atc->db->db_escape($str);
		break;
		case ATCSEARCH_AND:
			$re = explode(' ', $re);
			$re = implode('(.*)', $re);
			$re = $atc->db->db_escape($re);
			$str = $atc->db->db_escape($str);
		break;
	}
	
	$retval = array();
	$i = 0;
	// Верю, нехилый запрос. Вроде не тормозит, всё равно поиск с индексированием ещё будет в 2.1.0
	$sql = 'SELECT short_version, full_version, id_article, type FROM ' . ARTICLES_TABLE . ' WHERE id_article IN (SELECT article FROM ' . STRUCTURE_TABLE . ' WHERE language=' . $atc->language . ') AND (full_version REGEXP (\'' . $re . '\') OR short_version REGEXP (\'' . $re . '\'))';
	for($s=$atc->db->db_query($sql); $s_res=$atc->db->db_fetchassoc($s); ++$i)
	{
		$short = $atc->process_text($s_res['short_version'], $s_res['type']);
		$retval[$i]['description'] = $short == '' ? $lang['no_description'] : $short;
		/// @todo TODO: здесь ещё стоит побороться с накруткой SQL в рамках 2.0.0
		$structure_element = get_structure_element_info($s_res['id_article']);
		$retval[$i]['href'] = ATCMS_WEB_PATH . '/index.php?go=' . $structure_element['id_element'];
		$retval[$i]['title'] = htmlspecialchars($structure_element['title']);
	}
	$atc->db->db_freeresult($s);
	
	return $retval;
}

function format_result($str, $method)
{
	global $atc;
	
	$a = articles_result($str, $method);
	$m = modules_result($str, $method);
	
	$results = $atc->template('search_results');
	$module_results = $atc->template('module_search_results');
	$result = $atc->template('search_result');
	
	$results->add_tag('ARTICLES_SEARCH_RESULTS', '');
	$results->add_tag('MODULES_SEARCH_RESULTS', '');
	
	foreach($a as $r)
	{
		$result->add_tag('RESULT_DESCRIPTION', $r['description']);
		$result->add_tag('HREF', $r['href']);
		$result->add_tag('RESULT_TITLE', $r['title']);
		$results->ext_tag('ARTICLES_SEARCH_RESULTS', $result->ret());
	}
	
	foreach($m as $k=>$v)
	{
		$module_results->add_tag('MODULE_NAME', htmlspecialchars($k));
		$module_results->add_tag('SEARCH_RESULTS', '');
		foreach($v as $r)
		{
			$result->add_tag('RESULT_DESCRIPTION', $r['description']);
			$result->add_tag('HREF', $r['href']);
			$result->add_tag('RESULT_TITLE', $r['title']);
			$module_results->ext_tag('SEARCH_RESULTS', $result->ret());
		}
		$results->ext_tag('MODULES_SEARCH_RESULTS', $module_results->ret());
	}
	
	return $results->ret();
}

$method = isset($_POST['method']) && $_POST['method'] == 'OR' ? ATCSEARCH_OR : ATCSEARCH_AND;
if(!isset($_POST['find']) || $_POST['find'] == '') $atc->general_message(ATCMESSAGE_INCORRECT_SEARCH_QUERY);

$atc->process_contents(format_result($_POST['find'], $method), $lang['search_results']);
$atc->finalization();
?>