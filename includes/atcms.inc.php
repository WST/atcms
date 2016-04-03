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

/**
* 23.02.2008
* Давайте определяться с путями
* С ними уже было столько глюков, что пора уже, наконец, положить этому конец.
* Итак:
* ATCMS_ROOT должно быть абсолютным путём к корню установки и должно оканчиваться БЕЗ!!! слеша
* ATCMS_INCLUDES_PATH должно быть равно ATCMS_ROOT . '/includes'
* ВСЕ!!! остальные пути должны быть построены аналогичным образом
*/

/**
* 22.05.2008
* AT CMS, ты мну заебало :-)
*/

/**
* 24.05.2008
* На помойку yes/no в базе. 1/0 рулит!
*/

/**
* 21.06.2008
* Потеряно самое главное — строгость
* Исправлять не буду, это займёт очень много времени
*/

defined('ATCMS') || die('Error');
define('ATCMS_VERSION', '2.0.2'); //Версия

/// Базовые пути
define('ATCMS_INCLUDES_PATH', $p=dirname(__FILE__));
define('ATCMS_ROOT', dirname($p));


/// Базовые функции вывода
(@file_exists($f = ATCMS_INCLUDES_PATH . '/interface_basic.inc.php') && @is_readable($f))
	|| trigger_error('Cannot open basic interface module', E_USER_ERROR);
require $f;

/// Конфигурационный файл
(@file_exists($f = ATCMS_ROOT . '/config.inc.php') && @is_readable($f))
	|| simple_die('Cannot access configuration file! Check your installation.<br />Click <a href="installer/install.php">here</a> to run installer.');
require $f;

foreach($_GET as $k=>$v) if(!is_string($v)) unset($_GET[$k]);
foreach($_POST as $k=>$v) if(!is_string($v)) unset($_POST[$k]);
foreach($_COOKIE as $k=>$v) if(!is_string($v)) unset($_COOKIE[$k]);
foreach($_REQUEST as $k=>$v) if(!is_string($v)) unset($_REQUEST[$k]);

///Режим отладки
if(defined('DEBUG') && DEBUG)
{
	ini_set('display_errors', 'On');
	ini_set('mysql.trace_mode', 'On');
	error_reporting(E_ALL & E_STRICT); //Отображать все ошибки, включая предупреждения E_NOTICE
	ini_set('register_globals', false); //Не регистрировать глобальные переменные на основе данных GPC
	set_time_limit(5);
	@set_error_handler('_atcms_bug_handler');
}

/// Веб-пути
defined('ATCMS_WEB_PATH')
	|| simple_die('AT CMS web path is undefined. You probably have an error in your config.inc.php.');
define('AVATARS_WEB_PATH', ATCMS_WEB_PATH . '/images/avatars');

///Прочие относительные пути
define('AVATARS_DIRECTORY', ATCMS_ROOT . '/images/avatars');
define('IMAGES_DIRECTORY', ATCMS_ROOT . '/images');
define('DB_SERVER_DRIVERS_DIRECTORY', ATCMS_ROOT . '/includes/db');
define('MODULES_DIRECTORY', ATCMS_ROOT . '/modules');
define('FORMS_DIRECTORY', ATCMS_INCLUDES_PATH . '/forms');

///Компоненты ядра
$includes = array
(
	'regexps' => 'regular expressions module',
	'functions' => 'functions file',
	'template' => 'template class',
	'session' => 'sessions module',
	'bbcode.wscore' => 'WSCore 2.0 BBML processing module',
	'structure' => 'structure processing module',
	'forms' => 'forms processing module'
);
foreach($includes as $k=>$v)
{
	(@file_exists($f = ATCMS_INCLUDES_PATH . '/' . $k . '.inc.php') && @is_readable($f))
		|| simple_die('Cannot access ' . $v);
	require $f;
}

//Префикс — его может не быть в файле конфигурации
defined('DB_TABLES_PREFIX') || define('DB_TABLES_PREFIX', '');

// Форматы данных (текста)
define('ATCFORMAT_PLAIN', 0);
define('ATCFORMAT_HTML', 1);
define('ATCFORMAT_BBCODE', 2);

//Типы поиска
define('ATCSEARCH_OR', 0);
define('ATCSEARCH_AND', 1);

//Общие сообщения об ошибках
define('ATCMESSAGE_ACCESS_USER', 0);
define('ATCMESSAGE_ACCESS_ADMIN', 1);
define('ATCMESSAGE_GENERAL_ERROR', 2);
define('ATCMESSAGE_LANGUAGE_SET', 3);
define('ATCMESSAGE_INCORRECT_SEARCH_QUERY', 4);
define('ATCMESSAGE_USER_DOESNT_EXIST', 5);
define('ATCMESSAGE_WRONG_LANGUAGE', 6);

// Названия файлов COOKIE
define('ATCSESSION_COOKIE_NAME', 'atc_sid');
define('ATCSESSION_AUTOLOGIN_COOKIE_NAME', 'atc_autologin');
define('ATCSESSION_LANGUAGE_COOKIE_NAME', 'atc_language');

// Таблицы
define('CONFIGURATION_TABLE', DB_TABLES_PREFIX . 'config'); //Конфигурационная таблица
define('STRUCTURE_TABLE', DB_TABLES_PREFIX . 'structure'); //Структурная таблица
define('ARTICLES_TABLE', DB_TABLES_PREFIX . 'articles'); //Таблица статей
define('USERS_TABLE', DB_TABLES_PREFIX . 'users'); //Таблица пользователей
define('MODULES_TABLE', DB_TABLES_PREFIX . 'modules'); //Таблица модулей
define('EMOTICONS_TABLE', DB_TABLES_PREFIX . 'emoticons'); //Таблица смайликоFF
define('SPAM_TABLE', DB_TABLES_PREFIX . 'spam'); //Таблица антиспама
define('AUTOLOGIN_TABLE', DB_TABLES_PREFIX . 'autologin'); //Таблица автологинов
define('LANGUAGES_TABLE', DB_TABLES_PREFIX . 'languages');
define('TAGS_TABLE', DB_TABLES_PREFIX . 'tags');
define('FORMS_TABLE', DB_TABLES_PREFIX . 'forms');
define('SESSIONS_TABLE', DB_TABLES_PREFIX . 'sessions');
define('EVENTLOG_TABLE', DB_TABLES_PREFIX . 'events');

//Текущее время
define('CURRENT_TIMESTAMP', time());

//Типы системных событий
define('ATCEVENT_GENERAL', 1); //Байда всякая
define('ATCEVENT_ERROR', 2); //Ошибки
define('ATCEVENT_ACHTUNG', 3); //Ахтунги

// Ггггггг

class atcmain
{
	// Внутренние члены класса
	private $cfg = array(); //Конфигурационный массив
	private $blocks = array();
	private $global_tags = array();
	private $i = 0; //Переменная специально для деления на два! :-)
	private $emo = array();
	
