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
	isset($_GET['id_user']) && preg_match(PCREGEXP_INTEGER, $_GET['id_user'])
		|| $atc->general_message(ATCMESSAGE_USER_DOESNT_EXIST);

	$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE id_user= ' . $_GET['id_user'];
	$u = $atc->db->db_query($sql);
	$atc->db->db_numrows($u) != 0
		|| $atc->general_message(ATCMESSAGE_USER_DOESNT_EXIST);
	$u_res = $atc->db->db_fetchassoc($u);
	$atc->db->db_freeresult($u);

	$level = $u_res['level'] == '1' ? $lang['admin'] : $lang['user'];

	$hide_email = (bool) $u_res['hide_email'];

	$vcard = $atc->template('vcard');
	$vcard->add_tag('VCARD_USER', htmlspecialchars($u_res['name']));
	$vcard->add_tag('VCARD_ID_USER', $u_res['id_user']);
	$vcard->add_tag('VCARD_LEVEL', $level);
	$vcard->add_tag('VCARD_SIGNATURE', $atc->process_text($u_res['signature'], ATCFORMAT_BBCODE));
	$vcard->add_tag('VCARD_REGTIME', $atc->date($atc->cfgvar('date_format'), $u_res['regtime']));
	$vcard->add_tag('VCARD_ICQ', $u_res['icq']);
	$vcard->add_tag('VCARD_JID', $u_res['jabber']);
	$vcard->add_tag('VCARD_SITE', $u_res['site']);
	$vcard->add_tag('VCARD_AVATAR', $atc->user_avatar($u_res['avatar']));
	$vcard->add_tag('VCARD_EMAIL', ( $hide_email ? '' : $u_res['email'] ));
	$vcard->add_tag('VCARD_LOCATION', htmlspecialchars($u_res['location']));
	$vcard->add_tag('VCARD_OCCUPATION', htmlspecialchars($u_res['occupation']));
	$vcard->add_tag('VCARD_INTERESTS', htmlspecialchars($u_res['interests']));
	$vcard->add_tag('VCARD_PHONE', $u_res['phone']);

	$atc->process_contents($vcard->ret(), $lang['profile_view']);
}
else
{
	$atc->general_message(ATCMESSAGE_ACCESS_USER);
}
$atc->finalization();
?>
