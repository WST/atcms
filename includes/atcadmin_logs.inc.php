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

class atcadmin_logs
{
	private $atc = NULL;
	private $db = NULL;
	
	/**
	* Конструктор
	*/
	
	public function __construct(& $atc)
	{
		$this->atc = & $atc;
		$this->db = & $atc->db;
	}
	
	public function logs()
	{
		$p = isset($_GET['p']) && preg_match(PCREGEXP_INTEGER, $_GET['p']) ? $_GET['p'] : 0;
		$start = $p * 20;
		
		$cnt = $this->db->db_countrows(EVENTLOG_TABLE);
		
		$pgbar = $this->atc->pagebar($cnt, 20, $p, ATCMS_WEB_PATH . '/admin.php', 'act=logs');
		
		$sql = 'SELECT id_user, name FROM ' . USERS_TABLE . ' WHERE id_user IN (SELECT DISTINCT id_user FROM ' . EVENTLOG_TABLE . ')';
		for($u=$this->db->db_query($sql); $u_res=$this->db->db_fetchassoc($u); true)
		{
			$uinfo[$u_res['id_user']]['name'] = $u_res['name'];
		}
		$this->db->db_freeresult($u);
		
		$logs_list = '';
		$types = array
		(
			1=>$this->atc->lang['event_type_information'],
			2=>$this->atc->lang['event_type_warning'],
			3=>$this->atc->lang['event_type_achtung']
		);
		$sql = 'SELECT * FROM ' . EVENTLOG_TABLE . ' ORDER BY timestamp DESC ' . $this->db->db_limit($start, 20);
		for($l=$this->db->db_query($sql); $l_res=$this->db->db_fetchassoc($l); true)
		{
			$user = isset($uinfo[$l_res['id_user']]) ?
				'<a href="' . ATCMS_WEB_PATH . '/vcard.php?id_user=' . $l_res['id_user'] . '">' . htmlspecialchars($uinfo[$l_res['id_user']]['name']) . '</a>' : $this->atc->lang['guest'];
			$logs_list .= '<tr><td width="1%"><img title="' . $types[$l_res['type']] . '" alt="' . $types[$l_res['type']] . '" src="' . ATCMS_WEB_PATH . '/images/events/' . $l_res['type'] . '.png"></td><td nowrap="nowrap" width="1%">' . $this->atc->date($this->atc->cfgvar('date_format'), $l_res['timestamp']) . '</td><td>' . $user . '</td><td>' . htmlspecialchars($l_res['message']) . '</td></tr>';
		}
		$this->db->db_freeresult($l);
		
		$logs = $this->atc->template('admin/logs');
		$logs->add_tag('PGBAR', $pgbar);
		$logs->add_tag('LOGS_LIST', $logs_list);
		$logs->add_tag('CONFIRM', $this->atc->forms->generate_confirmation());
		$this->atc->process_contents($logs->ret(), $this->atc->lang['logs']);
	}
	
	public function truncate_logs()
	{
		if(!$this->atc->forms->validate())
		{
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['fatal_oops']);
		}
		
		$sql = 'TRUNCATE TABLE ' . EVENTLOG_TABLE;
		$this->db->db_query($sql);
		$this->atc->log_message(ATCEVENT_GENERAL, 'Event logs truncated');
		$this->atc->message($this->atc->lang['message'], $this->atc->lang['logs_truncated'], ATCMS_WEB_PATH . '/admin.php?act=logs', $this->atc->lang['go_back']);
	}
}

?>