	//Общедоступные члены класса
	public $modules = array();
	public $id_modules = array();
	public $execution_start = 0.0; //Начало выполнения скрипта
	public $current_page = 0; //Текущая страница структуры
	public $lang = array();
	public $language = 0;
	public $languages = array();
	public $sysconfig = array();
	
	//Системные модули (общедоступные и нет)
	private $bbcode = NULL;
	public $db = NULL; //разъём для соединения с MySQL
	public $session = NULL; //Сессия
	//public $mailer = NULL;
	//public $jabber = NULL;
	//public $icq = NULL;
	public $structure = NULL; //Структура
	public $forms = NULL; //Обработчик форм
	
	/**
	* Добавить глобальный тег (для шаблонного интерпретатора)
	* @param string имя тега
	* @param string значение тега
	*/
	
	public function add_global_tag($name, $value)
	{
		$this->global_tags[$name] = $value;
	}
	
	protected function admin_menu($simple = true)
	{
		$system_elements = array('structure', 'settings', 'modules', 'tags', 'users', 'languages', 'service', 'logs');
		$sql = 'SELECT module_name FROM ' . MODULES_TABLE . ' WHERE admin_interface=1';
		$m=$this->db->db_query($sql); 
		if($simple)
		{
			$retval = '';
			$i = 0;
			$n = count($system_elements);
			foreach($system_elements as $k=>$v)
			{
				$retval .= '<a href="' . ATCMS_WEB_PATH . '/admin.php?act=' . $v . '" title="' . $this->lang['admin_' . $v . '_notice'] . '">' . $this->lang['admin_' . $v] . '</a>';
				if(++$i != $n) $retval .= '<br>';
			}
			while($m_res = $this->db->db_fetchassoc($m))
			{
				$title = str_replace('%1', htmlspecialchars($m_res['module_name']), $this->lang['module_administration']);
				$retval .= '<br><a href="' . ATCMS_WEB_PATH . '/modules/' . $m_res['module_name'] . '/admin.php" title="' . $title . '">' . $title . '</a>';
			}
		}
		else
		{
			$retval = '<ul>';
			foreach($system_elements as $k=>$v)
				$retval .= '<li><a href="' . ATCMS_WEB_PATH . '/admin.php?act=' . $v . '" title="' . $this->lang['admin_' . $v . '_notice'] . '">' . $this->lang['admin_' . $v] . '</a></li>';
			
			while($m_res = $this->db->db_fetchassoc($m))
			{
				$title = str_replace('%1', htmlspecialchars($m_res['module_name']), $this->lang['module_administration']);
				$retval .= '<li><a href="' . ATCMS_WEB_PATH . '/modules/' . $m_res['module_name'] . '/admin.php" title="' . $title . '">' . $title . '</a></li>';
			}
			
			$retval .= '<ul>';
		}
		$this->db->db_freeresult($m);
		return $retval;
	}
	
	/**
	* Определить глобальные шаблонные теги
	*/
	
	private function define_global_tags()
	{
		foreach($this->lang as $k=>$v)
		{
			$this->global_tags['LANG_' . strtoupper($k)] = $v;
		}
		
		$this->global_tags['SITE'] = $this->cfgvar('site_title'); //Заголовок сайта
		$this->global_tags['SITE_DESCRIPTION'] = $this->cfgvar('site_description'); //Краткое описание сайта
		$this->global_tags['PATH'] = ATCMS_WEB_PATH . '/layout/' . $this->cfgvar('layout'); //Путь к активному «скину»
		$this->global_tags['WEB_PATH'] = ATCMS_WEB_PATH;
		$this->global_tags['POWEREDBY'] = 'Powered by AT&nbsp;CMS ' . ATCMS_VERSION . '<br />©&nbsp;Ilja&nbsp;<a href="http://the1st.net.ru">“WST”</a>&nbsp;Averkov&nbsp;(2007–2008)'; //Информация о версии
		$this->global_tags['MODULE_DEBUG'] = ''; //Отладочная информация внешних модулей
		$this->global_tags['ADDITIONAL_HTML_HEADERS'] = ''; //Дополнительные HTML-теги «шапки» страницы
		$this->global_tags['AVATAR'] = $this->user_avatar($this->session->avatar); //Аватар активного пользователя
		$this->global_tags['FORMAT'] = atcms_datatypes_browser(0);
		$this->global_tags['FORMAT_HTML'] = atcms_datatypes_browser(ATCFORMAT_HTML);
		$this->global_tags['FORMAT_BBCODE'] = atcms_datatypes_browser(ATCFORMAT_BBCODE);
		$this->global_tags['NOW'] = CURRENT_TIMESTAMP;
		$this->global_tags['CAPTCHA'] = $this->session->captcha(false);
		$this->global_tags['SMALL_CAPTCHA'] = $this->session->captcha(true);
	}
	
	/**
	* Получить установленные языки в виде нумерованного массива и записать в $languages
	*/
	
	private function get_languages()
	{
		$sql = 'SELECT * FROM ' . LANGUAGES_TABLE;
		for($l=$this->db->db_query($sql); $l_res=$this->db->db_fetchassoc($l); true)
		{
			$this->languages[$l_res['id_language']] = $l_res['file'];
		}
		$this->db->db_freeresult($l);
	}
	
	/**
	* Получить установленные языки в виде набора option-ов для HTML
	* @retval string список установленных языков
	*/
	
	public function get_languages_list($s = 0, $show_all=false)
	{
		$sel = $s==0 ? ' selected' : '';
		$retval = $show_all ? '<option value="0"' . $sel . '>' . $this->lang['all_languages'] . '</option>' : '';
		foreach($this->languages as $k=>$v)
		{
			$sel = $k == $s ? ' selected' : '';
			$retval .= '<option value="' . $k . '"' . $sel . '>' . $v . '</option>';
		}
		return $retval;
	}
	
	/**
	* Получить ID требуемого языка для внутреннего использования
	*/
	
	private function process_language()
	{
		if($this->session->language != 0 && isset($this->languages[$this->session->language]))
		{
			$this->language = $this->session->language;
			require ATCMS_ROOT . '/languages/' . $this->languages[$this->session->language] . '.php';
		}
		else
		{
			if(in_array(LANGUAGE, $this->languages))
			{
				$l = array_flip($this->languages);
				$this->language = $l[LANGUAGE];
				require ATCMS_ROOT . '/languages/' . LANGUAGE . '.php';
			}
			else simple_die('Default language is not installed :-(');
		}
		$this->lang = $lang;
	}
	
	/**
	* Проверить, установлен ли язык с именем $ln
	* @param string Имя языка
	* @retval bool Признак наличия языка в системе
	*/
	
	public function language_installed($ln)
	{
		return in_array($ln, $this->languages);
	}
	
