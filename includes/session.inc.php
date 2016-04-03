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

class atcsession
{
	/**
	* Члены класса сессии
	*/
	
	private $atcmain = NULL;
	private $db = NULL;
	public $admin = false;
	public $user = '';
	public $id_user = 0;
	public $ok = false;
	public $session = '';
	public $language = 0;
	public $avatar = '';
	
	/**
	* Создать случайную строку символов
	* @param int длина генерируемой строки
	* @param bool разрешено ли использовать знак нуля (просто он похож на букву “O”, это не есть хорошо)
	* @retval str результирующая строка
	*/
	
	public function generate_random_string($len = 32, $allchars = true)
	{
		$retval = '';
		mt_srand((double)microtime() * 1000000);
		for($i=0; $i<$len; $i++)
		{
			if($allchars)
			{
				switch(mt_rand(0,2))
				{
					case 0: $retval .= chr(mt_rand(0x30, 0x39)); break;
					case 1: $retval .= chr(mt_rand(0x41, 0x5A)); break;
					case 2: $retval .= chr(mt_rand(0x61, 0x7A)); break;
				}
			}
			else
			{
				$retval .= chr(mt_rand(0x30, 0x39));
			}
		}
		return $retval;
	}
	
	/**
	* Создать случайную строку для ИНН сеанса
	* @retval str SID
	*/
	
	public function generate_session_id()
	{
		do
		{
			$retval = $this->generate_random_string(32);
		}
		while($this->db->db_countrows(SESSIONS_TABLE, '*', 'session=\'' . $retval . '\''));
		return $retval;
	}
	
	/**
	* Продлить сессию (установить новый COOKIE и записать в таблицу пользователей новое время сессии)
	* @note NOTE: Столь активное использование таблицы пользователей, конечно, нежелательно
	* @todo TODO: По возможности стоит сделать отдельную таблицу сеансов
	*/

	private function session_update()
	{
		@setcookie(ATCSESSION_COOKIE_NAME, $this->session, CURRENT_TIMESTAMP+$this->atcmain->cfgvar('session_length'));
		//$sql = 'UPDATE ' . USERS_TABLE . ' SET session_time=' . CURRENT_TIMESTAMP . ' WHERE session=\'' . $this->session . '\' AND ip_address=\'' . $this->get_ip_address() . '\'';
		$sql = 'UPDATE ' . SESSIONS_TABLE . ' SET session_time=' . CURRENT_TIMESTAMP . ' WHERE session=\'' . $this->session . '\' AND ip_address=\'' . $this->get_ip_address() . '\'';
		$this->db->db_query($sql);
	}
	
	/**
	* Получить IP-адрес
	* @todo TODO: см. баг #0000000070
	* @retval string IP-адрес
	*/
	
