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
define('MODULE_PATH', ATCMS_ROOT . '/modules/gallery');

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
	
	private function install(& $atc)
	{
		$f = @fopen($fn = ATCMS_ROOT . '/images/testfile.' . CURRENT_TIMESTAMP, 'w+');
		if(!$f) return '<b>Error:</b> ATCMS “images” directory MUST be writable by web server';
		else
		{
			fclose($f);
			unlink($fn);
		}
		
		$f = @fopen($fn = MODULES_DIRECTORY . '/gallery/cache/testfile.' . CURRENT_TIMESTAMP, 'w+');
		if(!$f) return '<b>Error:</b> ATCMS Gallery’s “cache” directory MUST be writable by web server';
		else
		{
			fclose($f);
			unlink($fn);
		}
		
		if(($scheme = $this->installation_scheme()) === false) return '<b>Error:</b> empty scheme';
		$atc->db->db_execute($scheme);
		
		$atc->cfgputs('gallery:thumbnail_width', '160', true);
		$atc->cfgputs('gallery:thumbnail_height', '120', true);
		$atc->cfgputs('gallery:thumbnails_per_row', '3', true);
		$atc->cfgputs('gallery:display_random_photo', '1', true);
		$atc->cfgputs('gallery:enable_lightbox', '1', true);
		$atc->cfgputs('gallery:interpolation', '1', true);
		$atc->register_module('gallery', true, true);
		
		return 'Installed!';
	}
	
	public function __construct(& $atc)
	{
		echo $this->install($atc);
	}
}

?>