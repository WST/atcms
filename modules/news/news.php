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

class news
{
	private $atc = NULL;
	private $db = NULL;
	public $lang = array();
	private $num = '';
	private $layout = 'default';
	
	/**
	* Подключить языковой файл
	*/
	
	private function process_language()
	{
		$lang = array();
		if(!file_exists($f = MODULES_DIRECTORY . '/news/languages/' . $this->atc->languages[$this->atc->language] . '.php'))
		{
			require MODULES_DIRECTORY . '/news/languages/en.php';
		}
		else
		{
			require $f;
		}
		$this->lang = $lang;
		
		foreach($this->lang as $k=>$v)
		{
			$this->atc->add_global_tag('LANG_NEWS_' . strtoupper($k), $v);
		}
	}
	
	/**
	* Конструктор
	*/
	
	public function __construct(& $atc)
	{
		$this->atc = & $atc;
		$this->db = & $atc->db;
		
		$layout = $this->atc->cfgvar('layout');
		
		define('NEWS_FORMS', MODULES_DIRECTORY . '/news/forms');
		
		if(@is_dir($f = MODULES_DIRECTORY . '/news/layout/' . $layout))
		{
			$this->layout = $layout;
			define('NEWS_LAYOUT', $f);
		}
		else
		{
			define('NEWS_LAYOUT', MODULES_DIRECTORY . '/news/layout/default');
		}
		define('NEWS_TABLE', DB_TABLES_PREFIX . 'news');
		define('NEWS_CONFIGURATION', DB_TABLES_PREFIX . 'news_config');
		define('NEWS_COMMENTS_TABLE', DB_TABLES_PREFIX . 'news_comments');
		
		$this->process_language();
		
		///Ссылки на ленты
		$sql = 'SELECT id_element, title FROM ' . STRUCTURE_TABLE . ' WHERE module = (SELECT id_module FROM ' . MODULES_TABLE . ' WHERE module_name=\'news\') AND language=' . $this->atc->language;
		for($e = $this->db->db_query($sql); $e_res = $this->db->db_fetchassoc($e); true)
		{
			$this->atc->add_html_header('<link rel="alternate" type="application/rss+xml" title="' . $this->atc->process_text($e_res['title'], ATCFORMAT_HTML) . '" href="' . ATCMS_WEB_PATH . '/modules/news/rss.php?id_element=' . $e_res['id_element'] . '">');
		}
		$this->db->db_freeresult($e);
	}
	
	/**
	* Получить значение конфигурационной переменной или выдать сообщение об её отсутствии
	* средствами ядра AT CMS
	* @param string имя конфигурационной переменной модуля
	* @retval string значение переменной
	*/
	
	public function cfgvar($v)
	{
		return $this->atc->cfgvar('news:' . $v);
	}
	
	/**
	* Получить требуемый шаблон
	* @param string имя шаблона
	* @retval object шаблон
	*/
	
	public function template($name)
	{
		$retval = $this->atc->template(NEWS_LAYOUT . '/' . $name . '.htt', true);
		$retval->add_tag('ALLOW_COMMENTS', $this->cfgvar('allow_comments') ? 'yes' : '');
		$retval->add_tag('NEWS_STYLE', @file_get_contents(NEWS_LAYOUT . '/style.css'));
		$retval->add_tag('NEWS_LAYOUT', ATCMS_WEB_PATH . '/modules/news/layout/' . $this->layout);
		return $retval;
	}
	
	private function tomes($id_message)
	{
		return ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page . '&amp;act=show_message&amp;id_message=' . $id_message;
	}
	
	/// @todo: переписать надо эту функию...
	
