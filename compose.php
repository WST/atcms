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

function show_composing_form($error='')
{
	global $atc, $lang;
	
	$request = array_merge($_GET, $_POST);
	
	isset($request['id_user']) && preg_match(PCREGEXP_INTEGER, $request['id_user'])
		|| $atc->general_message(ATCMESSAGE_USER_DOESNT_EXIST);
	
	$sql = 'SELECT name, email, icq, jabber FROM ' . USERS_TABLE . ' WHERE id_user=' . $request['id_user'];
	$u = $atc->db->db_query($sql);
	if($atc->db->db_numrows($u) == 0)
	{
		$atc->db->db_freeresult($u);
		$atc->general_message(ATCMESSAGE_USER_DOESNT_EXIST);
	}
	$u_res = $atc->db->db_fetchassoc($u);
	$atc->db->db_freeresult($u);
	
	$request['type'] = isset($request['type']) ? $request['type'] : 'email';
	$types = '<option value="email"'. ($request['type']=='email' ? ' selected':'') .'>E-Mail</option>';
	
	if(!empty($u_res['jabber']))
		$types .= '<option value="xmpp"' . ($request['type']=='xmpp' ? ' selected':'') . '>Jabber/XMPP</option>';
	if(!empty($u_res['icq']))
		$types .= '<option value="icq"' . ($request['type']=='icq' ? ' selected':'') . '>ICQ</option>';
	
	$data = array
	(
		'subject'=>htmlspecialchars(isset($request['subject']) ? $request['subject'] : ''),
		'message'=>htmlspecialchars(isset($request['message']) ? $request['message'] : ''),
		'recipient'=>htmlspecialchars($u_res['name']),
		'id_user'=>$request['id_user'],
		'action'=>'send',
		'type'=>$types
	);
	
	$composing_form = $atc->template('message_composing_form');
	$form = $atc->forms->create('compose', false, $atc->lang, $data, ATCMS_WEB_PATH . '/compose.php', 'POST', $error);
	$composing_form->add_tag('FORM', $form->ret());
	$atc->process_contents($composing_form->ret(), $lang['message_composing']);
}

function send_message()
{
	global $atc, $lang;
	
	isset($_POST['id_user']) && preg_match(PCREGEXP_INTEGER, $_POST['id_user']) || $atc->general_message(ATCMESSAGE_USER_DOESNT_EXIST);
	
	if(!$atc->forms->validate())
	{
		return show_composing_form($lang['oops']);
	}
	
	if(!$atc->session->ok && !$atc->session->validate_captcha())
	{
		return show_composing_form($lang['wrong_captcha']);
	}
	
	if(!isset($_POST['subject']) || trim($_POST['subject']) == '')
	{
		return show_composing_form($lang['empty_subject']);
	}
	if(!isset($_POST['message']) || trim($_POST['message']) == '')
	{
		return show_composing_form($lang['empty_message']);
	}
	
	$sql = 'SELECT email, icq, jabber, layout FROM ' . USERS_TABLE . ' WHERE id_user=' . $_POST['id_user'];
	$u = $atc->db->db_query($sql);
	if($atc->db->db_numrows($u) == 0)
	{
		$atc->db->db_freeresult($u);
		$atc->general_message(ATCMESSAGE_USER_DOESNT_EXIST);
	}
	$u_res = $atc->db->db_fetchassoc($u);
	$atc->db->db_freeresult($u);
	
	$_POST['type'] = isset($_POST['type']) ? $_POST['type'] : 'email';
	switch($_POST['type'])
	{
		default: //email
			require ATCMS_INCLUDES_PATH . '/email.inc.php';
			$mailer = new email($atc);
			
			$_POST['message'] = $atc->preprocess_text($_POST['message'], ATCFORMAT_BBCODE);
			$_POST['message'] = $atc->process_text($_POST['message'], ATCFORMAT_BBCODE, false);
			
			$mailer->add_message($u_res['email'], htmlspecialchars($_POST['subject']), $atc->process_text($_POST['message']), 'text/html');
			$mailer->send();
		break;
		case 'icq':
			$uin = $atc->cfgvar('system_icq_uin');
			$password = $atc->cfgvar('system_icq_password');
			
			if(!empty($uin) && !empty($password))
			{
				if(!function_exists('socket_create'))
				{
					$atc->log_message(ATCEVENT_ERROR, 'Cannot use PHP’s socket functions');
					return show_composing_form($lang['no_socket_functions']);
				}
				
				require ATCMS_INCLUDES_PATH . '/icq.inc.php';
				$icq = new WebIcqLite();
				if(!$icq->connect($uin, $password))
				{
					$atc->log_message(ATCEVENT_ERROR, 'ICQ connection has failed');
					return show_composing_form($lang['icq_connection_failed']);
				}
				/// TODO: разбивать длинные сообщения на несколько
				$_POST['message'] = @iconv('UTF-8', $lang['ICQ_CHARSET'], $_POST['message']);
				if(!$icq->send_message($u_res['icq'], $_POST['message']))
				{
					return show_composing_form($lang['icq_sending_failed']);
				}
				$icq->disconnect();
			}
			else
			{
				$atc->log_message(ATCEVENT_ERROR, 'ICQ messaging is not properly configured');
				return show_composing_form($lang['icq_is_not_configured']);
			}
		break;
		case 'xmpp':
			$jid = $atc->cfgvar('system_jid');
			$password = $atc->cfgvar('system_jid_password');
			if(!empty($jid) && !empty($password))
			{
				if(!function_exists('socket_create'))
				{
					$atc->log_message(ATCEVENT_ERROR, 'Cannot use PHP’s socket functions');
					return show_composing_form($lang['no_socket_functions']);
				}
				
				$jid = explode('@', $jid);
				require ATCMS_INCLUDES_PATH . '/xmpphp/XMPP.php';
				$jab = new XMPPHP_XMPP($jid[1], 5222, $jid[0], $password, 'xmpphp', $jid[1], False, XMPPHP_Log::LEVEL_INFO);
				try
				{
					$jab->connect();				
					$jab->processUntil('session_start');
					$jab->message($u_res['jabber'], $_POST['message'], null, $_POST['subject']);
					$jab->disconnect();
				}
				catch(XMPPHP_Exception $e)
				{
					$atc->log_message(ATCEVENT_ERROR, 'Jabber class cannot send messages!');
					return show_composing_form($lang['jabber_sending_failed'] . ': ' . $e->getMessage());
				}
			}
			else
			{
				$atc->log_message(ATCEVENT_ERROR, 'Jabber messaging is not properly configured');
				return show_composing_form($lang['jabber_is_not_configured']);
			}
		break;
	}
	
	$atc->message($lang['message'], $lang['message_sent'], ATCMS_WEB_PATH . '/index.php', $lang['go_index']);
}


$act = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch($act)
{
	default: show_composing_form(); break;
	case 'send': send_message(); break;
}

$atc->finalization();
?>
