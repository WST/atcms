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

if($atc->session->ok || $atc->cfgvar('guests_can_view_users_list'))
{
	$users_per_page = $atc->cfgvar('users_per_page');
	$users_list = $atc->template('users_list');
	$user = $atc->template('user');

	$p = isset($_GET['p']) && preg_match(PCREGEXP_INTEGER, $_GET['p']) ? $_GET['p'] : 0;
	$start = $p * $users_per_page;
	$cnt = $atc->db->db_countrows(USERS_TABLE);

	$cnt > $start || $atc->message($lang['error'], $lang['page_does_not_exists'], ATCMS_WEB_PATH . '/index.php', $lang['go_index']);

	$pgbar = $atc->pagebar($cnt, $users_per_page, $p, ATCMS_WEB_PATH . '/users.php', '');

	$sql = 'SELECT id_user, name, level, email, regtime FROM ' . USERS_TABLE . ' ORDER BY regtime ASC ' . $atc->db->db_limit($start, $users_per_page);

	for($u = $atc->db->db_query($sql); $u_res = $atc->db->db_fetchassoc($u); true)
	{
		$level = $lang['user'];
		if($u_res['level'] == '1') $level = $lang['admin'];
		$user->add_tag('NAME', htmlspecialchars($u_res['name']));
		$user->add_tag('LEVEL', $level);
		$user->add_tag('ID_USER', $u_res['id_user']);
		$user->add_tag('REGTIME', $atc->date($atc->cfgvar('date_format'), $u_res['regtime']));
		$users_list->ext_tag('USERS_LIST', $user->ret());
	}

	$atc->db->db_freeresult($u);

	$users_list->add_tag('PGBAR', $pgbar);

	$atc->process_contents($users_list->ret(), $lang['users_list']);
}
else
{
	$atc->general_message(ATCMESSAGE_ACCESS_USER);
}
$atc->finalization();
?>