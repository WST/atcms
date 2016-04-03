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

class gallery
{
	private $atc = NULL;
	public $lang = array();
	private $db = NULL;
	
	public function __construct(& $atc)
	{
		$this->atc = & $atc;
		$this->db = & $atc->db;
		
		define('GALLERY_TABLE', DB_TABLES_PREFIX . 'gallery');
		define('GALLERY_LAYOUT', MODULES_DIRECTORY . '/gallery/layout/default/');
		define('GALLERY_FORMS', MODULES_DIRECTORY . '/gallery/forms');
		define('GALLERY_CACHE', MODULES_DIRECTORY . '/gallery/cache');
		
		$lang = array();
		if(!file_exists($f = ATCMS_ROOT . '/modules/gallery/languages/' . $this->atc->languages[$this->atc->language] . '.php'))
		{
			// Язык по умолчанию
			require ATCMS_ROOT . '/modules/gallery/languages/en.php';
		}
		else
		{
			require $f;
		}
		$this->lang = $lang;
		
		foreach($this->lang as $k=>$v)
		{
			$this->atc->add_global_tag('LANG_GALLERY_' . strtoupper($k), $v);
		}
		
		if($this->atc->cfgvar('gallery:enable_lightbox'))
		{
			define('GALLERY_LIGHTBOX', ATCMS_WEB_PATH . '/modules/gallery/lightbox');
			$this->atc->add_html_header('<link rel="stylesheet" href="' . GALLERY_LIGHTBOX . '/css/lightbox.css" type="text/css" media="screen">');
			$this->atc->add_html_header('<script type="text/javascript" src="' . GALLERY_LIGHTBOX . '/js/prototype.js"></script>');
			$this->atc->add_html_header('<script type="text/javascript" src="' . GALLERY_LIGHTBOX . '/js/scriptaculous.js?load=effects,builder"></script>');
			$this->atc->add_html_header('<script type="text/javascript" src="' . GALLERY_LIGHTBOX . '/js/lightbox.js"></script>');
		}
		
		if($this->atc->cfgvar('gallery:display_random_photo')) $this->display_random_photo();
	}
	
	private function swap_element($val1, $val2)
	{
		if($val1 === $val2) return false;
		$sql = 'SELECT id_image FROM ' . GALLERY_TABLE . ' WHERE thread=' . $this->atc->current_page . ' AND pos=' . $val2;
		$nr_id_q = $this->db->db_query($sql);
		$nr_id = $this->db->db_result($nr_id_q, 0, 'id_image');
		$this->db->db_freeresult($nr_id_q);
		return ($this->db->db_query('UPDATE ' . GALLERY_TABLE . ' SET pos=' . $val2 . ' WHERE thread=' . $this->atc->current_page . ' AND pos=' . $val1) && $this->db->db_query('UPDATE ' . GALLERY_TABLE.' SET pos=' . $val1 . ' WHERE thread=' . $this->atc->current_page . ' AND id_image=' . $nr_id));
	}
	
