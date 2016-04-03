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

defined('ATCMS') || die();

class uninstall_module
{
	public function __construct(& $atc)
	{
		echo 'Deleting tables: ';
		$del = array(DB_TABLES_PREFIX . 'gallery');
		$atc->db->db_delete_table($del);
		echo 'OK<br>';
		echo 'Deleting settings: ';
		$atc->cfgdel('gallery:thumbnail_width', true);
		$atc->cfgdel('gallery:thumbnail_height', true);
		$atc->cfgdel('gallery:thumbnails_per_row', true);
		$atc->cfgdel('gallery:display_random_photo', true);
		$atc->cfgdel('gallery:enable_lightbox', true);
		$atc->cfgdel('gallery:interpolation', true);
		echo 'OK<br>';
		echo 'Module uninstalled.';
	}
}

?>