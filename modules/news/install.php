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
define('MODULE_PATH', ATCMS_ROOT . '/modules/news');

class install_module
{
	private function installation_scheme()
	{
		if(file_exists($f = MODULE_PATH . '/schemes/' . DB_DRIVER . '.ini') && is_readable($f))
		{
			return file_get_contents($f);
		}
		else return false;
	}
	
	public function __construct(& $atc)
	{
		if(($scheme = $this->installation_scheme()) === false) return '<b>Error:</b> empty scheme';
		$atc->db->db_execute($scheme);
		
		$cfg = array
		(
			'news_per_page'=>'15',
			'allow_comments'=>'1',
			'comments_per_page'=>'10',
			'news_in_rss'=>'10',
			'comments_order'=>'DESC'
		);
		
		foreach($cfg as $k=>$v)
			$atc->cfgputs('news:' . $k, $v, true);
		
		$atc->register_module('news', true, true);
		echo 'Module installed!';
	}
}

?>