	/**
	* Проверить, установлен ли язык с ID $id_language
	* @param int ID языка
	* @retval bool Признак наличия языка в системе
	*/
	
	public function id_language_installed($id_language)
	{
		return isset($this->languages[$id_language]);
	}
	
	/**
	* Получить ID языка по его имени
	* @param string имя языка
	* @retval int ID языка
	*/
	
	public function get_language_id($ln)
	{
		$l = array_flip($this->languages);
		return $l[$ln];
	}
	
	/**
	* Вывести отладочную информацию модуля (записать её в глобальный тег)
	* @param string текст
	* @param string имя модуля
	*/
	
	private function module_debug($text, $module_name)
	{
		if(defined('DEBUG') && DEBUG)
			$this->global_tags['MODULE_DEBUG'] .= "\nModule " . $module_name . " debug info:\n" . $text;
	}
	
	/**
	* Добавить html-тег в шапку (полезно для RSS-генераторов итп)
	* @param string добавляемая строка
	*/
	
	public function add_html_header($text)
	{
		$this->global_tags['ADDITIONAL_HTML_HEADERS'] .= $text . "\n";
	}
	
	/**
	* Считать конфигурационную переменную или выдать сообщение об отсутствии оной
	* @param string имя конфигурационной переменной
	* @retval string значение конфигурационной переменной
	*/
	
	public function cfgvar($v, $a = null)
	{
		if(is_null($a))
			$a = & $this->cfg;
			
		isset($a[$v]) || simple_die('Required configuration variable ' . htmlspecialchars($v) . ' does not exist!');
		if(preg_match('#^(1|0)$#i', $a[$v]))
			return (bool) $a[$v];
		
		else return $a[$v];
	}
	
	
	/**
	* Создать новую переменную конфигурации или переопределить существующую
	* @param string Имя конфигурационного параметра
	* @param string Значение конфигурационного параметра
	*/
	
	public function cfgputs($param_name, $param_value, $save = false)
	{
		$this->cfg[$param_name] = $param_value;
		if($save)
		{
			$param_name = $this->db->db_escape($param_name);
			$param_value = $this->db->db_escape($param_value);
			$sql = 'REPLACE INTO ' . CONFIGURATION_TABLE . ' (param_name, param_value) VALUES (\'' . $param_name . '\', \'' . $param_value . '\')' ;
			$this->db->db_query($sql);
		}
	}
	
	public function cfgdel($param_name, $save = false)
	{
		unset($this->cfg[$param_name]);
		if($save)
		{
			$sql = 'DELETE FROM ' . CONFIGURATION_TABLE . ' WHERE param_name=\'' . $this->db->db_escape($param_name) . '\'';
			$this->db->db_query($sql);
		}
	}
	
	/**
	* Создать новый шаблон
	* @param string Путь к файлу шаблона
	* @param bool Считать ли путь полным путём относительно вызывающего файла (см руководство по API)
	* @retval object Шаблон
	*/
	
	public function template($template_file, $fullpath=false)
	{
		return new template($template_file, $this->cfgvar('layout'), $this->global_tags, $fullpath);
	}
	
	/**
	* Произвести инициализацию установленных модулей (либо одного выбранного модуля)
	*/
	
	private function initialize_modules($module = null)
	{
		if(!is_null($module))
		{
			$sql = 'SELECT id_module FROM ' . MODULES_TABLE . ' WHERE module_name=\'' . $module . '\'';
			$m = $this->db->db_query($sql);
			if($this->db->db_numrows($m) == 1)
			{
				atcms_local_globals(0);
				require MODULES_DIRECTORY . '/' . $module . '/' . $module . '.php';
				atcms_local_globals(1);
				if(class_exists($module))
				{
					atcms_local_globals(0);
					ob_start();
					$this->modules[$module] = new $module($this);
					$this->id_modules[$this->db->db_result($m, 0, 'id_module')] = & $this->modules[$module];
					$this->module_debug(ob_get_contents(), $module);
					ob_clean();
					atcms_local_globals(1);
				}
				else
				{
					$this->module_debug('Module ' . $mn . ' is not a valid AT CMS module :-(', $mn);
				}
			}
			else
			{
				$this->module_debug('Module ' . $mn . ' is not installed :-(', $mn);
			}
			
			$this->db->db_freeresult($m);
		}
		else
		{
			$sql = 'SELECT * FROM ' . MODULES_TABLE;
			for($m=$this->db->db_query($sql); $m_res=$this->db->db_fetchassoc($m); true)
			{
				file_exists($fn = ATCMS_ROOT . '/modules/' . $m_res['module_name'] . '/' . $m_res['module_name'] . '.php') && @is_readable($fn) || simple_die('Module ' . $m_res['module_name'] . ' isnt readable');
				atcms_local_globals(0);
				require $fn;
				atcms_local_globals(1);
				
				if(class_exists($mn = $m_res['module_name']))
				{
					atcms_local_globals(0);
					ob_start();
					$this->modules[$mn] = new $mn($this);
					$this->id_modules[$m_res['id_module']] = & $this->modules[$mn];
					$this->module_debug(ob_get_contents(), $mn);
					ob_clean();
					atcms_local_globals(1);
				}
				else
				{
					$this->module_debug('Module ' . $mn . ' is not a valid AT CMS module :-(', $mn);
				}
			}
			$this->db->db_freeresult($m);
		}
	}
	
	public function get_modules_list($selected_module)
	{
		$retval = '<option value="0" selected>' . $this->lang['no_module'] . '</option>';
		$sql = 'SELECT * FROM ' . MODULES_TABLE;
		for($m=$this->db->db_query($sql); $m_res=$this->db->db_fetchassoc($m); true)
		{
			$sel = ($m_res['id_module'] == $selected_module) ? ' selected' : '';
			$retval .= '<option value="' . $m_res['id_module'] . '" ' . $sel . '>' . $m_res['module_name'] . '</option>';
		}
		$this->db->db_freeresult($m);
		$retval .= '</option>';
		return $retval;
	}
	
	/**
	* Создать основные «блоки» (меню, поиск по сайту…)
	*/
	
