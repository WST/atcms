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

class atcadmin_structure
{
	private $atc = NULL;
	private $lang = NULL;
	private $db = NULL;
	private $request = array(); //для простоты эта штука объединяет GET и POST -параметры
	
	/**
	* Конструктор
	*/
	
	public function __construct(& $atcadmin)
	{
		$this->atc = & $atcadmin;
		$this->lang = & $atcadmin->lang;
		$this->db = & $atcadmin->db;
		$this->request = array_merge($_GET, $_POST);
	}
	
	/**
	* Проверить корректность ID требуемого элемента структуры
	* и отвалиться с ошибкой в случае некорректности оного
	* @note проверяется значение $_GET['id_element'] / $_POST['id_element']
	*/
	
	private function validate_id_element()
	{
		isset($this->request['id_element'])
			&& preg_match(PCREGEXP_INTEGER, $this->request['id_element'])
				&& $this->atc->structure->element_exists($this->request['id_element'])
					|| $this->atc->message($this->lang['error'], $this->lang['target_element_doesnt_exist'], ATCMS_WEB_PATH . '/admin.php?act=structure', $this->lang['return_to_acp_structure_manager']);
	}
	
	/**
	* Проверить корректность ID требуемого языка
	* и отвалиться с ошибкой в случае некорректности оного
	* @note проверяется значение $_GET['language'] / $_POST['language']
	*/
	
	private function validate_id_language()
	{
		isset($this->request['language'])
			&& $this->atc->id_language_installed($this->request['language'])
				|| $this->atc->general_message(ATCMESSAGE_WRONG_LANGUAGE);
	}
	
	/**
	* Главное меню структурного менеджера
	*/
	
	public function structure()
	{
		if(count($this->atc->languages) == 1)
		{
			$l = $this->atc->language;
			$structure = $this->atc->structure->process_structure(true, 0, 0, 0, $l);
			$structure_manager = $this->atc->template('admin/structure_manager');
			$structure_manager->add_tag('TREE', $structure);
			$structure_manager->add_tag('LANGUAGE', $l);
			$structure_manager->add_tag('LANGUAGE_NAME', $this->atc->languages[$l]);
			$this->atc->process_contents($structure_manager->ret(), $this->lang['structure_management']);
		}
		elseif(!isset($this->request['language']))
		{
			$select_tree = $this->atc->template('admin/select_tree');
			$this->languages = '<ul>';
			foreach($this->atc->languages as $k=>$v)
			{
				$this->languages .= '<li><a href="' . ATCMS_WEB_PATH . '/admin.php?act=structure&amp;language=' . $k . '">' . $v . '</a></li>';
			}
			$this->languages .= '</ul>';
			$select_tree->add_tag('LANGUAGES', $this->languages);
			$this->atc->process_contents($select_tree->ret(), $this->lang['structure_management']);
		}
		else
		{
			$this->validate_id_language();
			
			$structure = $this->atc->structure->process_structure(true, 0, 0, 0, $this->request['language']);
			
			$structure_manager = $this->atc->template('admin/structure_manager');
			$structure_manager->add_tag('TREE', $structure);
			$structure_manager->add_tag('LANGUAGE', $this->request['language']);
			$structure_manager->add_tag('LANGUAGE_NAME', $this->atc->languages[$this->request['language']]);
			$this->atc->process_contents($structure_manager->ret(), $this->lang['structure_management']);
		}
	}
	
	/**
	* Удаление элемента структуры
	*/
	
	public function structure_element_delete()
	{
		if(!$this->atc->forms->validate())
		{
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['fatal_oops']);
		}
		
		$this->validate_id_element();
		
		$children = $this->atc->structure->get_children($this->request['id_element']);
		
		$sql = 'SELECT module_name FROM ' . MODULES_TABLE . ' WHERE id_module IN (SELECT module FROM ' . STRUCTURE_TABLE . ' WHERE id_element IN (' . ($els = implode(', ', $children)) . '))';
		
		//Удалить соответствующие ветки из модулей
		for($m=$this->db->db_query($sql); $m_res=$this->db->db_fetchrow($m); true)
		{
			$this->atc->modules[$m_res[0]]->delete_thread($this->request['id_element']);
		}
		$this->db->db_freeresult($m);
		