	private function show_comments($id_message)
	{
		///Подготовка
		$comments_per_page = $this->cfgvar('comments_per_page');
		$order = $this->cfgvar('comments_order') == 'ASC' ? 'ASC' : 'DESC';
		$p = isset($_GET['p']) && preg_match(PCREGEXP_INTEGER, $_GET['p']) ? $_GET['p'] : 0;
		$start = $p * $comments_per_page;
		$confirm = $this->atc->forms->generate_confirmation();
		
		///Запрос числа комментариев
		$n_res = $this->db->db_countrows(NEWS_COMMENTS_TABLE, '*', 'id_message=' . $id_message);
		
		///Всякие шаблоны
		$comments = $this->template('comments');
		$comments->add_tag('COMMENTS', '');
		$comment = $this->template('comment');
		
		///Имена авторов
		$uinfo = array();
		/// NOTE: да, тут избыточность, но MySQL не поддерживает LIMIT в подзапросе,
		/// а разбивать на 2 запроса мне лень
		$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE id_user IN (SELECT id_user FROM ' . NEWS_COMMENTS_TABLE . ' WHERE id_message=' . $id_message . ')';
		for($u = $this->db->db_query($sql); $u_res = $this->db->db_fetchassoc($u); true)
		{
			//Скинуть в массив $uinfo
			$uinfo[$u_res['id_user']]['id_user'] = $u_res['id_user'];
			$uinfo[$u_res['id_user']]['name'] = $u_res['name'];
			$uinfo[$u_res['id_user']]['signature'] = $u_res['signature'];
			$uinfo[$u_res['id_user']]['jabber'] = $u_res['jabber'];
			$uinfo[$u_res['id_user']]['icq'] = $u_res['icq'] == 0 ? '' : $u_res['icq'];
			$uinfo[$u_res['id_user']]['email'] = $u_res['email'];
			$uinfo[$u_res['id_user']]['hide_email'] = $u_res['hide_email'];
			$uinfo[$u_res['id_user']]['level'] = $u_res['level'];
			$uinfo[$u_res['id_user']]['avatar'] = $u_res['avatar'];
		}
		$this->db->db_freeresult($u);
		
		///Флаг, указывающий на то, что верхнее сообщение пройдено
		$flag = false;
		
		///Основной цикл
		$sql = 'SELECT * FROM ' . NEWS_COMMENTS_TABLE . ' WHERE id_message=' . $id_message . ' ORDER BY datetime ' . $order . ' ' . $this->db->db_limit($start, $comments_per_page);
		for($c = $this->db->db_query($sql); $c_res = $this->db->db_fetchassoc($c); true)
		{
			$comment->add_tag('COMMENT', $this->atc->process_text($c_res['comment'], ATCFORMAT_BBCODE, false));
			$comment->add_tag('DATETIME', $this->atc->date($this->atc->cfgvar('date_format'), $c_res['datetime']));
			
			if(isset($uinfo[$c_res['id_user']]))
			{
				$comment->add_tag('COMMENT_USER', '<a href="' . ATCMS_WEB_PATH . '/vcard.php?id_user=' . $c_res['id_user'] . '">' . $uinfo[$c_res['id_user']]['name'] . '</a>');
				$comment->add_tag('COMMENT_USER_AVATAR', $this->atc->user_avatar($uinfo[$c_res['id_user']]['avatar']));
				$comment->add_tag('SIGNATURE', $this->atc->process_text($uinfo[$c_res['id_user']]['signature'], ATCFORMAT_BBCODE, false));
				$comment->add_tag('ICQ', $uinfo[$c_res['id_user']]['icq']);
				$comment->add_tag('JABBER', $uinfo[$c_res['id_user']]['jabber']);
				$comment->add_tag('COMMENT_USER_LEVEL', $uinfo[$c_res['id_user']]['level'] == 1 ? $this->atc->lang['admin'] : $this->atc->lang['user']);
				$comment->add_tag('EMAIL', $uinfo[$c_res['id_user']]['email']);
				$comment->add_tag('COMMENT_ID_USER', $c_res['id_user']);
			}
			else
			{
				$comment->add_tag('SIGNATURE', '');
				$comment->add_tag('ICQ', '');
				$comment->add_tag('EMAIL', '');
				$comment->add_tag('COMMENT_ID_USER', '0');
				$comment->add_tag('JABBER', '');
				$comment->add_tag('COMMENT_USER', $c_res['guest_name']);
				$comment->add_tag('COMMENT_USER_AVATAR', '');
				$comment->add_tag('COMMENT_USER_LEVEL', $this->atc->lang['guest']);
			}
			
			$actions = '';
			
			if($this->atc->session->id_user == $c_res['id_user'] || $this->atc->session->admin)
			{
				$actions .= '<a href="' . ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page . '&amp;act=edit_comment&amp;id_comment=' . $c_res['id_comment'] . '">' . $this->lang['edit_comment'] . '</a>';
				if(!$flag || $this->atc->session->admin)
				{
					$flag = true;
					$actions .= '&nbsp;|&nbsp;<a href="' . ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page . '&amp;act=delete_comment&amp;id_comment=' . $c_res['id_comment'] . '&amp;' . $confirm . '">' . $this->lang['delete_comment'] . '</a>';
				}
			}
			
			$comment->add_tag('ACTIONS', $actions);
			$comments->ext_tag('COMMENTS', $comment->ret(false));
		}
		$this->db->db_freeresult($c);
		
		///public function pagebar($element_count, $limit, $current_pg, $href, $getparam)
		$pgbar = $this->atc->pagebar($n_res, $comments_per_page, $p, ATCMS_WEB_PATH . '/index.php', 'act=show_message&amp;go=' . $this->atc->current_page . '&amp;id_message=' . $id_message);
		
		$comments->add_tag('PGBAR', $pgbar);
		
		return $comments->ret();
	}
	
