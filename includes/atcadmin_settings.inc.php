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

class atcadmin_settings
{
	private $atc = NULL;
	private $db = NULL;
	private $current_configuration = NULL;
	
	/**
	* Конструктор
	*/
	
	public function __construct(& $atc)
	{
		$this->atc = & $atc;
		$this->db = & $atc->db;
		$this->current_configuration = & $atc->sysconfig;
	}
	
	public function settings()
	{
		$this->current_configuration['layout'] = atcms_layout_browser($this->current_configuration['layout']);
		$this->current_configuration['timezone'] = atcms_timezone_browser($this->current_configuration['timezone']);
		$this->current_configuration['act'] = 'settings_save';
		
		$strings = array
		(
			'site_title',
			'site_description',
			'keywords',
			'date_format',
		);
		
		foreach($strings as $k=>$v)
			$this->current_configuration[$v] = htmlspecialchars($this->current_configuration[$v]);
		
		$form = $this->atc->forms->create( 'settings', false, $this->atc->lang, $this->current_configuration, ATCMS_WEB_PATH . '/admin.php', 'POST');
		$settings = $this->atc->template('admin/settings');
		$settings->add_tag('FORM', $form->ret());
		
		$this->atc->process_contents($settings->ret(), $this->atc->lang['settings']);
	}
	
	public function settings_save()
	{
		if(!$this->atc->forms->validate())
		{
			return $this->settings($this->atc->lang['oops']);
		}
		
		$set = array();
		
		$checkboxes = array('process_emoticons', 'display_online_users', 'guests_can_view_users_list');
		foreach($checkboxes as $k=>$v)
			$set[$v] =
				(isset($_POST[$v]) && $_POST[$v] == 1) ? 1 : 0;
		
		$params = array
		(
			'site_title',
			'site_description',
			'keywords',
			'date_format',
			'structure_recursion_limit',
			'timezone',
			'layout',
			'avatar_maxfilesize',
			'avatar_maxwidth',
			'avatar_maxheight',
			'session_length'	,
			'users_per_page',
			'system_icq_uin',
			'system_icq_password',
			'system_jid',
			'system_jid_password'
		);
		
		foreach($params as $k=>$v)
		{
			if(isset($_POST[$v]))
				$set[$v] = & $_POST[$v];
		}
		
		if(!isset($set['session_length']) || !preg_match(PCREGEXP_INTEGER_NOZERO, $set['session_length']))
			$set['session_length'] = & $this->current_configuration['session_length'];
		if(!isset($set['avatar_maxfilesize']) || !preg_match(PCREGEXP_INTEGER_NOZERO, $set['avatar_maxfilesize']))
			$set['avatar_maxfilesize'] = & $this->current_configuration['avatar_maxfilesize'];
		if(!isset($set['avatar_maxwidth']) || !preg_match(PCREGEXP_INTEGER_NOZERO, $set['avatar_maxwidth']))
			$set['avatar_maxwidth'] = & $this->current_configuration['avatar_maxwidth'];
		if(!isset($set['avatar_maxheight']) || !preg_match(PCREGEXP_INTEGER_NOZERO, $set['avatar_maxheight']))
			$set['avatar_maxheight'] = & $this->current_configuration['avatar_maxheight'];
		if(!isset($set['users_per_page']) || !preg_match(PCREGEXP_INTEGER_NOZERO, $set['users_per_page']))
			$set['users_per_page'] = & $this->current_configuration['users_per_page'];
		if(!isset($set['timezone']) || !preg_match(PCREGEXP_TIMEZONE, $set['timezone']))
			$set['timezone'] = & $this->current_configuration['timezone'];
		if(!isset($set['structure_recursion_limit']) || !preg_match(PCREGEXP_DIGIT, $set['structure_recursion_limit']))
			$set['structure_recursion_limit'] = & $this->current_configuration['structure_recursion_limit'];
		
		foreach($set as $k=>$v)
		{
			$v = $this->atc->db->db_escape($v);
			$sql = 'UPDATE ' . CONFIGURATION_TABLE . ' SET param_value=\'' . $v . '\' WHERE param_name=\'' . $k . '\'' ;
			$this->atc->db->db_query($sql);
		}
		
		$this->atc->log_message(ATCEVENT_GENERAL, 'System settings were changed');
		$this->atc->message($this->atc->lang['message'], $this->atc->lang['settings_saved'], ATCMS_WEB_PATH . '/admin.php', $this->atc->lang['return_to_acp']);
	}
}

?>