	private function initialize_blocks()
	{
		/// Главное меню
		$this->blocks[0]['menu']['title'] = $this->lang['navigation'];
		$this->blocks[0]['menu']['content'] =
			($s = $this->structure->process_structure(false, 0, 0, $this->current_page, $this->language)) == '' ?
				$this->lang['no_structure'] : $s;
				
		/// Форма входа
		$login_form = $this->template('login_form');
		$this->blocks[1]['login_logout']['title'] = $this->lang['login_logout'];
		$this->blocks[1]['login_logout']['content'] = $login_form->ret();
		
		/// Форма поиска
		$search = $this->template('search_form');
		$this->blocks[1]['search']['title'] = $this->lang['search'];
		$this->blocks[1]['search']['content'] = $search->ret();
		
		/// А-ля ArigatoCMS :)
		if($this->session->admin)
		{
			$this->blocks[0]['admin']['title'] = $this->lang['acp'];
			$this->blocks[0]['admin']['content'] = '';
			if($this->current_page)
			{
				/// Здесь меню быстрых действиефф
				$this->blocks[0]['admin']['title'] = $this->lang['acp'];
				$this->blocks[0]['admin']['content'] .= '<a href="' . ATCMS_WEB_PATH . '/admin.php?act=structure_element_edit&amp;id_element=' . $this->current_page . '">' . $this->lang['edit_this_page'] . '</a><hr>';
			}
			$this->blocks[0]['admin']['content'] .= $this->admin_menu(true);
		}
		
		if(!$this->session->ok && count($this->languages) > 1) //гость, показать ему языки на выбор (если оных больше 1)
		{
			$lang_form = $this->template('lang_form');
			$lang_form->add_tag('LANGUAGES', '');
			
			foreach($this->languages as $k=>$v)
			{
				if($k == $this->language)
					$lang_form->ext_tag('LANGUAGES', '<option value="' . $k . '" selected>' . $v . ' (active)</option>');
				else
					$lang_form->ext_tag('LANGUAGES', '<option value="' . $k . '">' . $v . '</option>');
			}
			
			$this->blocks[0]['language']['title'] = 'Language';
			$this->blocks[0]['language']['content'] = $lang_form->ret();
		}
		
		if($this->cfgvar('display_online_users') && ($this->session->ok || $this->cfgvar('guests_can_view_users_list')) )
		{
			$online_user = $this->template('online_user');
			$online = '';
			$j = 0;
			$sql = 'SELECT name, level, id_user FROM ' . USERS_TABLE . ' WHERE id_user IN (SELECT id_user FROM ' . SESSIONS_TABLE . ' WHERE session_time > ' . (CURRENT_TIMESTAMP - $this->cfgvar('session_length')) . ')';
			$o = $this->db->db_query($sql);
			$n = $this->db->db_numrows($o);
			while($o_res = $this->db->db_fetchassoc($o))
			{
				$online_user->add_tag('ONLINE_NAME', htmlspecialchars($o_res['name']));
				$online_user->add_tag('ONLINE_ID_USER', $o_res['id_user']);
				$online_user->add_tag('ONLINE_LEVEL', $o_res['level']);
				$online .= $online_user->ret();
				if (++$j != $n) $online .= ', ';
			}
			$this->db->db_freeresult($o);
			if($online != '')
			{
				$this->blocks[0]['online']['title'] = $this->lang['active_users'] . ' (' . $n . ')';
				$this->blocks[0]['online']['content'] = '<small>' . $online . '</small>';
			}
		}
	}
	
	/**
	* Функция API для добавления «блока»
	* @param string Отображаемый заголовок «блока»
	* @param string Содержимое «блока»
	*/
	
	public function add_block($title, $content)
	{
		$id = (++$this->i)%2;
		$this->blocks[$id][$b = 'block' . $this->i]['title'] = $title;
		$this->blocks[$id][$b]['content'] = $content;
	}
	
	/**
	* Вызвать метод process_contents класса требуемого модуля
	* @param int Идентификатор нужного модуля
	* @retval string Выводимая модулем информация
	*/
	
	private function process_module($id_module)
	{
		$sql = 'SELECT module_name FROM ' . MODULES_TABLE . ' WHERE id_module = ' . $id_module;
		$m = $this->db->db_query($sql);
		$module_name = $this->db->db_result($m, 0, 'module_name');
		$this->db->db_freeresult($m);
		
		ob_start();
		atcms_local_globals(0);
		$this->modules[$module_name]->process_contents($this->current_page);
		$retval = ob_get_contents();
		atcms_local_globals(1);
		ob_clean();
		
		return $retval;
	}
	
	/**
	* Преобразовать массивы с информацией о «блоках» в собственно контент
	*/
	
	private function process_blocks()
	{
		$this->global_tags['BLOCKS_LEFT'] = '';
		$this->global_tags['BLOCKS_RIGHT'] = '';
		
		$block = $this->template('block');
		$i = 0;
		
		$block->add_tag('TITLE', $this->blocks[0]['menu']['title']);
		$block->add_tag('ID_BLOCK', ++$i);
		$block->add_tag('CONTENT', $this->blocks[0]['menu']['content']);
		$this->global_tags['BLOCKS_LEFT'] .= $block->ret(true);
		
		foreach($this->blocks[0] as $k=>$b)
		{
			if($k == 'menu') continue;
			$block->add_tag('TITLE', $b['title']);
			$block->add_tag('ID_BLOCK', ++$i);
			$block->add_tag('CONTENT', $b['content']);
			$this->global_tags['BLOCKS_LEFT'] .= $block->ret(true);
		}
		foreach($this->blocks[1] as $b)
		{
			$block->add_tag('TITLE', $b['title']);
			$block->add_tag('ID_BLOCK', ++$i);
			$block->add_tag('CONTENT', $b['content']);
			$this->global_tags['BLOCKS_RIGHT'] .= $block->ret(true);
		}
		unset($block);
	}
	
	/**
	* Сгенерировать блок разбивки на страницы
	* @param int Общее число записей
	* @param int Требуемое число записей для одной страницы
	* @param int Текущая страница
	* @param string Ссылка
	* @param string Дополнительные параметры GET
	* @retval string Требуемый блок разбивки
	*/
	
	public function pagebar($element_count, $limit, $current_pg, $href, $getparam)
	{
		$pgbar = $this->template('pagebar');
		if($element_count <= $limit) $pgbar->add_tag('PAGEBAR', '');
		else
		{
			if($current_pg > 0)
			{
				$pgbar->ext_tag('PAGEBAR', '<a href="'.$href.'?p='.($current_pg-1).'&amp;'.$getparam.'">&lt;</a>&nbsp;');
			}
			else
			{
				$pgbar->ext_tag('PAGEBAR', '&lt;&nbsp;');
			}
			$pages_to = floor($element_count / $limit);
			$flag_before = $flag_after = false;
			for($i=0;$i<$pages_to;$i++)
			{
				if($i < 3 || $i > $pages_to-4 || $i == $current_pg)
				{
					if($i != $current_pg)
						$pgbar->ext_tag('PAGEBAR', '<a href="'.$href.'?p='.$i.'&amp;'.$getparam.'">'.$i.'</a>&nbsp;');
					else
						$pgbar->ext_tag('PAGEBAR', $i.'&nbsp;');
				}
				elseif($i < $current_pg && !$flag_before)
				{
					$pgbar->ext_tag('PAGEBAR', '&nbsp;&hellip;&nbsp;');
					$flag_before = true;
				}
				elseif($i > $current_pg && !$flag_after)
				{
					$pgbar->ext_tag('PAGEBAR', '&nbsp;&hellip;&nbsp;');
					$flag_after = true;
				}
			}
			if($current_pg < $pages_to-1)
			{
				$pgbar->ext_tag('PAGEBAR', '<a href="'.$href.'?p='.($current_pg+1).'&amp;'.$getparam.'">&gt;</a>&nbsp;');
			}
			else
			{
				$pgbar->ext_tag('PAGEBAR', '&gt;&nbsp;');
			}
		}
		$retval = $pgbar->ret();
		return $retval;
	}
	