	/**
	* Вывести список новостей для заданного элемента структуры
	*/
	
	private function show_news($error = '')
	{
		///Подготовка
		$news_per_page = $this->cfgvar('news_per_page');
		$p = isset($_GET['p']) && preg_match(PCREGEXP_INTEGER, $_GET['p']) ? $_GET['p'] : 0;
		$start = $p * $news_per_page;
		
		///Запрос числа новостей
		$c_res = $this->db->db_countrows(NEWS_TABLE, '*', 'id_element=' . $this->atc->current_page);
		
		
		///Всякие шаблоны
		$news = $this->template('news');
		$message = $this->template('message');
		$news->add_tag('MESSAGES', '');
		$message->add_tag('GO', $this->atc->current_page);
		
		///Имена авторов
		$uinfo = array();
		/// NOTE: да, тут тоже избыточность, но MySQL не поддерживает LIMIT в подзапросе,
		/// а разбивать на 2 запроса мне и это тоже лень :-)
		$sql = 'SELECT name, id_user FROM ' . USERS_TABLE . ' WHERE id_user IN (SELECT id_user FROM ' . NEWS_TABLE . ' WHERE id_element=' . $this->atc->current_page . ')';
		for($u = $this->db->db_query($sql); $u_res = $this->db->db_fetchassoc($u); true)
		{
			//Скинуть в массив $uinfo
			$uinfo[$u_res['id_user']] = $u_res['name'];
		}
		$this->db->db_freeresult($u);
		
		$rinfo = array();
		$sql = 'SELECT id_message, count(*) AS cnt FROM ' . NEWS_COMMENTS_TABLE . ' GROUP BY id_message';
		for($r = $this->db->db_query($sql); $r_res = $this->db->db_fetchassoc($r); true)
		{
			$rinfo[$r_res['id_message']] = $r_res['cnt'];
		}
		$this->db->db_freeresult($r);
		
		///Собственно новости
		$sql = 'SELECT * FROM ' . NEWS_TABLE . ' WHERE id_element=' . $this->atc->current_page . ' ORDER BY datetime DESC ' . $this->db->db_limit($start, $news_per_page);
		for($n = $this->db->db_query($sql); $n_res = $this->db->db_fetchassoc($n); true)
		{
			$message->add_tag('TITLE', $this->atc->process_text($n_res['title'], ATCFORMAT_HTML));
			$message->add_tag('MESSAGE', $this->atc->process_text($n_res['message'], $n_res['type'], true));
			$message->add_tag('DATETIME', $this->atc->date($this->atc->cfgvar('date_format'), $n_res['datetime']));
			$message->add_tag('ID_MESSAGE', $n_res['id_message']);
			
			if(isset($rinfo[$n_res['id_message']]))
			{
				$message->add_tag('COMMENTS_COUNT', $rinfo[$n_res['id_message']]);
			}
			else
			{
				$message->add_tag('COMMENTS_COUNT', '0');
			}
			
			if(isset($uinfo[$n_res['id_user']]))
			{
				$message->add_tag('AUTHOR', '<a href="' . ATCMS_WEB_PATH . '/vcard.php?id_user=' . $n_res['id_user'] . '">' . $uinfo[$n_res['id_user']] . '</a>');
			}
			else
			{
				/// Это может случиться, если пользователь, например, был удалён %)
				$message->add_tag('AUTHOR', $n_res['guest_name']);
			}
			
			$news->ext_tag('MESSAGES', $message->ret(false));
		}
		$this->db->db_freeresult($n);
		
		//public function pagebar($element_count, $limit, $current_pg, $href, $getparam)
		$pgbar = $this->atc->pagebar($c_res, $news_per_page, $p, ATCMS_WEB_PATH . '/index.php', 'go=' . $this->atc->current_page);
		
		$data = array
		(
			'act'=>'newmes',
			'go'=>$this->atc->current_page,
			'title'=>htmlspecialchars(isset($_POST['title']) ? $_POST['title'] : ''),
			'message'=>htmlspecialchars(isset($_POST['message']) ? $_POST['message'] : ''),
			'type'=>atcms_datatypes_browser(isset($_POST['type']) ? $_POST['type'] : ATCFORMAT_BBCODE)
		);
		
		//public function __construct(& $atc, $type, $fullpath, & $langvar, $values, $action, $method, $error='', $enctype='')
		$form = $this->atc->forms->create(NEWS_FORMS . '/newmes.ini', true, $this->lang, $data, ATCMS_WEB_PATH . '/index.php', 'POST', $error);
		
		$news->add_tag('PGBAR', $pgbar);
		$news->add_tag('BBCODE_TOOLBAR', $this->atc->bbcode_toolbar('message', true));
		$news->add_tag('GO', $this->atc->current_page);
		$news->add_tag('FORM', $form->ret());
		if($this->atc->session->admin)
		{
			$news->add_tag('CONFIRM', $this->atc->forms->generate_confirmation());
		}
		else
		{
			$news->add_tag('CONFIRM', '');
		}
		
		$news->out();
	}
	