		$sql = 'DELETE FROM ' . ARTICLES_TABLE . ' WHERE id_article IN (SELECT article FROM ' . STRUCTURE_TABLE . ' WHERE id_element IN (' . $els . '))';
		$this->db->db_query($sql);
		
		$sql = 'DELETE FROM ' . STRUCTURE_TABLE . ' WHERE id_element IN (' . $els . ')';
		$this->db->db_query($sql);
		
		if(count($children) == 1)
			$this->atc->message($this->lang['message'], $this->lang['structure_element_deleted'], ATCMS_WEB_PATH . '/admin.php?act=structure', $this->lang['return_to_acp_structure_manager']);
		else
			$this->atc->message($this->lang['message'], $this->lang['structure_subtree_deleted'], ATCMS_WEB_PATH . '/admin.php?act=structure', $this->lang['return_to_acp_structure_manager']);
	}
	
	/**
	* Создание нового элемента структуры (форма)
	*/
	
	public function structure_element_new($error = '')
	{
		$this->validate_id_language();
		isset($this->request['id_element'])
			|| $this->atc->message($this->lang['error'], $this->lang['target_element_doesnt_exist'], ATCMS_WEB_PATH . '/admin.php?act=structure', $this->lang['return_to_acp_structure_manager']);
		if($this->request['id_element'] != 0)
			$this->validate_id_element();
		
		$data = array
		(
			//Вот здесь накосячить проще всего
			'act'=>'structure_element_new_save',
			'language'=>$this->request['language'],
			'id_element'=>$this->request['id_element'],
			'module'=>$this->atc->get_modules_list(isset($this->request['module']) ? $this->request['module'] : 0),
			'format'=>atcms_datatypes_browser(isset($this->request['format']) ? $this->request['format'] : ATCFORMAT_BBCODE),
			'href'=>htmlspecialchars(isset($this->request['href']) ? $this->request['href'] : ''),
			'short_version'=>htmlspecialchars(isset($this->request['short_version']) ? $this->request['short_version'] : ''),
			'full_version'=>htmlspecialchars(isset($this->request['full_version']) ? $this->request['full_version'] : ''),
			'title'=>htmlspecialchars(isset($this->request['title']) ? $this->request['title'] : ''),
			'short_title'=>htmlspecialchars(isset($this->request['short_title']) ? $this->request['short_title'] : ''),
			'display_substructure'=>(isset($this->request['display_substructure']) && $this->request['display_substructure'] == 1 ? 1 : 0)
		);
		
		$form = $this->atc->forms->create('admin/structure_element_new', false, $this->atc->lang, $data, ATCMS_WEB_PATH . '/admin.php', 'POST', $error);
		$structure_element_new = $this->atc->template('admin/structure_element_new');
		$structure_element_new->add_tag('FORM', $form->ret());
		$this->atc->process_contents($structure_element_new->ret(), $this->lang['structure_element_addition']);
	}
	
	/**
	* Создание нового элемента структуры (обработчик)
	*/
	
	public function structure_element_new_save()
	{
		$this->validate_id_language();
		
		if(!$this->atc->forms->validate())
		{
			return $this->structure_element_new($this->atc->lang['oops']);
		}
		
		isset($this->request['id_element']) || $this->atc->message($this->lang['error'], $this->lang['target_element_doesnt_exist'], ATCMS_WEB_PATH . '/admin.php?act=structure', $this->lang['return_to_acp_structure_manager']);
		if($this->request['id_element'] != 0)
			$this->validate_id_element();
		
		if(!isset($this->request['title']) || trim($this->request['title'])=='')
		{
			return $this->structure_element_new($this->lang['empty_element_title']);
		}
		
		if(!isset($this->request['short_title']) || trim($this->request['short_title'])=='')
		{
			return $this->structure_element_new($this->lang['empty_element_short_title']);
		}
		
		isset($this->request['format'])
			&& $this->request['format'] == ATCFORMAT_HTML
				|| $this->request['format'] = ATCFORMAT_BBCODE;
				
		
		$module = isset($this->request['module']) && preg_match(PCREGEXP_INTEGER, $this->request['module']) ? $this->request['module'] : 0;
		$substructure = isset($this->request['display_substructure']) && $this->request['display_substructure'] == 1 ? 1 : 0;
		
		$short = isset($this->request['short_version']) ? $this->atc->preprocess_text($this->request['short_version'], $this->request['format']) : '';
		$full = isset($this->request['full_version']) ? $this->atc->preprocess_text($this->request['full_version'], $this->request['format']) : '';
		
		//Запросим новую позицию
		$sql = 'SELECT max(pos)+1 AS newpos FROM ' . STRUCTURE_TABLE . ' WHERE language=' . $this->request['language'];
		$p = $this->db->db_query($sql);
		$newpos = (int) ($this->db->db_result($p, 0, 'newpos'));
		$this->db->db_freeresult($p);
		
		if($short != '' || $full != '')
		{
			//Сначала занесём текстовые данные в отдельную таблицу
			$sql = 'INSERT INTO ' . ARTICLES_TABLE . ' (timestamp, type, short_version, full_version) VALUES (' . CURRENT_TIMESTAMP . ', ' . $this->request['format'] . ', \'' . $this->db->db_escape($short) . '\', \'' . $this->db->db_escape($full) . '\')';
			$a = $this->db->db_query($sql);
			$article = $this->db->db_insert_id();
		}
		else
		{
			$article = 0;
		}
		
		$sql = 'INSERT INTO ' . STRUCTURE_TABLE . ' (parent, title, short_title, module, article, href, pos, substructure, language) VALUES (' . $this->request['id_element'] . ', \'' . $this->db->db_escape($this->request['title']) . '\', \'' . $this->db->db_escape($this->request['short_title']) . '\', ' . $module . ', ' . $article . ', \'' . $this->db->db_escape($this->request['href']) . '\', ' . $newpos . ', ' . $substructure . ', ' . $this->request['language'] . ')';
		$this->db->db_query($sql);
		
		$this->atc->log_message(ATCEVENT_GENERAL, 'Added new structure element');
		$this->atc->message($this->lang['message'], str_replace('%1', $this->request['title'], $this->lang['structure_element_added']), ATCMS_WEB_PATH . '/admin.php?act=structure&amp;language=' . $this->request['language'], $this->lang['return_to_acp_structure_manager']);
	}
	
	/**
	* Редактирование элемента структуры (форма)
	*/
	
	public function structure_element_edit($error = '')
	{
		$this->validate_id_element();
		
		$sql = 'SELECT * FROM ' . STRUCTURE_TABLE . ' WHERE id_element=' . $this->request['id_element'];
		$e = $this->db->db_query($sql);
		$e_res = $this->db->db_fetchassoc($e);
		$this->db->db_freeresult($e);
		
		if($e_res['module'] != 0)
		{
			$sql = 'SELECT module_name FROM ' . MODULES_TABLE . ' WHERE id_module=' . $e_res['module'];
			$m = $this->db->db_query($sql);
			$module = $this->db->db_result($m, 0, 'module_name') . ' [ <a href="' . ATCMS_WEB_PATH . '/admin.php?act=structure_element_delete_module&amp;id_element=' . $this->request['id_element'] . '&amp;' . $this->atc->forms->generate_confirmation() . '" title="' . $this->lang['delete'] . '">x</a> ]';
			$this->db->db_freeresult($m);
		}
		else
		{
			$module = isset($this->request['module']) ?
				'<select name="module"><option value="0">' . $this->lang['no_module'] . '</option>' :
					'<select name="module"><option value="0" selected>' . $this->lang['no_module'] . '</option>';
			$sql = 'SELECT module_name, id_module FROM ' . MODULES_TABLE . ' WHERE mcpc=1';
			for($m=$this->db->db_query($sql); $m_res = $this->db->db_fetchassoc($m); true)
			{
				$sel = (isset($this->request['module']) && $this->request['module'] == $m_res['id_module']) ? ' selected' : '';
				$module .= '<option value="' . $m_res['id_module'] . '"' . $sel . '>' . $m_res['module_name'] . '</option>';
			}
			$module .= '</select>';
			$this->db->db_freeresult($m);
		}
		
		$structure_element_edit = $this->atc->template('admin/structure_element_edit');
		
		if($e_res['article'] != 0)
		{
			$sql = 'SELECT short_version, full_version, type FROM ' . ARTICLES_TABLE . ' WHERE id_article=' . $e_res['article'];
			$a = $this->db->db_query($sql);
			$a_res = $this->db->db_fetchassoc($a);
			$this->db->db_freeresult($a);
			
			$full_version = $this->atc->unprocess_text($a_res['full_version'], $a_res['type']);
			$short_version = $this->atc->unprocess_text($a_res['short_version'], $a_res['type']);
			$type = $a_res['type'];
		}
		else //Текстового содержимого нет
		{
			$full_version = '';
			$short_version = '';
			$type = ATCFORMAT_BBCODE;
		}
		
		$possible_parents = '<option value="0">(' . $this->lang['root'] . ')</option>';
		foreach($this->atc->structure->get_tree_without_children($this->request['id_element']) as $k=>$v)
		{
			if($v['language'] != $e_res['language']) continue;
			$sel = $e_res['parent'] == $k ? ' selected' : '';
			$possible_parents .= '<option value="'.$k.'"'. $sel .'>' . $this->atc->process_text($v['title'], ATCFORMAT_HTML) . '</option>';
		}
		
		$data = array
		(
			'act'=>'structure_element_edit_save',
			'language'=>$e_res['language'],
			'id_element'=>$this->request['id_element'],
			'parent'=>$possible_parents,
			'module'=>$module,
			'display_substructure'=>isset($this->request['display_substructure']) && $this->request['display_substructure']=='1' ? '1' : $e_res['substructure'],
			'format'=>atcms_datatypes_browser(isset($this->request['format']) ? $this->request['format'] : $type),
			'href'=>htmlspecialchars(isset($this->request['href']) ? $this->request['href'] : $e_res['href']),
			'short_version'=>isset($this->request['short_version']) ? htmlspecialchars($this->request['short_version']) : $short_version,
			'full_version'=>isset($this->request['full_version']) ? htmlspecialchars($this->request['full_version']) : $full_version,
			'title'=>htmlspecialchars(isset($this->request['title']) ? $this->request['title'] : $e_res['title']),
			'short_title'=>htmlspecialchars(isset($this->request['short_title']) ? $this->request['short_title'] : $e_res['short_title'])
		);
		
		$form = $this->atc->forms->create('admin/structure_element_edit', false, $this->atc->lang, $data, ATCMS_WEB_PATH . '/admin.php', 'POST', $error);
		$structure_element_edit->add_tag('FORM', $form->ret());
		$this->atc->process_contents($structure_element_edit->ret(), $this->atc->lang['structure_element_edit']);
	}
	
	/**
	* Редактирование элемента структуры (обработчик)
	*/
	
	public function structure_element_edit_save()
	{
		$this->validate_id_element();
		
		if(!$this->atc->forms->validate())
		{
			return $this->structure_element_edit($this->atc->lang['oops']);
		}
		
		//Ничотаг проверочка :-)
		$parent = 
			isset($this->request['parent']) &&
			preg_match(PCREGEXP_INTEGER, $this->request['parent']) &&
			$this->atc->structure->element_exists($this->request['parent']) &&
			!in_array($this->request['parent'], $this->atc->structure->get_children($this->request['id_element'])) &&
			$this->request['parent'] != $this->request['id_element'] ?
				$this->request['parent'] : 0;
		
		$module = (isset($this->request['module']) && preg_match(PCREGEXP_INTEGER, $this->request['module']) && $this->atc->id_module_installed($this->request['module'])) ? $this->request['module'] : 0;
		
		$sql = 'SELECT article, parent, module FROM ' . STRUCTURE_TABLE . ' WHERE id_element=' . $this->request['id_element'];
		$m = $this->db->db_query($sql);
		$e_res = $this->db->db_fetchassoc($m);
		$this->db->db_freeresult($m);
		
		if($e_res['module'] != 0)
		{
			$module = $e_res['module'];
		}
		
		if(!isset($this->request['title']) || trim($this->request['title']) == '')
		{
			return $this->structure_element_edit($this->lang['empty_element_title']);
		}
		$title = $this->db->db_escape($this->request['title']);
		
		if(!isset($this->request['short_title']) || trim($this->request['short_title']) == '')
		{
			return $this->structure_element_edit($this->lang['empty_element_short_title']);
		}
		$short_title = $this->db->db_escape($this->request['short_title']);
		
		isset($this->request['format'])
			&& $this->request['format'] == ATCFORMAT_HTML
				|| $this->request['format'] = ATCFORMAT_BBCODE;
		
		$substructure = isset($this->request['display_substructure']) && $this->request['display_substructure'] == 1 ? 1 : 0;
		
		$short = isset($this->request['short_version']) ? $this->atc->preprocess_text($this->request['short_version'], $this->request['format']) : '';
		$full = isset($this->request['full_version']) ? $this->atc->preprocess_text($this->request['full_version'], $this->request['format']) : '';
		
		if($short != '' || $full != '')
		{
			if($e_res['article'] == 0) //Ещё не было
			{
				$sql = 'INSERT INTO ' . ARTICLES_TABLE . ' (timestamp, type, short_version, full_version) VALUES (' . CURRENT_TIMESTAMP . ', ' . $this->request['format'] . ', \'' . $this->db->db_escape($short) . '\', \'' . $this->db->db_escape($full) . '\')';
				$a = $this->db->db_query($sql);
				$article = $this->db->db_insert_id();
			}
			else
			{
				$sql = 'UPDATE ' . ARTICLES_TABLE . ' SET timestamp=' . CURRENT_TIMESTAMP . ', type=' . $this->request['format'] . ', short_version=\'' . $this->db->db_escape($short) . '\', full_version=\'' . $this->db->db_escape($full) . '\' WHERE id_article=' . $e_res['article'];
				$this->db->db_query($sql);
				$article = $e_res['article']; //Ничего не менять
			}
		}
		else
		{
			$sql = 'DELETE FROM ' . ARTICLES_TABLE . ' WHERE id_article=' . $e_res['article'];
			$this->db->db_query($sql);
			$article = 0;
		}
		
		$sql = 'UPDATE ' . STRUCTURE_TABLE . ' SET parent='. $parent .', title=\''. $title .'\', short_title=\'' . $short_title . '\', module=' . $module . ', article=' . $article . ', substructure=' . $substructure . ', href=\'' . $this->db->db_escape($this->request['href']) . '\' WHERE id_element=' . $this->request['id_element'];
		$this->db->db_query($sql);
		
		$this->atc->message($this->lang['message'], $this->lang['structure_element_saved'], ATCMS_WEB_PATH . '/admin.php?act=structure&amp;language=' . $this->request['language'], $this->lang['return_to_acp_structure_manager']);
	}
	
	/**
	* Сместить элемент структуры на 1 позицию вверх
	*/
	
	public function structure_element_up()
	{
		//$this->structure_element_move('up');
		if(!$this->atc->structure->move_element(@$_GET['id_element'], 'up'))
		{
			$this->atc->message($this->lang['message'], $this->lang['not_movable'], ATCMS_WEB_PATH . '/admin.php?act=structure&amp;language=' . $this->request['language'], $this->lang['go_back']);
		}
		else
		{
			header('Location: ' . ATCMS_WEB_PATH . '/admin.php?act=structure&language=' . $this->request['language']);
		}
	}
	
	/**
	* Сместить элемент структуры на 1 позицию вниз
	*/
	
	public function structure_element_down()
	{
		if(!$this->atc->structure->move_element(@$_GET['id_element'], 'down'))
		{
			$this->atc->message($this->lang['message'], $this->lang['not_movable'], ATCMS_WEB_PATH . '/admin.php?act=structure&amp;language=' . $this->request['language'], $this->lang['go_back']);
		}
		else
		{
			header('Location: ' . ATCMS_WEB_PATH . '/admin.php?act=structure&language=' . $this->request['language']);
		}
	}
	
	/**
	* Удалить из элемента экземпляр модуля
	*/
	
	public function structure_element_delete_module()
	{
		if(!$this->atc->forms->validate())
		{
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['fatal_oops']);
		}
		
		$this->validate_id_element();
		
		$sql = 'SELECT module_name FROM ' . MODULES_TABLE . ' WHERE id_module = (SELECT module FROM ' . STRUCTURE_TABLE . ' WHERE id_element=' . $this->request['id_element'] . ')';
		$m = $this->db->db_query($sql);
		$m_res = $this->db->db_fetchrow($m);
		$this->db->db_freeresult($m);
		
		$this->atc->modules[$m_res[0]]->delete_thread($this->request['id_element']);
		
		$sql = 'UPDATE ' . STRUCTURE_TABLE . ' SET module=0 WHERE id_element=' . $this->request['id_element'];
		$this->db->db_query($sql);
		
		$this->atc->message($this->lang['message'], str_replace('%1', $m_res[0], $this->lang['module_thread_deleted']), ATCMS_WEB_PATH . '/admin.php?act=structure_element_edit&amp;id_element=' . $this->request['id_element'], $this->lang['go_back']);
	}
}

?>