	/**
	* Задать данный элемент структуры в качестве активного
	* @param int Идентификатор нужного элемента
	*/
	
	private function define_current_page($n)
	{
		$this->current_page =
			$this->structure->element_exists($n) ?
				$n : 0;
	}
	
	private function die_406() {
		$err = @ file_get_contents('/opt/nginx/html/errors/406.html');
		header('HTTP/1.0 406 Not Acceptable');
		header('Content-Length: ' . strlen($err));
		header('Content-Type: text/html;charset=utf-8');
		die($err);
	}
	
	/**
	* Конструктор класса ядра
	* @param int Идентификатор элемента структуры (или 0)
	* @param bool запускать ли модули
	* @param bool требуется ли инициализировать mail-класс
	*/
	
	public function __construct($go=0, $initialize_modules=true, $initialize_mailer=true)
	{
		$this->execution_start = array_sum(explode(' ', microtime()));
		
		/*
		$allowed = array('77.232.134');
		$addr = explode('.', trim($a = $_SERVER['REMOTE_ADDR']));
		$addr_begin = $addr[0] . '.' . $addr[1] . '.' . $addr[2];
		
		if($a == '79.165.63.228') return $this->die_406();
		
		if(!in_array($addr_begin, $allowed)) {
			if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows')) {
				$this->die_406();
			}
		}
		*/
		
		/*
		Пришло время для подключения к СУБД.
		Это очень важный момент, потому что все данные ATCMS хранятся в СУБД.
		*/
		
		//Предварительные проверки
		defined('DB_DRIVER') || simple_die('There is no database driver specified in the configuration file!');
		(@file_exists(DB_SERVER_DRIVERS_DIRECTORY . '/' . DB_DRIVER . '.inc.php') && @is_readable(DB_SERVER_DRIVERS_DIRECTORY . '/' . DB_DRIVER . '.inc.php')) || simple_die('Cannot access selected database driver!');
		
		//Включение драйвера СУБД
		require DB_SERVER_DRIVERS_DIRECTORY . '/' . DB_DRIVER . '.inc.php';
		
		//Собственно создание разъёма
		$this->db = new dbserver(DB_SERVER, DB_SERVER_PORT, DB_USER, DB_DATABASE, DB_PASSWORD, 'simple_die'); //Соединение с СУБД посредством драйвера
		
		//Запрос умолчальной конфигурации
		$sql = 'SELECT * FROM ' . CONFIGURATION_TABLE;
		for($c=$this->db->db_query($sql); $c_res = $this->db->db_fetchassoc($c); true)
			$this->sysconfig[$c_res['param_name']]
				= $this->cfg[$c_res['param_name']]
					= $c_res['param_value'];
		$this->db->db_freeresult($c);
		
		//Я тоже ненавижу magic quotes, но shade ненавидит его больше.
		if(get_magic_quotes_gpc())
		{
			foreach($_GET as $k=>$v) $_GET[$k] = stripslashes($v);
			foreach($_POST as $k=>$v) $_POST[$k] = stripslashes($v);
			foreach($_COOKIE as $k=>$v) $_COOKIE[$k] = stripslashes($v);
		}
		
		$this->get_languages();
		
		//Запустить сессию
		$this->session = new atcsession($this);
		
		//Инициализировать языковой модуль
		$this->process_language();
		
		//Модуль для отправки сообщений электронной почты
		//$initialize_mailer && ($this->mailer = new email($this));
		
		//Получить структуру сайта
		//$this->get_structure();
		
		$this->structure = new atcstructure($this);
		
		//Определить активный элемент структуры (откуда брать контент)
		$this->define_current_page($go);
		
		//Определить глобальные шаблонные теги
		$this->define_global_tags();
		
		//Подключить интерпретатор BB-кода
		//$this->bbcode = new bbcode($this, $this->cfgvar('process_emoticons'));
		
		$this->forms = new form_processor($this);
		
		$this->initialize_emoticons();
		
		//Инициализировать блоки
		$this->initialize_blocks();
		
		if($initialize_modules === true)
		{
			$this->initialize_modules();
		}
		elseif(is_string($initialize_modules))
		{
			$this->initialize_modules($initialize_modules);
		}
	}
	
	/**
	* Неавтоматический деструктор
	*/
	
	public function finalization()
	{
		$this->session->delete_old_codes();
		$this->session->delete_old_sids();
		$this->forms->delete_old_forms();
		
		//Отсоединиться от СУБД
		$this->db->db_close();
	}
	
	/**
	* Произвести замену тегов
	* @param string исходная строка
	* @retval string результирующая строка
	*/
	
	private function process_tags($str)
	{
		static $repl;
		
		if(!isset($repl))
		{
			$repl = array();
			$sql = 'SELECT * FROM ' . TAGS_TABLE . ' WHERE language IN (0, ' . $this->language . ')';
			for($t=$this->db->db_query($sql); $t_res=$this->db->db_fetchassoc($t); true)
			{
				$repl[$t_res['replace_from']] = $t_res['replace_to'];
			}
			$this->db->db_freeresult($t);
		}
		
		foreach($repl as $k=>$v)
		{
			$str = str_replace($k, $v, $str);
		}
		
		return $str;
	}
	
	/**
	* Перенаправить текст обработчику BB-кода и вернуть результат
	* @param string Текст для обработки
	* @param bool Интерпретировать ли тег [html]
	* @retval string Результат интерпретирования
	*/
	
	private function bbcode($text, $allow_html_tag = false)
	{
		require_once ATCMS_INCLUDES_PATH . '/bbcode.wscore.inc.php';
		$text = bbcode_pass2($text);
		
		if($this->cfgvar('process_emoticons'))
		{
			foreach($this->emo as $k=>$v)
				$text = str_replace(htmlspecialchars($k), '<img src="' . ATCMS_WEB_PATH . '/images/emoticons/' . $v . '" alt="' . htmlspecialchars($k) . '" title="' . htmlspecialchars($k) . '">', $text);
		}
		
		return $text;
	}
	
	/**
	* Перенаправить текст нужному обработчику в зависимости от указанного типа данных
	* @param string Текст для обработки
	* @param int Тип данных
	* @param bool Интерпретировать ли тег [html]
	* @retval string Результат интерпретирования
	*/
	