	private function show_message()
	{
		isset($_GET['id_message'])
			&& preg_match(PCREGEXP_INTEGER, $_GET['id_message'])
				|| $this->atc->message($this->atc->lang['error'], $this->lang['id_message_doesnt_exist']);
		
		///Запросим данное сообщение
		$sql = 'SELECT * FROM ' . NEWS_TABLE . ' WHERE id_message=' . $_GET['id_message'];
		$m = $this->db->db_query($sql);
		if($this->db->db_numrows($m) == 0)
		{
			$this->db->db_freeresult($m);
			$this->atc->message($this->atc->lang['error'], $this->lang['id_message_doesnt_exist']);
		}
		$m_res = $this->db->db_fetchassoc($m);
		$this->db->db_freeresult($m);
		
		///Теперь получим имя его автора (чувак, кстати, будет гостем, если был удалён после публикации новости)
		$sql = 'SELECT name, id_user FROM ' . USERS_TABLE . ' WHERE id_user=' . $m_res['id_user'];
		$u = $this->db->db_query($sql);
		if($this->db->db_numrows($u) == 0)
		{
			$author = $m_res['guest_name'];
		}
		else
		{
			$author = '<a href="' . ATCMS_WEB_PATH . '/vcard.php?id_user=' . $this->db->db_result($u, 0, 'id_user') . '">' . $this->db->db_result($u, 0, 'name') . '</a>';
		}
		$this->db->db_freeresult($u);
		
		$mes = $this->template('message_standalone');
		
		$mes->add_tag('TITLE', $this->atc->process_text($m_res['title'], ATCFORMAT_HTML));
		$mes->add_tag('MESSAGE', $this->atc->process_text($m_res['message'], $m_res['type'], true));
		$mes->add_tag('DATETIME', $this->atc->date($this->atc->cfgvar('date_format'), $m_res['datetime']));
		$mes->add_tag('AUTHOR', $author);
		$mes->add_tag('GO', $this->atc->current_page);
		$mes->add_tag('ID_MESSAGE', $_GET['id_message']);
		$mes->add_tag('COMMENTS', $this->cfgvar('allow_comments') ? $this->show_comments($_GET['id_message']) : '');
		
		$mes->out();
	}
	
