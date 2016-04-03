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

class atcadmin_service
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
	
	function service()
	{
		$do = isset($_GET['do']) ? $_GET['do'] : '';
		switch($do)
		{
			default:
				$service = $this->atc->template('admin/service');
				$this->atc->process_contents($service->ret(), $this->atc->lang['technical_service']);
			break;
			case 'syncronize_avatars':
				$result = $this->atc->syncronize_avatars();
				$result = '<pre>' . ($result == '' ? $this->atc->lang['avatars_are_ok'] : $result) . '</pre>';
				$this->atc->message($this->atc->lang['message'], $result, ATCMS_WEB_PATH . '/admin.php', $this->atc->lang['return_to_acp'], 60);
			break;
			case 'optimize_database':
				$this->atc->db->db_optimize_tables();
				$this->atc->message($this->atc->lang['message'], $this->atc->lang['tables_optimized'], ATCMS_WEB_PATH . '/admin.php', $this->atc->lang['return_to_acp']);
			break;
		}
	}
}

?>