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
define('ATCMS_FORM_LIFETIME', 21600);

class atcform
{
	private $form = '';
	private $lang = array();
	private $db = NULL;
	private $atc = NULL;
	
	public function __construct(& $form_manager, & $atc, & $db, $type, $fullpath, & $langvar, $values, $action, $method, $error='', $enctype='')
	{
		$this->lang = & $langvar;
		$this->atc = & $atc;
		$this->db = & $db;
		
		$this->form = $this->atc->template('form');
		$this->form->add_tag('METHOD', $method);
		$this->form->add_tag('ACTION', $action);
		$this->form->add_tag('FORM_HIDDEN_DATA', '');
		$this->form->add_tag('FORM_DATA', '');
		$this->form->add_tag('FORM_ERROR', '');
		$this->form->add_tag('ENCTYPE', $enctype);
		$this->form->add_tag('FORM_ERROR', $error);
		
		$sid = $form_manager->generate_form_id();
		
		$scheme =
			$fullpath ?
				atcms_parse_scheme_data(@file_get_contents($type), true) :
					atcms_parse_scheme_data(@file_get_contents(FORMS_DIRECTORY . '/' . $type . '.ini'), true);
		
		foreach($scheme as $k=>$v)
		{
			if(!$form_manager->widget_exists($v['type']))
				$form_manager->initialize_widget($v['type']);
			
			if(isset($v['access']))
			{
				if($v['access'] == 'guest' && $this->atc->session->ok) continue;
				if($v['access'] == 'admin' && !$this->atc->session->admin) continue;
				if($v['access'] == 'user' && !$this->atc->session->ok) continue;
			}
			
			$form_manager->widgets[$v['type']]->add_tag('NAME', $k);
			$form_manager->widgets[$v['type']]->add_tag('TITLE', isset($v['title']) ? $this->lang[$v['title']] : '');
			$form_manager->widgets[$v['type']]->add_tag('NOTE', isset($v['note']) ? $this->lang[$v['note']] : '');
			$form_manager->widgets[$v['type']]->add_tag('ID', isset($v['id']) ? $v['id'] : '');
			
			switch($v['type'])
			{
				case 'bbcode_toolbar':
					$form_manager->widgets[$v['type']]->add_tag('VALUE', $this->atc->bbcode_toolbar($v['target']));
					$this->form->ext_tag('FORM_DATA', $form_manager->widgets[$v['type']]->ret());
				break;
				case 'text':
				case 'password':
					$form_manager->widgets[$v['type']]->add_tag('VALUE', isset($values[$k]) ? $values[$k] : '');
					$form_manager->widgets[$v['type']]->add_tag('MAXLENGTH', isset($v['maxlength']) ? $v['maxlength'] : '');
					$this->form->ext_tag('FORM_DATA', $form_manager->widgets[$v['type']]->ret());
				break;
				case 'checkbox':
					$form_manager->widgets[$v['type']]->add_tag('VALUE', isset($values[$k]) && !empty($values[$k]) ? $values[$k] : '');
					$this->form->ext_tag('FORM_DATA', $form_manager->widgets[$v['type']]->ret());
				break;
				case 'hidden':
					$form_manager->widgets[$v['type']]->add_tag('VALUE', isset($values[$k]) ? $values[$k] : '');
					$this->form->ext_tag('FORM_HIDDEN_DATA', $form_manager->widgets[$v['type']]->ret());
				break;
				default:
					$form_manager->widgets[$v['type']]->add_tag('VALUE', isset($values[$k]) ? $values[$k] : '');
					$this->form->ext_tag('FORM_DATA', $form_manager->widgets[$v['type']]->ret());
				break;
			}
		}
		
		if(!$form_manager->widget_exists('hidden'))
				$form_manager->initialize_widget('hidden');
		
		$form_manager->widgets['hidden']->add_tag('NAME', 'form_id');
		$form_manager->widgets['hidden']->add_tag('VALUE', $sid);
		
		$this->form->ext_tag('FORM_HIDDEN_DATA', $form_manager->widgets['hidden']->ret());
	}
	
