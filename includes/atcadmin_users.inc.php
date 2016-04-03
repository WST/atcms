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

defined('ATCMS') || die('Error');

class atcadmin_users
{
	private $atc = NULL;
	private $db = NULL;
	private $lang = NULL;
	
	/**
	* Конструктор
	*/
	
	public function __construct(& $atc)
	{
		$this->atc = & $atc;
		$this->db = & $atc->db;
		$this->lang = & $atc->lang;
		
		require ATCMS_INCLUDES_PATH . '/user_control.inc.php';
	}
	
	private function id_user_exists($id_user)
	{
		$sql = 'SELECT count(*) AS cnt FROM ' . USERS_TABLE . ' WHERE id_user=' . (int)$id_user;
		$u = $this->db->db_query($sql);
		$retval = (bool) $this->db->db_result($u, 0, 'cnt');
		$this->db->db_freeresult($u);
		
		return $retval;
	}
	
	private function validate_id_user($method = 'GET')
	{
		switch($method)
		{
			case 'GET':
				isset($_GET['id_user'])
					&& $this->id_user_exists($_GET['id_user'])
						|| $this->atc->message($this->lang['error'], $this->lang['user_does_not_exist'], ATCMS_WEB_PATH . '/admin.php?act=users', $this->lang['go_back']);
			break;
			case 'POST':
				isset($_POST['id_user'])
					&& $this->id_user_exists($_POST['id_user'])
						|| $this->atc->message($this->lang['error'], $this->lang['user_does_not_exist'], ATCMS_WEB_PATH . '/admin.php?act=users', $this->lang['go_back']);
			break;
		}
	}
	
	public function users()
	{
		$users_per_page = $this->atc->cfgvar('users_per_page');
		
		$p = isset($_GET['p']) && preg_match(PCREGEXP_INTEGER, $_GET['p']) ? $_GET['p'] : 0;
		$start = $p * $users_per_page;
		
		$sql = 'SELECT count(*) AS cnt FROM ' . USERS_TABLE;
		$c = $this->db->db_query($sql);
		$cnt = $this->db->db_result($c, 0, 'cnt');
		$this->db->db_freeresult($c);
		
		$cnt > $start || $this->atc->message($this->lang['error'], $this->lang['page_does_not_exists'], ATCMS_WEB_PATH . '/index.php', $this->lang['go_index']);
		
		$pgbar = $this->atc->pagebar($cnt, $users_per_page, $p, ATCMS_WEB_PATH . '/admin.php', 'act=users');
		
		$users = $this->atc->template('admin/users');
		$users->add_tag('PGBAR', $pgbar);
		
		$sql = 'SELECT id_user, name, level, email, regtime FROM ' . USERS_TABLE . ' ORDER BY regtime ASC ' . $this->db->db_limit($start, $users_per_page);
		for($u = $this->db->db_query($sql); $u_res = $this->db->db_fetchassoc($u); true)
		{
			$level = $this->lang['user'];
			if($u_res['level'] == '1') $level = $this->lang['admin'];
			$users->ext_tag('USERS_LIST', '<tr><td><a href="' . ATCMS_WEB_PATH . '/vcard.php?id_user=' . $u_res['id_user'] . '">' . htmlspecialchars($u_res['name']) . '</a></td><td>' . $level . '</td><td><a href="' . ATCMS_WEB_PATH . '/admin.php?act=user_delete&amp;id_user=' . $u_res['id_user'] . '&amp;' . $this->atc->forms->generate_confirmation() . '" title="' . $this->lang['delete'] . '">' . $this->lang['user_delete'] . '</a>&nbsp;|&nbsp;<a href="admin.php?act=user_edit&amp;id_user=' . $u_res['id_user'] . '">' . $this->lang['edit_user'] . '</a></td></tr>');
		}
		$this->db->db_freeresult($u);
		
		$this->atc->process_contents($users->ret(), $this->lang['users_management']);
	}
	
	public function user_delete()
	{
		if(!$this->atc->forms->validate())
		{
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['fatal_oops']);
		}
		
		$this->validate_id_user('GET');
		if($_GET['id_user'] == 1) $this->atc->message($this->lang['error'], $this->lang['user_is_founder'], ATCMS_WEB_PATH . '/admin.php?act=users', $this->lang['go_back']);
		
		$sql = 'DELETE FROM ' . USERS_TABLE . ' WHERE id_user=' . $_GET['id_user'];
		$this->db->db_query($sql);
		
		$sql = 'DELETE FROM ' . AUTOLOGIN_TABLE . ' WHERE id_user=' . $_GET['id_user'];
		$this->db->db_query($sql);
		
		$this->atc->log_message(ATCEVENT_GENERAL, 'Deleted user');
		$this->atc->message($this->lang['message'], $this->lang['user_deleted'], ATCMS_WEB_PATH . '/admin.php?act=users', $this->lang['go_back']);
	}
	
	public function new_user($error = '')
	{
		return user_control::create_user($this->atc, ATCMS_WEB_PATH . '/admin.php', 'new_user_save', $error, true);
	}
	
	public function new_user_save()
	{
		return user_control::create_user_save($this->atc, ATCMS_WEB_PATH . '/admin.php', 'new_user_save', ATCMS_WEB_PATH . '/admin.php?act=users', true);
	}
	
	public function user_edit()
	{
		isset($_GET['id_user'])
			|| $this->atc->general_message(ATCMESSAGE_USER_DOESNT_EXIST);
		return user_control::profile_edit($this->atc, $_GET['id_user'], ATCMS_WEB_PATH . '/admin.php', 'user_edit_save', '', true);
	}
	
	public function user_edit_save()
	{
		isset($_POST['id_user'])
			|| $this->atc->general_message(ATCMESSAGE_USER_DOESNT_EXIST);
		return user_control::profile_edit_save($this->atc, $_POST['id_user'], ATCMS_WEB_PATH . '/admin.php', 'user_edit_save', ATCMS_WEB_PATH . '/admin.php?act=users', true);
	}
}

?>