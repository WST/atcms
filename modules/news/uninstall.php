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

class uninstall_module
{
	public function __construct(& $atc)
	{
		$atc->db->db_delete_table
		(
			array
			(
				DB_TABLES_PREFIX . 'news',
				DB_TABLES_PREFIX . 'news_comments'
			)
		);
		$atc->cfgdel('news:news_per_page', true);
		$atc->cfgdel('news:allow_comments', true);
		$atc->cfgdel('news:comments_per_page', true);
		$atc->cfgdel('news:news_in_rss', true);
		$atc->cfgdel('news:comments_order', true);
		echo 'Module deleted';
	}
}

?>