	public function get_ip_address()
	{
		$x_forwarded_for = (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && atcms_check_ip($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '?'; //Если проверка (см функцию выше) пройдена
		return $_SERVER['REMOTE_ADDR'] . '/' . $x_forwarded_for; //Строка с IP
	}
	
	/**
	* Завершить действительную сессию
	*/
	
	public function session_close()
	{
		//$sql = 'UPDATE ' . USERS_TABLE . ' SET session=\'\', session_time=0 WHERE id_user=' . $this->id_user;
		$sql = 'DELETE FROM ' . SESSIONS_TABLE . ' WHERE session=\'' . $this->session . '\' AND id_user=' . $this->id_user;
		$this->db->db_query($sql);
		setcookie(ATCSESSION_COOKIE_NAME, '', 0x0);
	}
	
	/**
	* Запустить сессию (создать SID, записать его в таблицу, затем передать пользователю в виде COOKIE)
	* @param int идентификатор пользователя
	*/
	
	public function session_start($id_user)
	{
		$sid = $this->generate_session_id();
		$sql = 'INSERT INTO ' . SESSIONS_TABLE . ' (session_time, session, id_user, ip_address) VALUES (' . CURRENT_TIMESTAMP . ', \'' . $sid . '\', ' . $id_user . ', \'' . $this->get_ip_address() . '\')';
		$this->db->db_query($sql);
		setcookie(ATCSESSION_COOKIE_NAME, $sid, CURRENT_TIMESTAMP+$this->atcmain->cfgvar('session_length'));
		//setcookie(ATCSESSION_COOKIE_NAME, $sid, CURRENT_TIMESTAMP+$this->atcmain->cfgvar('session_length'), ATCMS_WEB_PATH);/// wtf °_°
	}
	
	/**
	* Получить специфичные сеансовые настройки
	* @param int идентификатор пользователя
	*/
	
	public function session_continue($id_user)
	{
		$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE id_user=' . $id_user;
		$u = $this->db->db_query($sql);
		$u_res = $this->db->db_fetchassoc($u);
		$this->db->db_freeresult($u);
		
		$this->ok = true;
		$this->admin = ($u_res['level'] == '1');
		$this->user = $u_res['name'];
		$this->id_user = $u_res['id_user'];
		$this->avatar = $u_res['avatar'];
		
		if(!empty($u_res['layout']))
			$this->atcmain->cfgputs('layout', $u_res['layout']);
		
		if(!empty($u_res['date_format']))
			$this->atcmain->cfgputs('date_format', $u_res['date_format']);
		
		$this->atcmain->cfgputs('timezone', $u_res['timezone']);
		if(!empty($u_res['language']))
			$this->language = $u_res['language'];
	}
	
	/**
	* Конструктор класса сессия
	* @param object ссылка на экземпляр ядра
	*/
	
	public function __construct(& $atcmain)
	{
		$this->atcmain = & $atcmain;
		$this->db = & $atcmain->db;
		
		$ip_address = $this->get_ip_address();
		$timezone = $this->atcmain->cfgvar('timezone');
		
		$ok = false;
		//if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $this->language = $this->get_language($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		if(isset($_COOKIE[ATCSESSION_COOKIE_NAME]) && preg_match(PCREGEXP_MD5_HASH, $this->session = $_COOKIE[ATCSESSION_COOKIE_NAME]))
		{
			//$sql = 'SELECT id_user FROM ' . USERS_TABLE . ' WHERE session=\'' . $_COOKIE[ATCSESSION_COOKIE_NAME] . '\' AND session_time > ' . ( CURRENT_TIMESTAMP-$this->atcmain->cfgvar('session_length') ) . '';
			$sql = 'SELECT id_user FROM ' . SESSIONS_TABLE . ' WHERE session=\'' . $this->session . '\' AND session_time > ' . ( CURRENT_TIMESTAMP-$this->atcmain->cfgvar('session_length') ) . '';
			$u = $this->db->db_query($sql);
			if($this->db->db_numrows($u) == 1)
			{
				$id_user = $this->db->db_result($u, 0, 'id_user');
				$this->session_continue($id_user);
				$this->session_update();
			} else $ok = true; //Можно проверять автологин
			$this->db->db_freeresult($u);
		}
		elseif(isset($_COOKIE[ATCSESSION_AUTOLOGIN_COOKIE_NAME]) || $ok)
		{
			$s = unserialize($_COOKIE[ATCSESSION_AUTOLOGIN_COOKIE_NAME]);
			if
			(
				is_array($s)
				&& isset($s['id_user']) && isset($s['hash'])
				&& preg_match(PCREGEXP_INTEGER, $s['id_user']) && preg_match(PCREGEXP_MD5_HASH, $s['hash'])
				&& $this->db->db_countrows(AUTOLOGIN_TABLE, '*', 'hash=\'' . $s['hash'] . '\' AND id_user=\'' . $s['id_user'] . '\'')
			)
			{
				$this->session_start($s['id_user']);
				$this->session_continue($s['id_user']);
				$this->session_update();
			}
		}
		
		/// NOTE: AT CMS всегда работает в GMT; время для конкретной временной зоны вычисляется путём прибавления нужного числа секунд
		putenv('TZ=GMT');
		
		$this->atcmain->add_global_tag('SESSION', $this->ok); // Тег, показывающий, запущен ли сеанс
		$this->atcmain->add_global_tag('ADMIN', $this->admin); // Админский ли он
		$this->atcmain->add_global_tag('USER', htmlspecialchars($this->user)); // И кому принадлежит
		
		if(!$this->ok && isset($_COOKIE[ATCSESSION_LANGUAGE_COOKIE_NAME]) && $this->atcmain->id_language_installed( (int)$_COOKIE[ATCSESSION_LANGUAGE_COOKIE_NAME] ))
			$this->language = (int)$_COOKIE[ATCSESSION_LANGUAGE_COOKIE_NAME];
	}
	
	/**
	* Отключить автоматическую авторизацию
	*/
	
	public function disable_autologin()
	{
		if(!$this->ok) return false;
		setcookie(ATCSESSION_AUTOLOGIN_COOKIE_NAME, '', 0x0);
		$sql = 'DELETE FROM ' . AUTOLOGIN_TABLE . ' WHERE id_user=' . $this->id_user;
		$this->db->db_query($sql);
	}
	
	/**
	* Установить COOKIE автоматической авторизации
	* @param int идентификатор пользователя
	*/
	
	public function set_autologin_cookie($id_user)
	{
		$s = array();
		$s['hash'] = $this->generate_random_string(32);
		$s['id_user'] = $id_user;
		
		setcookie(ATCSESSION_AUTOLOGIN_COOKIE_NAME, serialize($s), 0x7FFFFFFF);
		
		$sql = $this->db->db_countrows(AUTOLOGIN_TABLE, '*', 'id_user=' . $id_user) ?
			'UPDATE ' . AUTOLOGIN_TABLE . ' SET hash=\'' . $s['hash'] . '\' WHERE id_user=' . $id_user :
				'INSERT INTO ' . AUTOLOGIN_TABLE . ' (id_user, hash) VALUES (' . $id_user . ', \'' . $s['hash'] . '\')';
		
		$this->db->db_query($sql);
	}
	
	private function register_captcha_couple()
	{
		$sid = $this->generate_random_string(32);
		$code = $this->generate_random_string(6, false);
		
		$sql = 'INSERT INTO ' . SPAM_TABLE . ' (sid, code, stime) VALUES (\'' . $sid . '\', \'' . $code . '\', \'' . CURRENT_TIMESTAMP . '\')';
		$this->db->db_query($sql);
		
		return array('code'=>$code, 'sid'=>$sid);
	}
	
	public function captcha($small=false)
	{
		static $c;
		if(!isset($c)) $c = $this->register_captcha_couple();
		/// TODO: нужно сохранять это значение, чтобы не спамить впустую в бедную таблицу
		// Либо выделять значение из уже существующих, просто продлевая им срок действия
		// Хотя нужно подумать, это может привести к дыре
		$retval = '<input type="hidden" name="sid" value="' . $c['sid'] . '" style="display: none;">';
		$retval .=
			$small ?
				'<img src ="'. ATCMS_WEB_PATH . '/code.php?sid=' . $c['sid'] . '&amp;small=1" alt="">' :
				'<img src ="'. ATCMS_WEB_PATH . '/code.php?sid=' . $c['sid'] . '" alt="">';
		
		return $retval;
	}
	
	public function validate_captcha()
	{
		if(!isset($_POST['code']) || !isset($_POST['sid']))
		{
			return false;
		}
		
		$_POST['sid'] = $this->db->db_escape($_POST['sid']);
		$_POST['code'] = $this->db->db_escape($_POST['code']);
		
		return (bool) $this->db->db_countrows(SPAM_TABLE, '*', 'sid=\'' . $_POST['sid'] . '\' AND code=\'' . $_POST['code'] . '\'');
	}
	
	public function delete_old_codes()
	{
		//Удалить мусор из таблицы антиспама
		$sql = 'DELETE FROM ' . SPAM_TABLE . ' WHERE stime<' . (CURRENT_TIMESTAMP - $this->atcmain->cfgvar('session_length'));
		$this->db->db_query($sql);
		/// @todo TODO: эту таблицу так легко зафлудить, что накладные расходы постоянно растут.
		//Надо бы делать оптимизацию
	}
	
	public function delete_old_sids()
	{
		$sql = 'DELETE FROM ' . SESSIONS_TABLE . ' WHERE session_time<' . (CURRENT_TIMESTAMP - $this->atcmain->cfgvar('session_length'));
		$this->db->db_query($sql);
	}
}

?>