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

class atcadmin_languages
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
	
	public function languages()
	{
		$languages_list = $this->atc->template('admin/languages_list');
		$languages_list->add_tag('LANGUAGES_LIST', '');
		
		$confirm = $this->atc->forms->generate_confirmation();
		
		for($d = opendir(ATCMS_ROOT . '/languages'); (($d_res = readdir($d)) !== false); true)
		{
			if($d_res == '.' || $d_res == '..') continue;
			if(preg_match(PCREGEXP_LANGFILE_NAME, $d_res) && is_readable(ATCMS_ROOT . '/languages/' . $d_res))
			{
				$ln = substr($d_res, 0, strrpos($d_res, '.'));
				$flag = $this->atc->language_installed($ln);
				$installed = $flag ? $this->atc->lang['active'] : $this->atc->lang['inactive'];
				if($ln != LANGUAGE)
				{
					$action = $flag ? '<a href="' . ATCMS_WEB_PATH . '/admin.php?act=language_delete&amp;language_name=' . $ln . '&amp;' . $confirm . '" onclick="return confirm(\'' . $this->atc->lang['language_deletion_confirm'] . '\');">' . $this->atc->lang['language_delete'] . '</a>' : '<a href="' . ATCMS_WEB_PATH . '/admin.php?act=language_install&amp;language_name=' . $ln . '&amp;' . $confirm . '">' . $this->atc->lang['language_install'] . '</a>';
				}
				else
				{
					$action = $this->atc->lang['language_main'];
				}
				$languages_list->ext_tag('LANGUAGES_LIST', '<tr><td nowrap="nowrap">' . $ln . '</td><td>' . $installed . '</td><td>' . $action . '</td></tr>');
			}
		}
		closedir($d);
		
		$this->atc->process_contents($languages_list->ret(), $this->atc->lang['languages_management']);
	}
	
	public function language_install()
	{
		if(!$this->atc->forms->validate())
		{
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['fatal_oops']);
		}
		
		isset($_GET['language_name']) && preg_match(PCREGEXP_LANGNAME, $_GET['language_name']) && file_exists($f = ATCMS_ROOT . '/languages/' . $_GET['language_name'] . '.php') || $this->atc->message($this->atc->lang['error'], $this->atc->lang['language_installation_error'], ATCMS_WEB_PATH . '/admin.php?act=languages', $this->atc->lang['go_back']);
		
		$sql = 'INSERT INTO ' . LANGUAGES_TABLE . ' (file) VALUES (\''. $_GET['language_name'] .'\')';
		$this->db->db_query($sql);
		
		$this->atc->message($this->atc->lang['message'], $this->atc->lang['language_installed'], ATCMS_WEB_PATH . '/admin.php?act=languages', $this->atc->lang['go_back']);
	}
	
	public function language_delete()
	{
		if(!$this->atc->forms->validate())
		{
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['fatal_oops']);
		}
		
		isset($_GET['language_name']) && preg_match(PCREGEXP_LANGNAME, $_GET['language_name']) && $this->atc->language_installed($_GET['language_name']) || $this->atc->message($this->atc->lang['error'], $this->atc->lang['language_deletion_error'], ATCMS_WEB_PATH . '/admin.php?act=languages', $this->atc->lang['go_back']);
		
		$id_language = $this->atc->get_language_id($_GET['language_name']);
		
		$sql = 'SELECT module, id_element FROM ' . STRUCTURE_TABLE . ' WHERE language=' . $id_language . ' AND module<>0';
		for($m=$this->db->db_query($sql); $m_res=$this->db->db_fetchassoc($m); true)
		{
			//Удалить ненужные ветки из модулей
			$this->atc->id_modules[$m_res['module']]->delete_thread($m_res['id_element']);
		}
		$this->db->db_freeresult($m);
		
		$sql = 'DELETE FROM ' . ARTICLES_TABLE . ' WHERE id_article IN (SELECT article FROM ' . STRUCTURE_TABLE . ' WHERE language=' . $id_language . ')';
		$this->db->db_query($sql);
		
		$sql = 'DELETE FROM ' . STRUCTURE_TABLE . ' WHERE language=' . $id_language;
		$this->db->db_query($sql);
		
		$sql = 'DELETE FROM ' . TAGS_TABLE . ' WHERE language=' . $id_language;
		$this->db->db_query($sql);
		
		$sql = 'DELETE FROM ' . LANGUAGES_TABLE . ' WHERE file=\'' . $_GET['language_name'] . '\'';
		$this->db->db_query($sql);
		
		$this->atc->message($this->atc->lang['message'], $this->atc->lang['language_deleted'], ATCMS_WEB_PATH . '/admin.php?act=languages', $this->atc->lang['go_back']);
	}
}

?>