	public function process_text($text, $type = ATCFORMAT_BBCODE, $allow_html_tag = false)
	{
		$text = $this->process_tags($text);
		switch($type)
		{
			case ATCFORMAT_BBCODE:
				return $this->bbcode($text, $allow_html_tag);
			break;
			case ATCFORMAT_HTML:
				return $text;
			break;
			case ATCFORMAT_PLAIN:
				return htmlspecialchars($text);
			break;
		}
	}
	
	public function preprocess_text($text, $type = ATCFORMAT_BBCODE, $algo = BBCODE_MODE_BBML)
	{
		
		switch($type)
		{
			case ATCFORMAT_BBCODE:
				return bbcode_pass1($text, $algo);
			break;
			case ATCFORMAT_HTML:
				return $text;
			break;
		}
	}
	
	public function unprocess_text($text, $type = ATCFORMAT_BBCODE)
	{
		switch($type)
		{
			case ATCFORMAT_BBCODE:
				return bbcode_uncode($text);
			break;
			case ATCFORMAT_HTML:
				return htmlspecialchars($text);
			break;
		}
	}
	
	private function initialize_emoticons()
	{
		$sql = 'SELECT * FROM ' . EMOTICONS_TABLE;
		for($e = $this->db->db_query($sql); $e_res = $this->db->db_fetchassoc($e); true)
		{
			$this->emo[$e_res['emoticon_code']] = $e_res['emoticon_file'];
		}
		$this->db->db_freeresult($e);
	}
	
	/**
	* Собрать и вывести содержимое через обработчик вывода
	* @param string Текст для обработки (если активный элемент равен 0)
	* @param string Заголовок страницы (если активный элемент равен 0)
	*/
	
	public function process_contents($argument = '', $title = 'AT CMS 2.0')
	{
		if($this->current_page != 0)
		{
			$sql = 'SELECT title, module, article, substructure, href FROM ' . STRUCTURE_TABLE . ' WHERE id_element = ' . $this->current_page;
			$s = $this->db->db_query($sql);
			$s_res = $this->db->db_fetchassoc($s);
			$this->db->db_freeresult($s);
			
			if($s_res['href'] == '')
			{
				$title = $this->process_text($s_res['title'], ATCFORMAT_HTML);
				$text_data = '';
				
				$page = $this->template('page');
				
				$page->add_tag('ELEMENT_TITLE', $title);
				if($s_res['article'] != 0)
				{
					$sql = 'SELECT * FROM ' . ARTICLES_TABLE . ' WHERE id_article = ' . $s_res['article'];
					$a = $this->db->db_query($sql);
					$this->db->db_numrows($a) == 0 && simple_die('Fatal error while processing structure element');
					$a_res = $this->db->db_fetchassoc($a);
					$this->db->db_freeresult($a);
					
					$text = $this->process_text($a_res['full_version'], $a_res['type'], true);
					
					$page->add_tag('ART_CONTENTS',  $text);
					unset($article);
				}
				else
				{
					$page->add_tag('ART_CONTENTS',  '');
				}
				
				$page->add_tag('SUBSTRUCTURE', $s_res['substructure'] == 0 ? '' : $this->structure->process_substructure($this->current_page));
				$page->add_tag('MODULE_CONTENTS', $s_res['module'] == 0 ? '' : $this->process_module($s_res['module']));
			
				$text_data = $page->ret();
				unset($page);
			}
			else
			{
				return header('Location: ' . $s_res['href']);
			}
		}
		elseif($argument !== '') //Если элемент структуры равен 0 и аргумент не пуст
		{
			$text_data = $argument;
		}
		else //Аргумент пуст, текущий элемент структуры равен 0
		{
			$sql = 'SELECT id_element FROM ' . STRUCTURE_TABLE . ' WHERE parent=0 AND language=' . $this->language . ' ORDER BY pos ASC ' . $this->db->db_limit(0, 1);
			$g = $this->db->db_query($sql);
			if($this->db->db_numrows($g) == 0) //Нет страниц для отображения
			{
				if($this->session->admin) //Админ уже вошёл, нефиг ему капать на мозги заново
				{
					header('Location: ' . ATCMS_WEB_PATH . '/admin.php'); //Перенаправить в панель администрирования
					$this->finalization();
					die();
				}
				else
				{
					$this->message($this->lang['warning'], $this->lang['no_index'], '', '');
				}
			}
			else //Да, это главная страница
			{
				$go = $this->db->db_result($g, 0, 'id_element');
				$this->db->db_freeresult($g);
				//return $this->switch_to($go);
				$this->current_page = $go;
				return $this->process_contents($argument, $title);
			}
		}
		
		$this->out( $title, $this->cfgvar('keywords'), $this->cfgvar('site_description'), $text_data  );
	}
	
	/**
	* Собрать и вывести «шапку» сайта
	* @param string Заголовок страницы
	* @param string Ключевые слова
	* @param string Описание
	*/
	
	private function site_header($title, $keywords = '', $description = '')
	{
		$header = $this->template('header');
		$header->add_tag('KEYWORDS', $keywords);
		$header->add_tag('DESCRIPTION', $description);
		$header->add_tag('TITLE', $title);
		$header->out();
		unset($header);
	}
	
	/**
	* Собрать и вывести «подвал» сайта
	*/
	
	private function site_footer()
	{
		$footer = $this->template('footer'); //Шаблон «подвала»
		$footer->add_tag('DB_QUERIES', $this->db->db_queries_count); //Число SQL-запросов
		$generation = (array_sum(explode(' ', microtime())) - $this->execution_start);
		$footer->add_tag('GENERATION', sprintf('%.3f', $generation)); //Время выполнения
		$footer->out(); //Напечатать
		unset($footer); //Очистить
	}
	
	/**
	* Обработчик вывода
	* @param string Заголовок страницы
	* @param string Ключевые слова
	* @param string Описание
	* @param string Содержимое
	*/
	
	private function out($title, $keywords, $description, $text)
	{
		atcms_http_headers();
		$this->add_html_header('<script type="text/javascript" language="javascript" src="' . ATCMS_WEB_PATH . '/misc/utils.js"></script>');
		$this->process_blocks();
		ob_start();
		$this->site_header($title, $keywords, $description);
		echo $text;
		$this->site_footer();
		$result = ob_get_contents();
		ob_clean();
		// Вообще в этом месте можно сделать очень многое :-)
		//echo nl2br(htmlspecialchars($result));
		echo $result;
	}
	
	/**
	* Вывести системное уведомление и отвалиться
	* @param string Заголовок сообщения
	* @param string Текст сообщения
	* @param string Ссылка
	* @param string Текст ссылки
	* @param int Таймаут автоматического перехода по ссылке
	*/
	
	public function message($title, $text, $href='', $link_title='', $timer=5)
	{
		//$this->content_processing || $this->process_blocks();
		
		$message = $this->template('message');
		
		$message->add_tag('TITLE', $title);
		$message->add_tag('TEXT', $text);
		$message->add_tag('HREF', $href);
		$message->add_tag('LINK_TITLE', $link_title);
		
		$this->define_current_page(0);
		$href == '' || $this->add_html_header('<meta http-equiv="refresh" content="' . $timer . ';url=' . $href . '">');
		
		$this->out( $title, $this->cfgvar('keywords'), $this->cfgvar('site_description'), $message->ret()  );
		$this->finalization();
		die();
	}
	
