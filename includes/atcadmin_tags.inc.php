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

class atcadmin_tags
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
	
	/**
	* Список тегов
	*/
	
	public function tags()
	{
		$p = isset($_GET['p']) && preg_match(PCREGEXP_INTEGER, $_GET['p']) ? $_GET['p'] : 0;
		$start = $p * 20;
		$cnt = $this->db->db_countrows(TAGS_TABLE);
		
		$pgbar = $this->atc->pagebar($cnt, 20, $p, ATCMS_WEB_PATH . '/admin.php', 'act=tags');
		
		$tags_list = '';
		$sql = 'SELECT * FROM ' . TAGS_TABLE . ' ORDER BY language ASC ' . $this->db->db_limit($p, 20);
		for($t=$this->db->db_query($sql); $t_res=$this->db->db_fetchassoc($t); true)
		{
			$t_res['language'] = ($t_res['language'] == 0) ? $this->atc->lang['all_languages'] : $this->atc->languages[$t_res['language']];
			$tags_list .= '<tr><td>' . htmlspecialchars($t_res['replace_from']) . '</td><td>' . htmlspecialchars($t_res['replace_to']) . '</td><td>' . $t_res['language'] . '</td><td><a href="' . ATCMS_WEB_PATH . '/admin.php?act=tag_delete&amp;id_tag=' . $t_res['id_tag'] . '&amp;' . $this->atc->forms->generate_confirmation() . '" title="' . $this->atc->lang['delete'] . '">' . $this->atc->lang['tag_delete'] . '</a>&nbsp;|&nbsp;<a href="' . ATCMS_WEB_PATH . '/admin.php?act=edit_tag&amp;id_tag=' . $t_res['id_tag'] . '" title="' . $this->atc->lang['edit'] . '">' . $this->atc->lang['edit_tag'] . '</a></td></tr>';
		}
		$this->db->db_freeresult($t);
		if($tags_list == '') $tags_list = '<tr><td colspan="4" align="center">'.$this->atc->lang['no_tags'].'</td></tr>';
		
		$tags = $this->atc->template('admin/tags');
		$tags->add_tag('PGBAR', $pgbar);
		$tags->add_tag('TAGS_LIST', $tags_list);
		$this->atc->process_contents($tags->ret(), $this->atc->lang['tags']);
	}
	
	/**
	* Добавление нового тега (форма)
	*/
	
	public function new_tag($error = '')
	{
		$data = array
		(
			'language'=>$this->atc->get_languages_list( (isset($_POST['language']) ? $_POST['language'] : 0) , true),
			'act'=>'new_tag_save',
			'from'=>(isset($_POST['from']) ? htmlspecialchars($_POST['from']) : ''),
			'to'=>(isset($_POST['to']) ? htmlspecialchars($_POST['to']) : '')
		);
		
		$form = $this->atc->forms->create('admin/newtag', false, $this->atc->lang, $data, ATCMS_WEB_PATH . '/admin.php', 'POST', $error);
		
		$newtag = $this->atc->template('admin/newtag');
		$newtag->add_tag('FORM', $form->ret());
		$this->atc->process_contents($newtag->ret(), $this->atc->lang['new_tag']);
	}
	
	/**
	* Добавление нового тега (обработчик)
	*/
	
	public function new_tag_save()
	{
		if(!$this->atc->forms->validate())
		{
			return $this->new_tag($this->atc->lang['oops']);
		}
		if(!isset($_POST['from']) || trim($_POST['from'])=='')
		{
			return $this->new_tag($this->atc->lang['empty_tag']);
		}
		isset($_POST['to']) || $_POST['to'] = '';
		if(!isset($_POST['language']))
		{
			$this->atc->general_message(ATCMESSAGE_WRONG_LANGUAGE);
		}
		elseif($_POST['language'] != 0 && !$this->atc->id_language_installed($_POST['language']))
		{
			$this->atc->general_message(ATCMESSAGE_WRONG_LANGUAGE);
		}
		
		$sql = 'INSERT INTO ' . TAGS_TABLE . ' (language, replace_from, replace_to) VALUES (' . $_POST['language'] . ', \'' . $this->db->db_escape($_POST['from']) . '\', \'' . $this->db->db_escape($_POST['to']) . '\')';
		$this->db->db_query($sql);
		
		$this->atc->log_message(ATCEVENT_GENERAL, 'New tag added: ' . $_POST['from']);
		$this->atc->message($this->atc->lang['message'], $this->atc->lang['tag_added'], ATCMS_WEB_PATH . '/admin.php?act=tags', $this->atc->lang['go_back']);
	}
	
	/**
	* Удаление тега
	*/
	
	public function tag_delete()
	{
		if(!$this->atc->forms->validate())
		{
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['fatal_oops']);
		}
		
		isset($_GET['id_tag']) || $this->atc->message($this->atc->lang['error'], $this->atc->lang['tag_doesnt_exist'], ATCMS_WEB_PATH . '/admin.php?act=tags', $this->atc->lang['go_back']);
		$sql = 'DELETE FROM ' . TAGS_TABLE . ' WHERE id_tag=' . (int)$_GET['id_tag'];
		$d = $this->db->db_query($sql);
		if($this->db->db_affected_rows($d) == 0) $this->atc->message($this->atc->lang['error'], $this->atc->lang['tag_doesnt_exist'], ATCMS_WEB_PATH . '/admin.php?act=tags', $this->atc->lang['go_back']);
		$this->atc->message($this->atc->lang['message'], $this->atc->lang['tag_deleted'], ATCMS_WEB_PATH . '/admin.php?act=tags', $this->atc->lang['go_back']);
	}
	
	public function edit_tag($error = '')
	{
		$request = array_merge($_GET, $_POST);
		
		isset($request['id_tag']) && preg_match(PCREGEXP_INTEGER, $request['id_tag']) || $this->atc->message($this->atc->lang['error'], $this->atc->lang['tag_doesnt_exist'], ATCMS_WEB_PATH . '/admin.php?act=tags', $this->atc->lang['go_back']);
		$sql = 'SELECT * FROM ' . TAGS_TABLE . ' WHERE id_tag=' . $request['id_tag'];
		$t = $this->db->db_query($sql);
		if($this->db->db_numrows($t) == 0)
		{
			$this->db->db_freeresult($t);
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['tag_doesnt_exist'], ATCMS_WEB_PATH . '/admin.php?act=tags', $this->atc->lang['go_back']);
		}
		$t_res = $this->db->db_fetchassoc($t);
		$this->db->db_freeresult($t);
		
		$data = array
		(
			'act'=>'edit_tag_save',
			'id_tag'=>$request['id_tag'],
			'from'=>htmlspecialchars(isset($request['from']) ? $request['from'] : $t_res['replace_from']),
			'to'=>htmlspecialchars(isset($request['to']) ? $request['to'] : $t_res['replace_to']),
			'language'=>$this->atc->get_languages_list( (isset($request['language']) ? $request['language'] : $t_res['language']) , true)
		);
		
		$edit_tag = $this->atc->template('admin/edit_tag');
		
		$form = $this->atc->forms->create('admin/edit_tag', false, $this->atc->lang, $data, ATCMS_WEB_PATH . '/admin.php', 'POST', $error);
		$edit_tag->add_tag('FORM', $form->ret());
		$this->atc->process_contents($edit_tag->ret(), $this->atc->lang['edit_tag']);
	}
	
	public function edit_tag_save()
	{
		if(!$this->atc->forms->validate())
		{
			return $this->new_tag($this->atc->lang['oops']);
		}
		
		if(!isset($_POST['from']) || trim($_POST['from']) == '')
		{
			return $this->edit_tag($this->atc->lang['empty_tag']);
		}
		
		isset($_POST['to']) || $_POST['to'] = '';
		isset($_POST['id_tag']) || $this->atc->message($this->atc->lang['error'], $this->atc->lang['tag_doesnt_exist'], ATCMS_WEB_PATH . '/admin.php?act=tags', $this->atc->lang['go_back']);
		
		if(!isset($_POST['language']))
		{
			$this->atc->general_message(ATCMESSAGE_WRONG_LANGUAGE);
		}
		elseif($_POST['language'] != 0 && !$this->atc->id_language_installed($_POST['language']))
		{
			$this->atc->general_message(ATCMESSAGE_WRONG_LANGUAGE);
		}
		
		$sql = 'UPDATE ' . TAGS_TABLE . ' SET replace_from=\'' . $this->db->db_escape($_POST['from']) . '\', replace_to=\'' . $this->db->db_escape($_POST['to']) . '\', language=' . $_POST['language'] . ' WHERE id_tag=' . $_POST['id_tag'];
		$this->db->db_query($sql);
		
		$this->atc->log_message(ATCEVENT_GENERAL, 'Tag updated: ' . $_POST['from']);
		$this->atc->message($this->atc->lang['message'], $this->atc->lang['tag_saved'], ATCMS_WEB_PATH . '/admin.php?act=tags', $this->atc->lang['go_back']);
	}
}

?>