	private function newmes()
	{
		///Добавлять новости может только администратор (ага, дизъюнкция очень коммутативна...)
		$this->atc->session->admin || $this->atc->general_message(ATCMESSAGE_ACCESS_ADMIN);
		
		if(!$this->atc->forms->validate())
		{
			return $this->show_news($this->atc->lang['oops']);
		}
		
		///Заголовок нашего сообщения
		if(!isset($_POST['title']) || trim($_POST['title'])=='')
		{
			return $this->show_news($this->lang['empty_title']);
		}
		
		if(!isset($_POST['message']) || trim($_POST['message'])=='')
		{
			return $this->show_news($this->lang['empty_body']);
		}
		
		isset($_POST['type'])
			&& $_POST['type'] == ATCFORMAT_HTML
				|| $_POST['type'] = ATCFORMAT_BBCODE;
				
		$_POST['message'] = $this->atc->preprocess_text($_POST['message'], $_POST['type']);
		
		///Непосредственно занесение данных
		$sql = 'INSERT INTO ' . NEWS_TABLE . ' (id_element, id_user, guest_name, title, message, type, datetime) VALUES (' . $this->atc->current_page . ', ' . $this->atc->session->id_user . ', \'' . $this->atc->session->user . '\', \'' . $this->db->db_escape($_POST['title']) . '\', \'' . $this->db->db_escape($_POST['message']) . '\', ' . $_POST['type'] . ', ' . CURRENT_TIMESTAMP . ')';
		$this->db->db_query($sql);
		$id_message = $this->db->db_insert_id();
		
		$this->atc->message($this->atc->lang['message'], $this->lang['message_saved'], $this->tomes($id_message), $this->atc->lang['go_back']);
	}
	
