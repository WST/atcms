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

class template
{
	private $template_code = ''; //Текст шаблона
	private $template_tags = array(); //Теги
	private $global_tags = array(); //Глобальные теги
	
	/**
	* Чтение файла в строку
	* @param string имя файла
	* @param bool полный путь
	* @param string путь к папке, где искать файл, если путь не полный
	* @retval string результирующая строка
	*/
	
	private function f2s($filename, $fullpath, $template_path)
	{
		return $fullpath ?
			(file_exists ($filename) ? @file_get_contents($filename) : '') :
				(file_exists(ATCMS_ROOT . '/layout/' . $template_path . '/' . $filename.'.htt') ? @file_get_contents(ATCMS_ROOT . '/layout/' . $template_path . '/' . $filename.'.htt') : '');
	}
	
	/**
	* Конструктор
	* @param string имя файла
	* @param string путь к «скину»
	* @param array глобальные шаблонные теги
	* @param bool путь к файлу полный?
	*/
	
	public function __construct($filename, $template_path, $global_tags, $fullpath)
	{
		$this->global_tags = $global_tags;
		$this->template_code = $this->f2s($filename, $fullpath, $template_path);
	}
	
	/**
	* Добавить новый шаблонный тег
	* @param string имя тега
	* @param string значение тега
	*/
	
	public function add_tag($name, $value)
	{
		$this->template_tags[$name] = $value;
	}
	
	/**
	* Расширить существующий или создать новый шаблонный тег
	* @param string имя тега
	* @param string значение тега
	*/
	
	public function ext_tag($name, $value)
	{
		//Инициализирую здесь же
		if(!isset($this->template_tags[$name])) $this->template_tags[$name] = '';
		$this->template_tags[$name] .= $value;
	}
	
	/**
	* Удалить все шаблонные теги
	*/
	
	public function reset_tags()
	{
		$this->template_tags = array();
	}
	
	/**
	* Шаблонный интерпретатор
	* @param string текст шаблона
	* @param array список шаблонных тегов
	* @retval string результат интерпретации
	*/
	
	private function tpl($text, $tags)
	{
		$tags = array_merge($tags, $this->global_tags);
		foreach($tags as $tag_name=>$tag_value)
		{
			while(true)
			{
				$r = 0;
				$l = 0;
				if(($l = strpos($text, '{?' . $tag_name. '}', $r+$l)) !== false && ($r = (strpos($text, '{' . $tag_name . '?}', $l))) !== false && $r = $r-$l)
				{
					$tl = strlen($tag_name) + 3;
					if(empty($tag_value))
					{
						$text = substr_replace($text, '', $l, $r+$tl);
					}
					else
					{
						$text = substr_replace($text, substr($text, $l+$tl, $r-$tl), $l, $r+$tl);
					}
					continue;
				}
				if(($l = strpos($text, '{!' . $tag_name. '}')) !== false && ($r = strpos(substr($text, $l), '{' . $tag_name . '!}')) !== false)
				{
					$tl = strlen($tag_name) + 3;
					if(empty($tag_value))
					{
						$text = substr_replace($text, substr($text, $l+$tl, $r-$tl), $l, $r+$tl);
					}
					else
					{
						$text = substr_replace($text, '', $l, $r+$tl);
					}
					continue;
				}
				break;
			}
			$text = str_replace('{' . $tag_name . '}', $tag_value, $text);
		}
		return $text;
	}
	
	/**
	* Напечатать результат и, если нужно, сбросить теги
	* @param bool требуется ли сбросить теги
	*/
	
	public function out($reset_tags = true)
	{
		echo $this->tpl($this->template_code, $this->template_tags);
		if($reset_tags) $this->template_tags = array();
	}
	
	/**
	* Изрыгнуть результат куда просят и, если нужно, сбросить теги
	* @param bool требуется ли сбросить теги
	* @retval string результат шаблонной интерпретации
	*/
	
	public function ret($reset_tags = true)
	{
		$retval = $this->tpl($this->template_code, $this->template_tags);
		if($reset_tags) $this->template_tags = array();
		return $retval;
	}
}

?>