	/**
	* Проверяет, установлен ли модуль
	* @param string имя модуля
	*/
	
	public function module_installed($module_name)
	{
		return (bool) $this->db->db_countrows(MODULES_TABLE, '*', 'module_name=\'' . $module_name . '\'');
	}
	
	/**
	* Проверяет, установлен ли модуль
	* @param int ID модуля
	*/
	
	public function id_module_installed($id_module)
	{
		return (bool) $this->db->db_countrows(MODULES_TABLE, '*', 'id_module=' . $id_module);
	}
	
	/**
	* Зарегистрировать в системном реестре информацию о модуле $module_name (только после успешной установки)
	* @param string имя модуля
	* @param bool модуль может строить содержимое
	* @param bool модуль содержит администраторский интерфейс
	*/
	
	public function register_module($module_name, $mcpc = false, $admin_interface = false)
	{
		$mcpc = $mcpc ? 1:0;
		$admin_interface = $admin_interface ? 1:0;
		$sql = 'INSERT INTO ' . MODULES_TABLE . ' (module_name, mcpc, admin_interface) VALUES (\'' . $module_name . '\', ' . $mcpc . ', ' . $admin_interface . ')';
		$this->db->db_query($sql);
	}
	
	/**
	* Удалить из системного реестра информацию о модуле $module_name
	* Извините за терминологию ArigatoCMS 2.0
	* @param string имя модуля
	*/
	
	public function unregister_module($module_name)
	{
		$sql = 'SELECT id_module FROM ' . MODULES_TABLE . ' WHERE module_name=\'' . $module_name . '\'';
		$m = $this->db->db_query($sql);
		$id_module = $this->db->db_result($m, 0, 'id_module');
		$this->db->db_freeresult($m);
		
		$sql = 'UPDATE ' . STRUCTURE_TABLE . ' SET module=0 WHERE module=' . $id_module;
		$this->db->db_query($sql);
		
		$sql = 'DELETE FROM ' . MODULES_TABLE . ' WHERE module_name=\'' . $module_name . '\'';
		$this->db->db_query($sql);
	}
	
	/**
	* Перенаправить посетителя на другой элемент структуры и сыграть в ящик
	* @param int ID требуемого элемента структуры
	*/
	
	public function switch_to($page)
	{
		header('Location: ' . ATCMS_WEB_PATH . '/index.php?go=' . $page);
		$this->finalization();
		die();
	}
	
	/**
	* Выдаёт панель кнопок BB-кодов, ссылающихся на объект $object
	* @param string имя объекта
	* @retval string код панели
	*/
	
	public function bbcode_toolbar($object)
	{
		$emoticons = '';
		if($this->cfgvar('process_emoticons'))
		{
			foreach($this->emo as $k=>$v)
			{
				$emoticons .= '<img onclick="insertTags(\'' . $object . '\', \'' . addslashes($k) . '\', \'\', \'\');return false;" class="smile" alt="' . htmlspecialchars($k) . '" title="' . htmlspecialchars($k) . '" src="images/emoticons/' . $v . '">';
			}
		}
		$bbcode_toolbar = $this->template('bbcode_toolbar');
		$bbcode_toolbar->add_tag('OBJECT', $object);
		$bbcode_toolbar->add_tag('EMOTICONS', $emoticons);
		return $bbcode_toolbar->ret();
	}
	
	/**
	* Проверить существование пользователя с заданным идентификатором
	* @param int ID пользователя
	* @retval bool признак существования
	* @note NOTE: Не для использования в циклах!!!
	*/
	
	public function id_user_exists($id_user)
	{
		return (bool) $this->db->db_countrows(USERS_TABLE, '*', 'id_user=' . $id_user);
	}
	
	/**
	* Преобразовать размер файла в понятный человеку вид
	* @param int размер в байтах
	* @retval string размер в понятном человеку виде
	*/

	public function format_filesize($s)
	{
		$b = $this->lang['b'];
		if($s>1024)
		{
			$b = $this->lang['kb'];
			$s /= 1024;
		}
		if($s>1024)
		{
			$b = $this->lang['Mb'];
			$s /= 1024;
		}
		if($s>1024)
		{
			$b = $this->lang['Gb'];
			$s /= 1024;
		}
		if($s>1024)
		{
			$b = $this->lang['Tb'];
			$s /= 1024;
		}
		return sprintf('%.2F', $s) . ' ' . $b;
	}
	
	/**
	* Выдаёт линк на аватар по его имени
	* @param string имя аватара
	* @retval string HTML-код аватара
	*/
	
	public function user_avatar($fn)
	{
		if(trim($fn) == '') return '';
		if(file_exists(AVATARS_DIRECTORY . '/' . $fn))
		{
			return '<img src="' . AVATARS_WEB_PATH . '/' . $fn . '" alt="' . $this->lang['avatar'] . '" title="' . $this->lang['avatar'] . '">';
		}
		else return '';
	}
	
	/**
	* Подобрать методом научного тыка наиболее подходящее имя файла для нового аватара
	* @param string тип (расширение имени файла)
	* @retval string свободное имя
	*/
	
	private function generate_avatar_filename($type)
	{
		do
		{
			$fn = $this->session->generate_random_string(28); //Почему 28? А вот захотелось мне так! // © Куприенко Н. Н.
		}
		while(file_exists(AVATARS_DIRECTORY . '/' . $fn . '.' . $type));
		return $fn;
	}
	
	/**
	* Прописать в профиль пользователя аватар, равный $file
	* @param string имя изображения в каталоге аватаров
	* @retval bool признак успешного выполнения
	*/
	
	private function define_avatar($file, $id_user=0)
	{
		$id_user = $id_user==0 ? $this->session->id_user : $id_user;
		$sql = 'SELECT avatar FROM ' . USERS_TABLE . ' WHERE id_user=' . $id_user;
		$a = $this->db->db_query($sql);
		$avatar = $this->db->db_result($a, 0, 'avatar');
		$this->db->db_freeresult($a);
		
		if($avatar != '') @unlink(AVATARS_DIRECTORY . '/' . $avatar);
		
		$sql = 'UPDATE ' . USERS_TABLE . ' SET avatar=\'' . $file . '\' WHERE id_user=' . $id_user;
		$a = $this->db->db_query($sql);
		$retval = (bool) $this->db->db_affected_rows($a);
		
		return $retval;
	}
	
	/**
	* Удалить аватар
	*/
	
	public function delete_avatar($id_user=0)
	{
		$this->define_avatar('', $id_user);
	}
	
