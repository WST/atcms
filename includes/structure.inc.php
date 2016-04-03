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

class atcstructure
{
	private $atc = NULL;
	private $db = NULL;
	private $structure = array();
	
	/**
	* Получить структуру в виде массива записей
	*/
	
	private function get_structure()
	{
		$sql = 'SELECT * FROM ' . STRUCTURE_TABLE/* . ' ORDER BY pos ASC'*/;
		$s = $this->db->db_query($sql);
		while($s_res = $this->db->db_fetchassoc($s))
		{
			$this->structure[$s_res['id_element']]['parent'] = $s_res['parent'];
			$this->structure[$s_res['id_element']]['title'] = $s_res['title'];
			$this->structure[$s_res['id_element']]['short_title'] = $s_res['short_title'];
			$this->structure[$s_res['id_element']]['pos'] = $s_res['pos'];
			$this->structure[$s_res['id_element']]['language'] = $s_res['language'];
		}
		$this->db->db_freeresult($s); //Освободить память от результата запроса
	}
	
	public function __construct(& $atc)
	{
		$this->atc = & $atc;
		$this->db = & $atc->db;
		$this->get_structure();
	}
	
	/**
	* Проверка страницы на существование в виртуальной структуре
	* @param int ID элемента
	* @retval bool признак существования
	*/
	
	public function element_exists($n)
	{
		return isset($this->structure[$n]);
	}
	
	/**
	* Получить массив «родителей» данного элемента структуры
	* @param int Идентификатор требуемого элемента
	* @retval array Массив с ID «родителей»
	*/
	
	private function process_parents($id_article)
	{
		if($id_article == 0 || !$this->element_exists($id_article))
			return array();
		
		$retval = array($id_article);
		if($this->structure[$id_article]['parent'] != 0)
		{
			$retval[] = $this->structure[$id_article]['parent'];
			$retval = array_merge($retval, $this->process_parents($this->structure[$id_article]['parent']));
		}
		return $retval;
	}
	
	/**
	* Получить одномерный массив из ID данного элемента и ID всех его «детей»
	* @retval array ID всех «детей»
	*/
	
	public function get_children($id_element)
	{
		$retval = array($id_element);
		foreach($this->structure as $k=>$v)
		{
			if($v['parent'] == $id_element) $retval = array_merge($retval, $this->get_children($k));
		}
		return $retval;
	}
	
	/**
	* Построить текстовое меню на базе структуры
	* Старую реализацию (1.x, x<4) лучше не смотрите, эта у меня удалась куда лучше :)
	* @param bool Строим ли меню для панели администратора
	* @param int ID обрабатываемого элемента структуры
	* @param int Текущий уровень рекурсии
	* @param int ID активного элемента
	* @retval string Структура в виде HTML-дерева
	*/
	
	public function process_structure($admin, $art, $level, $active_art, $language)
	{
		static $parents, $confirm;
		
		if(!$admin && $this->atc->cfgvar('structure_recursion_limit') != 0 && $level >= $this->atc->cfgvar('structure_recursion_limit')) return ''; //Прервать данный проход функции
		
		$element = $this->atc->template('menu_element');
		$element->add_tag('IN_ADMIN', ($admin ? 'yes' : ''));
		$element->add_tag('LANGUAGE', $language);
		
		$retval = ''; //Результат
		
		if(!isset($parents))
		{
			$parents = $this->process_parents($active_art); //родители просматриваемой статьи
		}
		
		if(!isset($confirm))
		{
			$confirm = $this->atc->forms->generate_confirmation();
		}
		$element->add_tag('CONFIRM', $confirm);
		
		$thread = array();
		
		foreach($this->structure as $k=>$v)
		{
			if($v['parent'] != $art) continue;
			if(!$admin && $level != 0 && !in_array($v['parent'], $parents)) continue;
			if($v['language'] != $language) continue;
			
			$thread[$v['pos']]['ID'] = $k;
			$thread[$v['pos']]['ELEMENT_TITLE'] = htmlspecialchars($v['title']);
			$thread[$v['pos']]['ELEMENT_SHORT_TITLE'] = htmlspecialchars($v['short_title']);
		}
		
		@ksort($thread);
		
		foreach($thread as $k=>$v)
		{
			$element->add_tag('ID', $v['ID']);
			$element->add_tag('ELEMENT_TITLE', htmlspecialchars($v['ELEMENT_TITLE']));
			$element->add_tag('ELEMENT_SHORT_TITLE', htmlspecialchars($v['ELEMENT_SHORT_TITLE']));
			
			for($i=0;$i<$level;$i++) $retval .= '&nbsp;&bull;&nbsp;';
			$retval .= $element->ret(false);
			$retval .= $this->process_structure($admin, $v['ID'], $level+1, $active_art, $language);
		}
		
		unset($element);
		return $retval;
	}
	
