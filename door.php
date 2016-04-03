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

function login()
{
	global $atc, $lang;
	empty($_POST['name']) && $atc->message($lang['error'], $lang['empty_login'], ATCMS_WEB_PATH . '/index.php', $lang['go_index']);
	empty($_POST['password']) && $atc->message($lang['error'], $lang['empty_password'], ATCMS_WEB_PATH . '/index.php', $lang['go_index']);
	
	$name = $atc->db->db_escape($_POST['name']);
	$password = md5($_POST['password']);
	
	$sql = 'SELECT id_user FROM ' . USERS_TABLE . ' WHERE name=\'' . $name . '\' AND password=\'' . $password . '\'';
	$c = $atc->db->db_query($sql);
	if($ok = $atc->db->db_numrows($c) == 1)
	{
		$id_user = $atc->db->db_result($c, 0, 'id_user');
		$atc->session->session_start($id_user);
		$atc->session->session_continue($id_user);
	}
	$atc->db->db_freeresult($c);
	
	$ok || $atc->message($lang['error'], $lang['incorrect_login_or_password'], ATCMS_WEB_PATH . '/index.php', $lang['go_index']);
	
	if(isset($_POST['autologin']) && $_POST['autologin'] == 1)
	{
		$atc->session->set_autologin_cookie($id_user);
	}
	else
	{
		$atc->session->disable_autologin();
	}
	
	header('Location: ' . ATCMS_WEB_PATH . '/index.php');
}

function logout()
{
	global $atc;
	$atc->session->session_close();
	$atc->session->disable_autologin(); //Если этого не сделать, то следующая строчка приведёт к логину :)
	header('Location: index.php');
}

$act = empty($_REQUEST['act']) ? '' : $_REQUEST['act'];

switch($act)
{
	case 'login': login(); break;
	case 'logout': logout(); break;
}

$atc->finalization();
?>