	/**
	* Установить аватар из файла в ФС сервера
	* Громоздко, но очень просто :-)
	* @param string путь к файлу изображения
	* @retval mixed true в случае успеха, иначе — строка с описанием ошибки
	*/
	
	public function install_avatar($file, $id_user=0)
	{
		if(!file_exists($file) || !is_readable($file)) return $this->lang['avatar_file_unreadable'];
		$im = getimagesize($file);
		if($im[0] == 0) return $this->lang['avatar_file_wrong_type'];
		$type = atcms_determine_image_type($im[2]);
		
		$maxwidth = $this->cfgvar('avatar_maxwidth');
		$maxheight = $this->cfgvar('avatar_maxheight');
		
		if($im[0] > $maxwidth || $im[1] > $maxheight) //Равенство, разумеется, разрешено
		{
			$fx = 'imagecreatefrom' . $append = ($type == 'jpg' ? 'jpeg' : $type);
			$fy = 'image' . $append;
			$src = $fx($file);
			
			if(($w = $im[0] / $maxwidth) > ($h = $im[1] / $maxheight)) // Если больше
			{
				$destheight = floor($im[1] / $w); //А как Вы себе представляете картинку размером 100 на 64.6435766? :)
				$dest = imagecreatetruecolor($maxwidth, $destheight);
				imagefill($dest, 0, 0, imagecolorallocate($dest, 0xFF, 0xFF, 0xFF));
				if(imagecopyresampled($dest, $src, 0, 0, 0, 0, $maxwidth, $destheight, $im[0], $im[1]))
				{
					imagedestroy($src);
					$fn = $this->generate_avatar_filename($type);
					$f = $fn . '.' . $type;
					if(@$fy($dest, AVATARS_DIRECTORY . '/' . $f))
					{
						imagedestroy($dest);
						$this->define_avatar($f, $id_user);
						return true;
					}
					else
					{
						imagedestroy($dest);
						return $this->lang['avatar_cannot_be_saved'];
					}
				}
				else
				{
					imagedestroy($src);
					imagedestroy($dest);
					return $this->lang['avatar_cannot_be_resized'];
				}
			}
			else //В противном случае
			{
				$destwidth = floor($im[0] / $h);
				$dest = imagecreatetruecolor($destwidth, $maxheight);
				if(imagecopyresampled($dest, $src, 0, 0, 0, 0, $destwidth, $maxheight, $im[0], $im[1]))
				{
					imagedestroy($src);
					$fn = $this->generate_avatar_filename($type);
					$f = $fn . '.' . $type;
					if(@$fy($dest, AVATARS_DIRECTORY . '/' . $f))
					{
						imagedestroy($dest);
						$this->define_avatar($f, $id_user);
						return true;
					}
					else
					{
						imagedestroy($dest);
						return $this->lang['avatar_cannot_be_saved'];
					}
				}
				else
				{
					imagedestroy($src);
					imagedestroy($dest);
					return $this->lang['avatar_cannot_be_resized'];
				}
			}
			// У первокурсников моего факультета в таких местах обычно возникает вопрос: «а если они равны?» :-)
		}
		else
		{
			if(($s = filesize($file)) > $this->cfgvar('avatar_maxfilesize')) return str_replace(array('%1', '%2'), array($this->format_filesize($this->cfgvar('avatar_maxfilesize')), $this->format_filesize($s)), $this->lang['avatar_size_mismatch']);
			$fn = $this->generate_avatar_filename($type);
			$f = $fn . '.' . $type;
			if(@copy($file, AVATARS_DIRECTORY . '/' . $f))
			{
				$this->define_avatar($f, $id_user);
				return true;
			}
			else
			{
				return $this->lang['avatar_cannot_be_saved'];
			}
		}
	}
	
	/**
	* Выдать отформатированную в соответствии с заданным форматом дату
	* @param string формат даты
	* @param int отметка времени
	* @retval string отформатированная дата
	*/
	
	public function date($date_format, $timestamp = CURRENT_TIMESTAMP)
	{
		// AT CMS работает в GMT, поэтому ко времени нужно прибавить смещение временной зоны.
		// Надеюсь, я всё делаю правильно :-|
		/// @todo TODO: сделать заплатку для 'r'
		$tz = (int)($this->cfgvar('timezone'));
		$timestamp = $timestamp + 3600 * $tz;
		$retval = date($date_format, $timestamp);
		//if($date_format == 'r')
			//$retval = preg_replace('#((\+|\-)[0-9]{4})#ieU', '?', $retval);
		return $retval;
	}
	
	/**
	* Выдать стандартное сообщение и «откинуть копыта»
	* @param int тип сообщения
	*/
	
	public function general_message($type = ATCMESSAGE_GENERAL_ERROR)
	{
		switch($type)
		{
			case ATCMESSAGE_ACCESS_USER:
				$this->message($this->lang['access_denied'], $this->lang['login_required'], ATCMS_WEB_PATH . '/index.php', $this->lang['go_index']);
			break;
			case ATCMESSAGE_ACCESS_ADMIN:
				$this->message($this->lang['access_denied'], $this->lang['admin_required'], ATCMS_WEB_PATH . '/index.php', $this->lang['go_index']);
			break;
			case ATCMESSAGE_GENERAL_ERROR:
				$this->message($this->lang['error'], $this->lang['general_error'], ATCMS_WEB_PATH . '/index.php', $this->lang['go_index']);
			break;
			case ATCMESSAGE_LANGUAGE_SET:
				$this->message('Information', 'Language was set successfully', ATCMS_WEB_PATH . '/index.php', 'Go to the index page');
			break;
			case ATCMESSAGE_INCORRECT_SEARCH_QUERY:
				$this->message($this->lang['error'], $this->lang['incorrect_search_query'], ATCMS_WEB_PATH . '/index.php', $this->lang['go_index']);
			break;
			case ATCMESSAGE_USER_DOESNT_EXIST:
				$this->message($this->lang['error'], $this->lang['user_does_not_exist'], ATCMS_WEB_PATH . '/index.php', $this->lang['go_index']);
			break;
			case ATCMESSAGE_WRONG_LANGUAGE:
				$this->message($this->lang['error'], $this->lang['invalid_language'], ATCMS_WEB_PATH . '/admin.php?act=structure', $this->lang['return_to_acp_structure_manager']);
			break;
		}
	}
	
	/**
	* Записать сообщение в системный журнал
	* @param int тип сообщения
	* @param string текст сообщения
	*/
	
	public function log_message($type=ATCEVENT_GENERAL, $message='Unknown event')
	{
		$sql = 'INSERT INTO ' . EVENTLOG_TABLE . ' (type, timestamp, id_user, message) VALUES (' . $type . ', ' . CURRENT_TIMESTAMP . ', ' . $this->session->id_user . ', \'' . $this->db->db_escape($message) . '\')';
		$this->db->db_query($sql);
	}
}

?>