	/**
	* Вычесть из структуры детей заданного элемента и вернуть полученную структуру
	* @param int Идентификатор нужного элемента
	* @retval array Результирующая структура
	*/
	
	public function get_tree_without_children($id_element)
	{
		$retval = $this->structure;
		foreach($this->get_children($id_element) as $v)
		{
			unset($retval[$v]);
		}
		unset($retval[$id_element]);
		return $retval;
	}
	
	/**
	* Получить список «детей» одним уровнем ниже и выдать собственно в удобном виде
	* @param array Структура
	* @param int ID требуемого элемента
	* @retval string Результат для вывода
	*/
	
	public function process_substructure($p)
	{
		$substructure = array();
		foreach($this->structure as $k=>$v)
		{
			($v['parent'] == $p) &&
				$substructure[] = $k;
		}
		if(count($substructure) == 0) return ''; //Подразделов нет, список пуст.
		
		$substructure = implode(', ', $substructure);
		
		$retval = '';
		$subpage = $this->atc->template('subpage');
		
		$sql = 'SELECT * FROM ' . STRUCTURE_TABLE . ' WHERE id_element IN (' . $substructure . ') ORDER BY pos ASC';
		for($s=$this->db->db_query($sql); $s_res=$this->db->db_fetchassoc($s); true)
		{
			if($s_res['article'] != 0)
			{
				$sql = 'SELECT short_version, type FROM ' . ARTICLES_TABLE . ' WHERE id_article = ' . $s_res['article'];
				$a = $this->db->db_query($sql);
				if($this->db->db_numrows($a) == 0) simple_die('Invalid article');
				$a_res = $this->db->db_fetchassoc($a);
				$this->db->db_freeresult($a);
				
				$descr = $this->atc->process_text($a_res['short_version'], $a_res['type'], true);
				$subpage->add_tag('SUBPAGE_DESCR', $descr);
			}
			else //Да, это не аригатизатор (-:
			{
				$subpage->add_tag('SUBPAGE_DESCR', '');
			}
			$subpage->add_tag('SUBPAGE_TITLE', htmlspecialchars($s_res['title']));
			$subpage->add_tag('SUBPAGE_ID', $s_res['id_element']);
			$retval .= $subpage->ret();
		}
		$this->db->db_freeresult($s);
		
		return $retval;
	}
	
	/**
	* Геометрически сдвинуть элемент структуры
	* @param string направление смещения
	*/
	
	public function move_element($id_element, $direction)
	{
		$sql = 'SELECT pos, language, parent FROM ' . STRUCTURE_TABLE . ' WHERE id_element=' . $id_element;
		$p = $this->db->db_query($sql);
		$p_res = $this->db->db_fetchassoc($p);
		$this->db->db_freeresult($p);
		
		if(!$p_res) return false; //нет такого элемента
		
		switch($direction)
		{
			case 'down':
				$sql = 'SELECT id_element, pos FROM ' . STRUCTURE_TABLE . ' WHERE pos>' . $p_res['pos'] . ' AND language=' . $p_res['language'] . ' AND parent=' . $p_res['parent'] . ' ORDER BY pos ASC '.$this->db->db_limit(0, 1);
			break;
			default: case 'up':
				$sql = 'SELECT id_element, pos FROM ' . STRUCTURE_TABLE . ' WHERE pos<' . $p_res['pos'] . ' AND language=' . $p_res['language'] . ' AND parent=' . $p_res['parent'] . ' ORDER BY pos DESC '.$this->db->db_limit(0, 1);
			break;
		}
		
		$s = $this->db->db_query($sql);
		$s_res = $this->db->db_fetchassoc($s);
		$this->db->db_freeresult($s);
		if(!$s_res) return false;
		
		$sqlA = 'UPDATE ' . STRUCTURE_TABLE . ' SET pos=' . $p_res['pos'] . ' WHERE id_element=' . $s_res['id_element'];
		$sqlB = 'UPDATE ' . STRUCTURE_TABLE . ' SET pos=' . $s_res['pos'] . ' WHERE id_element=' . $id_element;
		
		$this->db->db_query($sqlA);
		$this->db->db_query($sqlB);
		
		$this->get_structure();
		
		return true;
	}
}

?>