	private function image_move($direction)
	{
		$sql = 'SELECT pos FROM ' . GALLERY_TABLE . ' WHERE id_image=' . (int)@$_REQUEST['id_image'];
		$p = $this->db->db_query($sql);
		$p_res = $this->db->db_fetchassoc($p);
		$this->db->db_freeresult($p);
		
		if(!isset($p_res['pos']))
		{
			$this->atc->message($this->atc->lang['message'], $this->atc->lang['not_movable'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
		}
		
		switch($direction)
		{
			case 'right':
				$sql = 'SELECT id_image, pos FROM ' . GALLERY_TABLE . ' WHERE thread=' . $this->atc->current_page . ' AND pos>' . $p_res['pos'] . ' ORDER BY pos ASC ' . $this->db->db_limit(0, 1);
			break;
			case 'left':
				$sql = 'SELECT id_image, pos FROM ' . GALLERY_TABLE . ' WHERE thread=' . $this->atc->current_page . ' AND pos<' . $p_res['pos'] . ' ORDER BY pos DESC ' . $this->db->db_limit(0, 1);
			break;
		}
		
		$s = $this->db->db_query($sql);
		$s_res = $this->db->db_fetchassoc($s);
		$this->db->db_freeresult($s);
		
		if(!isset($s_res['pos']))
		{
			$this->db->db_freeresult($s);
			$this->atc->message($this->atc->lang['message'], $this->atc->lang['not_movable'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
		}
		
		if($this->swap_element($p_res['pos'], $s_res['pos']))
		{
			header('Location: ' . ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page);
		}
		else
		{
			$this->atc->message($this->atc->lang['message'], $this->atc->lang['not_movable'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->atc->lang['go_back']);
		}
	}
	
	private function display_random_photo()
	{
		$sql = 'SELECT count(*) AS cnt FROM ' . GALLERY_TABLE . ' WHERE thread IN (SELECT id_element FROM ' . STRUCTURE_TABLE . ' WHERE language=' . $this->atc->language . ')';
		$c = $this->atc->db->db_query($sql);
		$cnt = $this->atc->db->db_result($c, 0, 'cnt');
		$this->atc->db->db_freeresult($c);
		
		if($cnt == 0) return $this->atc->add_block($this->lang['random_photo'], $this->lang['nothing_to_display']);
		
		mt_srand((double) microtime() * 2000000);
		
		$sql = 'SELECT filename, title, description, thread, id_image, cached, cached_filename FROM ' . GALLERY_TABLE . ' WHERE thread IN (SELECT id_element FROM ' . STRUCTURE_TABLE . ' WHERE language=' . $this->atc->language . ') ' . $this->atc->db->db_limit(mt_rand(0, $cnt-1), 1);
		$r = $this->atc->db->db_query($sql);
		$r_res = $this->atc->db->db_fetchassoc($r);
		$this->atc->db->db_freeresult($r);
		
		$title = $this->atc->process_text($r_res['title'], ATCFORMAT_PLAIN, false);
		$description = $this->atc->process_text($r_res['description'], ATCFORMAT_PLAIN, false);
		
		$random_photo = $this->atc->template(GALLERY_LAYOUT . 'random_photo_block.htt', true);
		$random_photo->add_tag('ID_IMAGE', $r_res['id_image']);
		$random_photo->add_tag('IMAGE_TITLE', $title);
		$random_photo->add_tag('IMAGE_FILENAME', $r_res['filename']);
		$random_photo->add_tag('IMAGE_DESCRIPTION', $description);
		$random_photo->add_tag('GO', $r_res['thread']);
		if($r_res['cached'])
		{
			$random_photo->add_tag('IMAGE', '<img alt="' . $title . '" title="' . $description . '" src="' . ATCMS_WEB_PATH . '/modules/gallery/cache/' . $r_res['cached_filename'] . '">');
		}
		else
		{
			$random_photo->add_tag('IMAGE', '<img alt="' . $title . '" title="' . $description . '" src="' . ATCMS_WEB_PATH . '/modules/gallery/resize.php?img=' . $r_res['id_image'] . '">');
		}
		
		$this->atc->add_block($this->lang['random_photo'], $random_photo->ret());
	}
	
	public function delete_thread($thread)
	{
		$sql = 'DELETE FROM ' . GALLERY_TABLE . ' WHERE thread=' . $thread;
		$this->atc->db->db_query($sql);
	}
	
	public function display_gallery()
	{
		$gallery = $this->atc->template(GALLERY_LAYOUT . 'gallery.htt', true);
		$row = $this->atc->template(GALLERY_LAYOUT . 'row.htt', true);
		$photo = $this->atc->template(GALLERY_LAYOUT . 'photo.htt', true);
		$nophoto = $this->atc->template(GALLERY_LAYOUT . 'nophoto.htt', true);
		
		$photo->add_tag('WIDTH', floor(100 / $this->atc->cfgvar('gallery:thumbnails_per_row')) . '%');
		
		$sql = 'SELECT * FROM ' . GALLERY_TABLE . ' WHERE thread=' . $this->atc->current_page . ' ORDER BY pos ASC';
		
		$flag = 0;
		$gallery->add_tag('PHOTO_ROWS', '');
		$gallery->add_tag('GO', $this->atc->current_page);
		for($p = $this->db->db_query($sql); $p_res = $this->db->db_fetchassoc($p); true)
		{
			$title = $this->atc->process_text($p_res['title'], ATCFORMAT_PLAIN, false);
			$description = $this->atc->process_text($p_res['description'], ATCFORMAT_PLAIN, false);
			//$photo->add_tag('IMAGE', '<img alt="' . $title . '" title="' . $description . '" src="' . ATCMS_WEB_PATH . '/modules/gallery/resize.php?file=' . $p_res['filename'] . '">');
			if($p_res['cached'])
			{
				$photo->add_tag('IMAGE', '<img alt="' . $title . '" title="' . $description . '" src="' . ATCMS_WEB_PATH . '/modules/gallery/cache/' . $p_res['cached_filename'] . '">');
			}
			else
			{
				$photo->add_tag('IMAGE', '<img alt="' . $title . '" title="' . $description . '" src="' . ATCMS_WEB_PATH . '/modules/gallery/resize.php?img=' . $p_res['id_image'] . '">');
			}
			$photo->add_tag('ID_IMAGE', $p_res['id_image']);
			$photo->add_tag('FILENAME', $p_res['filename']);
			$photo->add_tag('TITLE', $title);
			$photo->add_tag('DESCRIPTION', $description);
			
			$row->ext_tag('PHOTOS', $photo->ret());
			
			if(++$flag == $this->atc->cfgvar('gallery:thumbnails_per_row'))
			{
				$flag = 0;
				$gallery->ext_tag('PHOTO_ROWS', $row->ret(true));
			}
		}
		$this->db->db_freeresult($p);
		if($flag)
		{
			for($i=$flag; $i<$this->atc->cfgvar('gallery:thumbnails_per_row'); $i++)
				$row->ext_tag('PHOTOS', $nophoto->ret());
			$gallery->ext_tag('PHOTO_ROWS', $row->ret(true));
		}
		$gallery->out();
	}
	
	public function post_new_image($error = '')
	{
		$data = array
		(
			'go'=>$this->atc->current_page,
			'act'=>'post_new_save',
			'title'=>(isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''),
			'description'=>(isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '')
		);
		$form = $this->atc->forms->create(GALLERY_FORMS . '/post_new_image.ini', true, $this->lang, $data, ATCMS_WEB_PATH . '/index.php', 'POST', $error, 'multipart/form-data');
		$form->out();
	}
	
	private function assign_unused_filename($type)
	{
		do
		{
			$s = $this->atc->session->generate_random_string(8);
		}
		while(file_exists(IMAGES_DIRECTORY . '/' . ($retval = $s . '.' . $type)));
		return $retval;
	}
	
	public function post_new_image_save()
	{
		if(!$this->atc->forms->validate())
		{
			return $this->post_new_image($this->atc->lang['oops']);
		}
		
		if(!isset($_POST['title']) || ($title = trim($_POST['title'])) == '') return $this->post_new_image($this->lang['empty_title']);
		if(!isset($_POST['description']) || ($description = trim($_POST['description'])) == '') return $this->post_new_image($this->lang['empty_description']);
		if(!isset($_FILES['image']) || $_FILES['image']['size'] == 0 || !file_exists($_FILES['image']['tmp_name']) || !is_readable($_FILES['image']['tmp_name'])) return $this->post_new_image($this->lang['bad_image_file']);
		$im = getimagesize($_FILES['image']['tmp_name']);
		if($im[0] == 0) return $this->post_new_image($this->lang['not_an_image']);
		$type = atcms_determine_image_type($im[2]);
		if(@copy($_FILES['image']['tmp_name'], IMAGES_DIRECTORY . '/' . ($filename = $this->assign_unused_filename($type))))
		{
			$sql = 'SELECT max(pos)+1 AS newpos FROM ' . GALLERY_TABLE . ' WHERE thread=' . $this->atc->current_page;
			$p = $this->db->db_query($sql);
			$p_res = (int) $this->db->db_result($p, 0, 'newpos');
			$this->db->db_freeresult($p);
			
			$sql = 'INSERT INTO ' . GALLERY_TABLE . ' (thread, title, description, filename, pos) VALUES (' . $this->atc->current_page . ', \'' . $this->db->db_escape($title) . '\', \'' . $this->db->db_escape($description) . '\', \'' . $this->db->db_escape($filename) . '\', ' . $p_res . ')';
			$this->db->db_query($sql);
			$this->atc->message($this->atc->lang['message'], $this->lang['new_image_posted'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->lang['return']);
		}
		else
		{
			return $this->post_new_image($this->lang['file_copying_error']);
		}
	}
	
	public function add_existing_image($error = '')
	{
		$data = array
		(
			'go'=>$this->atc->current_page,
			'act'=>'add_existing_save',
			'title'=>(isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''),
			'description'=>(isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '')
		);
		$form = $this->atc->forms->create(GALLERY_FORMS . '/add_existing_image.ini', true, $this->lang, $data, ATCMS_WEB_PATH . '/index.php', 'POST', $error);
		$form->out();
	}
	
	private function add_existing_image_save()
	{
		if(!$this->atc->forms->validate())
		{
			return $this->post_new_image($this->atc->lang['oops']);
		}
		
		if(!isset($_POST['title']) || ($title = trim($_POST['title'])) == '') return $this->add_existing_image($this->lang['empty_title']);
		if(!isset($_POST['description']) || ($description = trim($_POST['description'])) == '') return $this->add_existing_image($this->lang['empty_description']);
		if(!isset($_POST['path']) || ($path = trim($_POST['path'])) == '' || strpos($path, '..') !== false) return $this->add_existing_image($this->lang['wrong_path']);
		if(!file_exists($f = ATCMS_ROOT . '/images/' . $path) || !is_readable($f)) return $this->add_existing_image($this->lang['no_file']);
		
		$sql = 'SELECT max(pos)+1 AS newpos FROM ' . GALLERY_TABLE . ' WHERE thread=' . $this->atc->current_page;
		$p = $this->db->db_query($sql);
		$p_res = (int) $this->db->db_result($p, 0, 'newpos');
		$this->db->db_freeresult($p);
		
		$sql = 'INSERT INTO ' . GALLERY_TABLE . ' (thread, title, description, filename, pos) VALUES (' . $this->atc->current_page . ', \'' . $this->db->db_escape($title) . '\', \'' . $this->db->db_escape($description) . '\', \'' . $this->db->db_escape($path) . '\', ' . $p_res . ')';
		$this->db->db_query($sql);
		
		$this->atc->message($this->lang['ok'], $this->lang['image_added'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->lang['return']);
	}
	
	private function delete_image($delete_file)
	{
		if($delete_file)
		{
			$sql = 'SELECT filename FROM ' . GALLERY_TABLE . ' WHERE id_image=' . (int) @$_GET['id_image'];
			$f = $this->db->db_query($sql);
			$f_res = $this->db->db_fetchassoc($f);
			$this->db->db_freeresult($f);
			
			if(isset($f_res['filename']))
			{
				if(!@unlink(IMAGES_DIRECTORY . '/' . $f_res['filename']))
				{
					return $this->atc->message($this->lang['ok'], $this->lang['cannot_delete_file'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->lang['return']);
				}
			}
			else
			{
				return $this->atc->message($this->lang['ok'], $this->lang['image_doesnt_exist'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->lang['return']);
			}
		}
		
		$sql = 'DELETE FROM ' . GALLERY_TABLE . ' WHERE id_image=' . (int) @$_GET['id_image'];
		$d = $this->db->db_query($sql);
		return $this->atc->message($this->lang['ok'], $this->lang['image_deleted'], ATCMS_WEB_PATH . '/index.php?go=' . $this->atc->current_page, $this->lang['return']);
	}
	
	public function process_contents($thread)
	{
		switch(@$_REQUEST['act'])
		{
			default:
				return $this->display_gallery();
			break;
			case 'post_new':
				return $this->post_new_image();
			break;
			case 'post_new_save':
				return $this->post_new_image_save();
			break;
			case 'add_existing':
				return $this->add_existing_image();
			break;
			case 'add_existing_save':
				return $this->add_existing_image_save();
			break;
			case 'delete_image':
				return $this->delete_image(false);
			break;
			case 'delete_file':
				return $this->delete_image(true);
			break;
			case 'left':
			case 'right':
				return $this->image_move($_REQUEST['act']);
			break;
		}
	}
	
	public function search($str, $method)
	{
		$re = atcms_regexp_prepare($str);
		switch($method)
		{
			case ATCSEARCH_OR:
				$re = explode(' ', $re);
				$re = '(' . implode('|', $re) . ')';
				$re = $this->atc->db->db_escape($re);
				$str = $this->atc->db->db_escape($str);
			break;
			case ATCSEARCH_AND:
				$re = explode(' ', $re);
				$re = implode('(.*)', $re);
				$re = $this->atc->db->db_escape($re);
				$str = $this->atc->db->db_escape($str);
			break;
		}
		
		$retval = array();
		$i = 0;
		
		$sql = 'SELECT thread, title, description FROM ' . GALLERY_TABLE . ' WHERE title REGEXP (\'' . $re . '\') OR description REGEXP (\'' . $re . '\')';
		
		for($s=$this->atc->db->db_query($sql); $s_res=$this->atc->db->db_fetchassoc($s); true)
		{
			$i++;
			$retval[$i]['description'] = $s_res['description'] == '' ? $lang['no_description'] : htmlspecialchars($s_res['description']);
			$retval[$i]['href'] = ATCMS_WEB_PATH . '/index.php?go=' . $s_res['thread'];
			$retval[$i]['title'] = htmlspecialchars($s_res['title']);
		}
		$this->atc->db->db_freeresult($s);
		return $retval;
	}
}

?>