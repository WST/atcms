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

class email
{
	private $messages = array();
	private $atc = NULL;
	private $mestpl = NULL;
	private $headers = array();
	
	/**
	* Конструктор класса сообщение
	*/
	
	public function __construct(& $atcmain)
	{
		$this->atc = & $atcmain;
		$this->mestpl = $this->atc->template('mail/email_message');
	}
	
	/**
	* Добавить сообщение в очередь на отправку
	* @param string получатель сообщения
	* @param string тема сообщения
	* @param string текст сообщения
	* @param string MIME-тип данных
	*/
	
	public function add_message($to, $subject, $text, $content_type = '')
	{
		$this->messages[$to]['subject'] = $subject;
		$this->messages[$to]['text'] = $text;
		
		if($content_type !== '') $this->headers[] = 'Content-Type: ' . $content_type . '; charset=utf-8';
	}
	
	/**
	* Применить к сообщению шаблон
	* @param string заголовок (тема) сообщения
	* @param string текст сообщения
	* @retval string обработанное сообщение
	*/
	
	private function email_template($title, $text)
	{
		$this->mestpl->add_tag('TITLE', htmlspecialchars($title));
		$this->mestpl->add_tag('TEXT', $text);
		return $this->mestpl->ret(true);
	}
	
	/**
	* Произвести отправку одного сообщения
	*/
	
	private function email_send($to, $subject, $message)
	{
		$headers = implode("\r\n", $this->headers);
		@mail($to, $subject, $message, $headers);
	}
	
	/**
	* Произвести отправку сообщений и очистить очередь
	*/
	
	public function send()
	{
		foreach($this->messages as $k=>$v)
			$this->email_send($k, $v['subject'], $this->email_template($v['subject'], $v['text']));
		
		$this->messages = array();
	}
}
?>