	private function validate_id($method, $target = 'id_message', $a = NULL)
	{
		$mes = $target == 'id_message' ? 
			$this->lang['id_message_doesnt_exist'] :
				$this->lang['id_comment_doesnt_exist'];
		
		switch($method)
		{
			case 'GET':
				isset($_GET[$target])
					&& preg_match(PCREGEXP_INTEGER, $_GET[$target])
						|| $this->atc->message($this->atc->lang['error'], $mes, ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
				$id = $_GET[$target];
			break;
			case 'POST':
				isset($_POST[$target])
					&& preg_match(PCREGEXP_INTEGER, $_POST[$target])
						|| $this->atc->message($this->atc->lang['error'], $mes, ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
				$id = $_POST[$target];
			break;
			case 'CUSTOM':
				isset($a[$target])
					&& preg_match(PCREGEXP_INTEGER, $a[$target])
						|| $this->atc->message($this->atc->lang['error'], $mes, ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
				$id = $a[$target];
			break;
		}
		
		$c_res = $this->db->db_countrows(($target == 'id_message' ? NEWS_TABLE : NEWS_COMMENTS_TABLE), '*', $target . '=' . $id);
		
		$c_res != 0 || $this->atc->message($this->atc->lang['error'], $mes, ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
	}
	
	private function post_comment($error = '')
	{
		$request = array_merge($_GET, $_POST);
		$this->validate_id('CUSTOM', 'id_message', $request);
		
		$this->cfgvar('allow_comments')
			|| $this->atc->message($this->atc->lang['error'], $this->atc->lang['access_denied'], $this->tomes($request['id_message']), $this->atc->lang['go_back']);
		
		$this->atc->session->ok
			|| $this->atc->general_message(ATCMESSAGE_ACCESS_USER);
		
		$data = array
		(
			'go'=>$this->atc->current_page,
			'act'=>'post_comment_save',
			'id_message'=>$request['id_message'],
			'message'=>htmlspecialchars(isset($request['message']) ? $request['message'] : '')
		);
		
		$form = $this->atc->forms->create(NEWS_FORMS . '/post_comment.ini', true, $this->lang, $data, ATCMS_WEB_PATH . '/index.php', 'POST', $error);
		$form->out();
	}
	
	private function post_comment_save()
	{
		if(!$this->atc->forms->validate())
		{
			return $this->post_comment($this->atc->lang['oops']);
		}
		
		$this->validate_id('POST', 'id_message');
		
		define('RETURN_TO', ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page . '&amp;act=post_comment&amp;id_message=' . $_POST['id_message']);
		
		$this->cfgvar('allow_comments')
			|| $this->atc->message($this->atc->lang['error'], $this->atc->lang['access_denied'], RETURN_TO, $this->atc->lang['go_back']);
		
		$this->atc->session->ok
			|| $this->atc->general_message(ATCMESSAGE_ACCESS_USER);
		
		///Тело сообщения
		if(!isset($_POST['message']) || trim($_POST['message']) == '')
		{
			return $this->post_comment($this->lang['empty_comment_body']);
		}
		
		$_POST['message'] = $this->atc->preprocess_text($_POST['message'], ATCFORMAT_BBCODE);
		
		$sql = 'INSERT INTO ' . NEWS_COMMENTS_TABLE . ' (id_message, id_user, guest_name, datetime, comment) VALUES (' . $_POST['id_message'] . ', ' . $this->atc->session->id_user . ', \'' . $this->db->db_escape($this->atc->session->user) . '\', ' . CURRENT_TIMESTAMP . ', \'' . $this->db->db_escape($_POST['message']) . '\')';
		$this->db->db_query($sql);
		
		$this->atc->message($this->atc->lang['message'], $this->lang['reply_saved'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page . '&amp;act=show_message&amp;id_message=' . $_POST['id_message'] . '#comments', $this->atc->lang['go_back']);
	}
	
	private function delete_comment()
	{
		//$this->atc->session->admin || $this->atc->general_message(ATCMESSAGE_ACCESS_ADMIN);
		
		if(!$this->atc->forms->validate())
		{
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['fatal_oops']);
		}
		
		$this->validate_id('GET', 'id_comment');
		
		$this->cfgvar('allow_comments')
			|| $this->atc->message($this->atc->lang['error'], $this->atc->lang['access_denied'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
		
		$this->atc->session->ok
			|| $this->atc->general_message(ATCMESSAGE_ACCESS_USER);
		
		$sql = 'SELECT id_user, id_message, datetime FROM ' . NEWS_COMMENTS_TABLE . ' WHERE id_comment=' . $_GET['id_comment'];
		$u = $this->db->db_query($sql);
		$u_res = $this->db->db_fetchassoc($u);
		$this->db->db_freeresult($u);
		
		if(!$this->atc->session->admin)
		{
			if($u_res['id_user'] != $this->atc->session->id_user)
				$this->atc->message($this->atc->lang['error'], $this->atc->lang['access_denied'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
			
			///Надо ещё проверить, является ли это сообщение верхним... Тока админ может из середины...
			$sql = 'SELECT max(datetime) AS md FROM ' . NEWS_COMMENTS_TABLE . ' WHERE id_message=' . $u_res['id_message'];
			$d = $this->db->db_query($sql);
			$d_res = (int) $this->db->db_result($d, 0, 'md');
			$this->db->db_freeresult($d);
			
			if($u_res['datetime'] != $d_res)
				$this->atc->message($this->atc->lang['error'], $this->atc->lang['access_denied'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
		}
		
		$sql = 'DELETE FROM ' . NEWS_COMMENTS_TABLE . ' WHERE id_comment=' . $_GET['id_comment'];
		$this->db->db_query($sql);
		
		$this->atc->message($this->atc->lang['message'], $this->lang['comment_deleted'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
	}
	
	private function edit_comment($error = '')
	{
		$request = array_merge($_GET, $_POST);
		$this->validate_id('CUSTOM', 'id_comment', $request);
		
		$sql = 'SELECT * FROM ' . NEWS_COMMENTS_TABLE . ' WHERE id_comment=' . $request['id_comment'];
		$n = $this->db->db_query($sql);
		$n_res = $this->db->db_fetchassoc($n);
		$this->db->db_freeresult($n);
		
		$data = array
		(
			'go'=>$this->atc->current_page,
			'act'=>'edit_comment_save',
			'id_comment'=>$request['id_comment'],
			'comment'=>$this->atc->unprocess_text( (isset($request['comment']) ? $request['comment'] : $n_res['comment']), ATCFORMAT_BBCODE)
		);
		
		$form = $this->atc->forms->create(NEWS_FORMS . '/edit_comment.ini', true, $this->lang, $data, ATCMS_WEB_PATH . '/index.php', 'POST', $error);
		$form->out();
	}
	
	private function edit_comment_save()
	{
		if(!$this->atc->forms->validate())
		{
			return $this->edit_comment($this->atc->lang['oops']);
		}
		
		$this->validate_id('POST', 'id_comment');
		
		$sql = 'SELECT id_user, id_message FROM ' . NEWS_COMMENTS_TABLE . ' WHERE id_comment=' . $_POST['id_comment'];
		$u = $this->db->db_query($sql);
		//$u_res = $this->db->db_result($u, 0, 'id_user');
		$u_res = $this->db->db_fetchassoc($u);
		$this->db->db_freeresult($u);
		
		if(!$this->atc->session->admin && $u_res['id_user'] != $this->atc->session->id_user)
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['access_denied'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
		
		///Тело сообщения
		if(!isset($_POST['comment']) || trim($_POST['comment']) == '')
		{
			return $this->edit_comment($this->lang['empty_comment_body']);
		}
		
		$_POST['comment'] = $this->atc->preprocess_text($_POST['comment'], ATCFORMAT_BBCODE);
		
		///Непосредственно занесение данных
		$sql = 'UPDATE ' . NEWS_COMMENTS_TABLE . ' SET comment=\'' . $this->db->db_escape($_POST['comment']) . '\' WHERE id_comment=' . $_POST['id_comment'];
		$this->db->db_query($sql);
		
		$this->atc->message($this->atc->lang['message'], $this->lang['comment_saved'], $this->tomes($u_res['id_message']), $this->atc->lang['go_back']);
	}
	
	private function delete_message()
	{
		$this->atc->session->admin || $this->atc->general_message(ATCMESSAGE_ACCESS_ADMIN);
		
		if(!$this->atc->forms->validate())
		{
			$this->atc->message($this->atc->lang['error'], $this->atc->lang['fatal_oops']);
		}
		
		$this->atc->session->admin
			|| $this->atc->general_message(ATCMESSAGE_ACCESS_ADMIN);
		
		isset($_GET['id_message'])
			&& preg_match(PCREGEXP_INTEGER, $_GET['id_message'])
				|| $this->atc->message($this->atc->lang['error'], $this->lang['id_message_doesnt_exist'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
				
		$sql = 'DELETE FROM ' . NEWS_COMMENTS_TABLE . ' WHERE id_message=' . $_GET['id_message'];
		$this->db->db_query($sql);
		
		$sql = 'DELETE FROM ' . NEWS_TABLE . ' WHERE id_message=' . $_GET['id_message'];
		$d = $this->db->db_query($sql);
		if($this->db->db_affected_rows($d) == 0)
			$this->atc->message($this->atc->lang['error'], $this->lang['id_message_doesnt_exist'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
		else
			$this->atc->message($this->atc->lang['message'], $this->lang['message_deleted'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
	}
	
	private function edit_message($error = '')
	{
		$this->atc->session->admin
			|| $this->atc->general_message(ATCMESSAGE_ACCESS_ADMIN);
			
		$request = array_merge($_GET, $_POST);
		$this->validate_id('CUSTOM', 'id_message', $request);
		
		
		$sql = 'SELECT title, message, type FROM ' . NEWS_TABLE . ' WHERE id_message=' . $request['id_message'];
		$n = $this->db->db_query($sql);
		$n_res = $this->db->db_fetchassoc($n);
		$this->db->db_freeresult($n);
		
		$data = array
		(
			'act'=>'edit_message_save',
			'go'=>$this->atc->current_page,
			'id_message'=>$request['id_message'],
			'title'=>htmlspecialchars(isset($request['title']) ? $request['title'] : $n_res['title']),
			'message'=>isset($request['message']) ? $request['message'] : $this->atc->unprocess_text($n_res['message'], $n_res['type']),
			'type'=>atcms_datatypes_browser(isset($request['type']) ? $request['type'] : $n_res['type'])
		);
		
		$form = $this->atc->forms->create(NEWS_FORMS . '/edit_message.ini', true, $this->lang, $data, ATCMS_WEB_PATH . '/index.php', 'POST', $error);
		$form->out();
	}
	
	private function edit_message_save()
	{
		if(!$this->atc->forms->validate())
		{
			return $this->edit_message($this->atc->lang['oops']);
		}
		
		$this->atc->session->admin
			|| $this->atc->general_message(ATCMESSAGE_ACCESS_ADMIN);
			
		$this->validate_id('POST', 'id_message');
		
		if(!isset($_POST['title']) || trim($_POST['title']) == '')
		{
			return $this->edit_message($this->lang['empty_title']);
		}
		
		if(!isset($_POST['message']) || trim($_POST['message']) == '')
		{
			return $this->edit_message($this->lang['empty_body']);
		}
		
		isset($_POST['type'])
			&& $_POST['type'] == ATCFORMAT_HTML
				|| $_POST['type'] = ATCFORMAT_BBCODE;
				
		$_POST['message'] = $this->atc->preprocess_text($_POST['message'], $_POST['type']);
		
		///Непосредственно занесение данных
		$sql = 'UPDATE ' . NEWS_TABLE . ' SET message=\'' . $this->db->db_escape($_POST['message']) . '\', type=' . $_POST['type'] . ', title=\'' . $this->db->db_escape($_POST['title']) . '\' WHERE id_message=' . $_POST['id_message'];
		$this->db->db_query($sql);
		
		$this->atc->message($this->atc->lang['message'], $this->lang['message_saved'], $this->tomes($_POST['id_message']), $this->atc->lang['go_back']);
	}
	
	public function delete_thread($thread)
	{
		$sql = 'DELETE FROM ' . NEWS_COMMENTS_TABLE . ' WHERE id_message IN (SELECT id_message FROM ' . NEWS_TABLE . ' WHERE id_element=' . $thread . ')';
		$this->db->db_query($sql);
		
		$sql = 'DELETE FROM ' . NEWS_TABLE . ' WHERE id_element=' . $thread;
		$this->db->db_query($sql);
	}
	
	public function search($str, $method)
	{
		$re = atcms_regexp_prepare($str);
		switch($method)
		{
			case ATCSEARCH_OR:
				$re = explode(' ', $re);
				$re = '(' . implode('|', $re) . ')';
				$re = $this->db->db_escape($re);
				$str = $this->db->db_escape($str);
			break;
			case ATCSEARCH_AND:
				$re = explode(' ', $re);
				$re = implode('(.*)', $re);
				$re = $this->db->db_escape($re);
				$str = $this->db->db_escape($str);
			break;
		}
		
		$retval = array();
		$i = 0;
		
		$sql = 'SELECT id_message, title, message, type, id_element FROM ' . NEWS_TABLE . ' WHERE title REGEXP (\'' . $re . '\') OR message REGEXP (\'' . $re . '\')';
		
		for($s=$this->db->db_query($sql); $s_res=$this->db->db_fetchassoc($s); true)
		{
			$i++;
			$description = $this->atc->process_text($s_res['message'], $s_res['type'], true);
			$retval[$i]['description'] = $description == '' ? $this->atc->lang['no_description'] : $description;
			$retval[$i]['href'] = ATCMS_WEB_PATH . '/index.php?go=' . $s_res['id_element'] . '&amp;act=show_message&amp;id_message=' . $s_res['id_message'];
			$retval[$i]['title'] = htmlspecialchars($s_res['title']);
		}
		$this->db->db_freeresult($s);
		return $retval;
	}
	
	public function process_contents($thread)
	{
		$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
		switch($act)
		{
			default:
				$this->show_news();
			break;
			case 'newmes':
				$this->newmes();
			break;
			case 'show_message':
				$this->show_message();
			break;
			case 'edit_message':
				$this->edit_message();
			break;
			case 'edit_message_save':
				$this->edit_message_save();
			break;
			case 'delete_message':
				$this->delete_message();
			break;
			case 'post':
				$this->post_comment();
			break;
			case 'post_comment_save':
				$this->post_comment_save();
			break;
			case 'delete_comment':
				$this->delete_comment();
			break;
			case 'edit_comment':
				$this->edit_comment();
			break;
			case 'edit_comment_save':
				$this->edit_comment_save();
			break;
		}
	}
}

?>