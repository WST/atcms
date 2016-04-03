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

define('PM_ROOT', ATCMS_ROOT . '/modules/private_messaging');

class private_messaging
{
	private $atc;
	private $db;
	private $lang;
	
	private function get_language()
	{
		$lang = array();
		if(!file_exists($f = PM_ROOT . '/languages/' . $this->atc->languages[$this->atc->language] . '.php'))
		{
			// Язык по умолчанию
			require PM_ROOT . '/languages/en.php';
		}
		else
		{
			require $f;
		}
		$this->lang = $lang;
	}
	
	public function __construct(& $atc)
	{
		$this->atc = & $atc;
		$this->db = & $atc->db;
		
		$this->get_language();
		
		$block = $atc->template(PM_ROOT . '/layout/default/pmblock', true);
		
		$atc->add_block($this->lang['private_messaging'], $block->ret());
	}
}

?>