	/**
	* Напечатать сформированную форму
	*/
	
	public function out()
	{
		$this->form->out();
	}
	
	/**
	* Выдать сформированную форму
	* @retval string форма
	*/
	
	public function ret()
	{
		return $this->form->ret();
	}
}

class form_processor
{
	private $forms = array();
	private $atc = NULL;
	private $db = NULL;
	public $widgets = array();
	
	/**
	* Создать случайную строку для ИНН формы
	* @retval str SID
	*/
	
	public function generate_form_id()
	{
		do
		{
			$retval = $this->atc->session->generate_random_string(32);
		}
		while($this->db->db_countrows(FORMS_TABLE, '*', 'form_session=\'' . $retval . '\' AND id_user=\'' . $retval . '\' AND stime>' . ( CURRENT_TIMESTAMP - $this->atc->cfgvar('session_length') ) ));
		
		$sql = 'INSERT INTO ' . FORMS_TABLE . ' (form_session, id_user, stime) VALUES (\'' . $retval . '\', ' . $this->atc->session->id_user . ', ' . CURRENT_TIMESTAMP . ')';
		$this->db->db_query($sql);
		
		return $retval;
	}
	
	/**
	* Загрузить шаблоны элементов управления
	*/
	
	public function initialize_widget($type)
	{
		/// NOTE: проверка на isset в функции create
		$this->widgets[$type] = $this->atc->template('forms/' . $type);
	}
	
	public function widget_exists($type)
	{
		return isset($this->widgets[$type]);
	}
	
	/**
	* Конструктор
	* @param object ссылка на ядро AT CMS
	*/

	public function __construct(& $atc)
	{
		$this->atc = & $atc;
		$this->db = & $atc->db;
	}
	
	/**
	* Собрать новую форму
	* @param string схема формы
	* @param bool полный ли путь к схеме
	* @param array используемый языковой массив
	* @param array умолчальные значения полей
	* @param string ACTION формы
	* @param string METHOD формы
	* @param string сообщение об ошибке
	* @param string кодировка формы
	*/
	
	public function create($type, $fullpath, & $langvar, $values, $action, $method, $error='', $enctype='')
	{
		return new atcform($this, $this->atc, $this->db, $type, $fullpath, $langvar, $values, $action, $method, $error, $enctype);
	}
	
	/**
	* Проверить, не истёк ли срок действия формы
	* @param object ссылка на ядро
	* @retval bool признак результата
	*/
	
	public function validate()
	{
		if(!isset($_REQUEST['form_id']))
		{
			$this->atc->log_message(ATCEVENT_ACHTUNG, 'Form attack! There was no form_id provided!');
			return false;
		}
		else
		{
			$sql = 'DELETE FROM ' . FORMS_TABLE . ' WHERE id_user=' . $this->atc->session->id_user . ' AND form_session=\'' . $this->db->db_escape($_REQUEST['form_id']) . '\' AND stime>' . (CURRENT_TIMESTAMP - ATCMS_FORM_LIFETIME);
			$c = $this->db->db_query($sql);
			$c_res = $this->db->db_affected_rows($c, 0, 'cnt');
			
			if($c_res == 0)
			{
				$this->atc->log_message(ATCEVENT_ACHTUNG, 'Form attack! Trying to submit wrong form_id!');
				return false;
			}
		}
		return true;
	}
	
	/**
	* Просрочить формы, срок действия которых истёк
	* @param object ссылка на ядро
	*/
	
	public function delete_old_forms()
	{
		$sql = 'DELETE FROM ' . FORMS_TABLE . ' WHERE stime<' . (CURRENT_TIMESTAMP - ATCMS_FORM_LIFETIME);
		$this->db->db_query($sql);
	}
	
	/**
	* Сгенерировать код подтверждения для перехода по «опасной» ссылке
	* @retval string код подтверждения для подстановки в ссылку
	*/
	
	public function generate_confirmation()
	{
		return 'form_id=' . $this->generate_form_id();
	}
}

?>
