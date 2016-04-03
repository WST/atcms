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

class atcadmin extends atcmain
{
	/**
	* Конструктор
	*/
	
	public function __construct()
	{
		parent::__construct(0, true, true);
		$this->session->admin || $this->general_message(ATCMESSAGE_ACCESS_ADMIN);
	}
	
	/**
	* Обменять местами два элемента в структуре
	* @note взято без изменений из AT CMS 1.4
	* @param int родитель
	* @param int позиция первого элемента
	* @param int позиция второго элемента
	* @param bool признак успешного обмена
	*/
	
	public function syncronize_avatars()
	{
		$retval = '';
		//Собрать ID пользователей с несуществующими аватарами. И для следующего шага запросить все аватары.
		$invalid_avatars = $avatars = array();
		$sql = 'SELECT avatar, id_user FROM ' . USERS_TABLE;
		for($a=$this->db->db_query($sql); $a_res=$this->db->db_fetchassoc($a); true)
		{
			if($a_res['avatar'] == '') continue;
			$avatars[] = $a_res['avatar'];
			if(!file_exists(AVATARS_DIRECTORY . '/' . $a_res['avatar']))
			{
				$invalid_avatars[] = $a_res['id_user'];
				$retval .= str_replace('%1', $a_res['avatar'], $this->lang['avatar_doesnt_exist_and_was_deleted']) . "\n";
			}
		}
		$this->db->db_freeresult($a);
		
		//Удалить из базы левые аватары
		if(count($invalid_avatars) > 0)
		{
			$sql = 'UPDATE ' . USERS_TABLE . ' SET avatar=\'\' WHERE id_user IN (' . implode(', ', $invalid_avatars) . ')';
			$this->db->db_query($sql);
		}
		
		//После необходимости — достаточность, действуем в обратном направлении.
		
		for($d=opendir(AVATARS_DIRECTORY); ($d_res=readdir($d)) !== false; true)
		{
			if($d_res == '.' || $d_res == '..') continue;
			if(preg_match('#^\.#', $d_res)) continue;
			if(!in_array($d_res, $avatars))
			{
				unlink(AVATARS_DIRECTORY . '/' . $d_res);
				$retval .= str_replace('%1', $d_res, $this->lang['unused_avatar_deleted']) . "\n";
			}
		}
		closedir($d);
		
		return $retval;
	}
	
	public function admin_index()
	{
		$index = $this->template('admin/index');
		$index->add_tag('ADMIN_MENU', $this->admin_menu(false));
		$this->process_contents($index->ret(), $this->lang['acp_index']);
	}
}

?>