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

class atcadmin_modules
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
	
	/**
	* Проверить наличие установщика модуля и убить скрипт в случае ошибки
	* @retval string имя файла
	*/
	
	private function validate_module_installer()
	{
		isset($_GET['module_name'])
			&& preg_match(PCREGEXP_MODULE_NAME, $_GET['module_name'])
				&& file_exists($f = MODULES_DIRECTORY . '/' . $_GET['module_name'] . '/' . 'install.php')
					|| $this->atc->message($this->atc->lang['error'], $this->atc->lang['module_installer_not_found'], ATCMS_WEB_PATH . '/admin.php?act=modules', $this->atc->lang['go_back']);
		return $f;
	}
	
	/**
	* Проверить наличие... эммм... разустановщика модуля и убить скрипт в случае ошибки
	* @retval string имя файла
	*/
	
	private function validate_module_uninstaller()
	{
		isset($_GET['module_name'])
			&& preg_match(PCREGEXP_MODULE_NAME, $_GET['module_name'])
				&& file_exists($f = MODULES_DIRECTORY . '/' . $_GET['module_name'] . '/' . 'uninstall.php')
					|| $this->atc->message($this->atc->lang['error'], $this->atc->lang['module_deletion_error'], ATCMS_WEB_PATH . '/admin.php?act=modules', $this->atc->lang['go_back']);
		return $f;
	}
	
	/**
	* Список модулей
	*/
	
	public function modules()
	{
		$modules_list = $this->atc->template('admin/modules_list');
		$modules_list->add_tag('MODULES_LIST', '');
		
		for($d = opendir(MODULES_DIRECTORY); (($d_res = readdir($d)) !== false); true)
		{
			if($d_res == '.' || $d_res == '..') continue;
			$m = array();
			if(is_dir(MODULES_DIRECTORY . '/' . $d_res) && preg_match(PCREGEXP_MODULE_NAME, $d_res, $m) && file_exists($fn = MODULES_DIRECTORY . '/' . $d_res . '/' . $d_res . '.php') && is_readable($fn))
			{
				$flag = $this->atc->module_installed($d_res);
				$installed = $flag ? $this->atc->lang['active'] : $this->atc->lang['inactive'];
				$action = $flag ? '<a href="' . ATCMS_WEB_PATH . '/admin.php?act=module_delete&amp;module_name=' . $d_res . '&amp;' . $this->atc->forms->generate_confirmation() . '" onclick="return confirm(\'' . $this->atc->lang['module_uninstall_confirm'] . '\');">' . $this->atc->lang['module_uninstall'] . '</a>' : '<a href="' . ATCMS_WEB_PATH . '/admin.php?act=module_install&amp;module_name=' . $d_res . '">' . $this->atc->lang['module_install'] . '</a>';
				$modules_list->ext_tag('MODULES_LIST', '<tr><td>' . $d_res . '</td><td>' . $installed . '</td><td>' . $action . '</td></tr>');
			}
		}
		closedir($d);
		
		$this->atc->process_contents($modules_list->ret(), $this->atc->lang['modules_management']);
	}
	
	/**
	* Установить модуль
	*/
	
	public function module_install()
	{
		$f = $this->validate_module_installer();
		
		ob_start();
		atcms_local_globals(0);
		require_once $f;
		$inst = new install_module($this->atc);
		atcms_local_globals(1);
		$out = ob_get_contents();
		ob_clean();
		
		$module_install = $this->atc->template('admin/module_install');
		$module_install->add_tag('INFORMATION', $out);
		
		$this->atc->process_contents($module_install->ret(), $this->atc->lang['module_installation']);
	}
	
	/**
	* Удалить модуль
	*/
	
	public function module_delete()
	{
		if(!$this->atc->forms->validate())
		{
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['fatal_oops']);
		}
		
		$f = $this->validate_module_uninstaller();
		
		ob_start();
		atcms_local_globals(0);
		require_once $f;
		$uninst = new uninstall_module($this->atc);
		$out = ob_get_contents();
		atcms_local_globals(1);
		ob_clean();
		
		$this->atc->unregister_module($_GET['module_name']);
		
		$module_uninst = $this->atc->template('admin/module_uninstall');
		$module_uninst->add_tag('INFORMATION', $out);
		
		$this->atc->process_contents($module_uninst->ret(), $this->atc->lang['module_deletion']);
	}
}

?>