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

/**
* Обработать схему таблиц и преобразовать её в массив
* @param string схема
* @param bool использовать ли секции
* @retval array обработанная схема
*/

function atcms_parse_scheme_data($scheme, $use_sections = true)
{
	$f = explode("\n", $scheme);
	$s = $m = array();
	foreach($f as $line)
	{
		$line = trim($line);
		if(empty($line) || preg_match('#^;#U', $line)) continue;
		elseif(preg_match('#^\[(.+?)\]$#iU', $line, $m))
		{
			if($use_sections) $s[($current_section = $m[1])] = array();
			else continue;
		}
		elseif(preg_match('#^(.*?)=( *)?(")?(.*?)(")?( *)?$#iU', $line, $m))
		{
			if($use_sections)
			{
				if(!isset($current_section)) continue;
				$s[$current_section][trim($m[1])] = trim($m[4]);
			}
			else $s[trim($m[1])] = trim($m[4]);
		}
	}
	return $s;
}

/**
* Заголовки HTTP
*/

function atcms_http_headers()
{
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Метка срока действия страницы
	header("Cache-Control: no-cache, must-revalidate"); // Настройки кеширования HTTP 1.1
	header("Pragma: no-cache"); // Настройки кеширования HTTP 1.0
	header("Last-Modified: " . date("D, d M Y H:i:s") . " GMT"); // Дата и время последней модификации документа
	header("X-Powered-By: WatchRooster"); // extra-powered by
	
	//header("Content-Type: text/plain;charset=utf-8"); // Тип данных вывода и его кодировка
	//header("Content-Type: text/html;charset=KOI8-R"); // Жёсткие крокозябры, включить если надо кого-то позлить :-)
	header("Content-Type: text/html;charset=utf-8"); // Тип данных вывода и его кодировка
}

/**
* Упаковать или распаковать глобальные переменные
* @author © Arigato, 2005 год
* http://my-cms.jino-net.ru
* @param int флаг (0 — упаковать; 1 — распаковать)
*/

function atcms_local_globals($action)
{
	static $save_glob, $ind;
	if(!isset($ind))
	{
		$ind = 0;
		$save_glob = array();
	}
	switch($action)
	{
		case 0:
			$save_glob[++$ind] = array();
			foreach($GLOBALS as $key=>$val)
			{
				if($key == "GLOBALS" ) continue;
				$save_glob[$ind][$key] = $val;
				//unset($GLOBALS[$key]); //пришлось убрать, чтобы дать модулям доступ к переменным запроса
			}
		break;
		case 1:
			foreach($save_glob[$ind--] as $key=>$val) $GLOBALS[$key] = $val;
		break;
	}
}

/**
* Обработать строку для участия в регулярном выражении
* @param string строка
* @retval string обработанная строка
*/

function atcms_regexp_prepare($str)
{
	return preg_quote($str);
}

/**
* Определить тип изображения
* @param int тип
* @retval string тип в виде строки
*/

function atcms_determine_image_type($type)
{
	switch($type)
	{
		case IMAGETYPE_PNG: $retval = 'png'; break;
		case IMAGETYPE_JPEG: $retval = 'jpg'; break;
		case IMAGETYPE_GIF: $retval = 'gif'; break;
	}
	return $retval;
}

/**
* Показать список временных зон
* @param string временная зона в системном формате
* @retval string список option-тегов
*/

function atcms_timezone_browser($tz)
{
	return '
	<option value="-12"' . ($tz == '-12' ? ' selected' : '') . '>GMT-12</option>
	<option value="-11"' . ($tz == '-11' ? ' selected' : '') . '>GMT-11</option>
	<option value="-10"' . ($tz == '-10' ? ' selected' : '') . '>GMT-10</option>
	<option value="-9"' . ($tz == '-9' ? ' selected' : '') . '>GMT-9</option>
	<option value="-8"' . ($tz == '-8' ? ' selected' : '') . '>GMT-8</option>
	<option value="-7"' . ($tz == '-7' ? ' selected' : '') . '>GMT-7</option>
	<option value="-6"' . ($tz == '-6' ? ' selected' : '') . '>GMT-6</option>
	<option value="-5"' . ($tz == '-5' ? ' selected' : '') . '>GMT-5</option>
	<option value="-4"' . ($tz == '-4' ? ' selected' : '') . '>GMT-4</option>
	<option value="-3.5"' . ($tz == '-3.5' ? ' selected' : '') . '>GMT-3:30</option>
	<option value="-3"' . ($tz == '-3' ? ' selected' : '') . '>GMT-3</option>
	<option value="-2"' . ($tz == '-2' ? ' selected' : '') . '>GMT-2</option>
	<option value="-1"' . ($tz == '-1' ? ' selected' : '') . '>GMT-1</option>
	<option value="0"' . ($tz == '0' ? ' selected' : '') . '>GMT</option>
	<option value="1"' . ($tz == '1' ? ' selected' : '') . '>GMT+1</option>
	<option value="2"' . ($tz == '2' ? ' selected' : '') . '>GMT+2</option>
	<option value="3"' . ($tz == '3' ? ' selected' : '') . '>GMT+3</option>
	<option value="3.5"' . ($tz == '3.5' ? ' selected' : '') . '>GMT+3:30</option>
	<option value="4"' . ($tz == '4' ? ' selected' : '') . '>GMT+4</option>
	<option value="4.5"' . ($tz == '4.5' ? ' selected' : '') . '>GMT+4:30</option>
	<option value="5"' . ($tz == '5' ? ' selected' : '') . '>GMT+5</option>
	<option value="5.5"' . ($tz == '5.5' ? ' selected' : '') . '>GMT+5:30</option>
	<option value="6"' . ($tz == '6' ? ' selected' : '') . '>GMT+6</option>
	<option value="6.5"' . ($tz == '6.5' ? ' selected' : '') . '>GMT+6:30</option>
	<option value="7"' . ($tz == '7' ? ' selected' : '') . '>GMT+7</option>
	<option value="8"' . ($tz == '8' ? ' selected' : '') . '>GMT+8</option>
	<option value="9"' . ($tz == '9' ? ' selected' : '') . '>GMT+9</option>
	<option value="9.5"' . ($tz == '9.5' ? ' selected' : '') . '>GMT+9:30</option>
	<option value="10"' . ($tz == '10' ? ' selected' : '') . '>GMT+10</option>
	<option value="11"' . ($tz == '11' ? ' selected' : '') . '>GMT+11</option>
	<option value="12"' . ($tz == '12' ? ' selected' : '') . '>GMT+12</option>
	<option value="13"' . ($tz == '13' ? ' selected' : '') . '>GMT+13</option>';
}

/**
* Показать список «скинов»
* @param string имя выбранного в списке «скина»
* @retval string список option-тегов
*/

function atcms_layout_browser($selected)
{
	$retval = '';
 	for($d = opendir($f= ATCMS_ROOT . '/layout'); $d_res = readdir($d); true)
	{
		if($d_res == '.' || $d_res == '..') continue;
		if(preg_match('#^\.#', $d_res)) continue; //Будь проклят Subversion
		$retval .= '<option value="' . $d_res . '"' . ($selected == $d_res ? ' selected' : '') . '>' . htmlspecialchars($d_res) . '</option>';
	}
	closedir($d);
	return $retval;
}

function atcms_datatypes_browser($type)
{
	return '
	<option value="' . ATCFORMAT_HTML . '"' . ($type == ATCFORMAT_HTML ? ' selected' : '') . '>HTML</option>
	<option value="' . ATCFORMAT_BBCODE . '"' . ($type == ATCFORMAT_BBCODE ? ' selected' : '') . '>BB-code</option>
	';
}

/**
* Проверить корректность строковой записи IP-адреса
* @author © Василий Триллер, 2007 год
* @param string ip-адрес
* @retval bool признак корректности переданного адреса
*/

function atcms_check_ip($str)
{
	return preg_match('#^([01]?[0-9]{1,2}|2[0-4][0-9]|25[0-5])\.([01]?[0-9]{1,2}|2[0-4][0-9]|25[0-5])\.([01]?[0-9]{1,2}|2[0-4][0-9]|25[0-5])\.([01]?[0-9]{1,2}|2[0-4][0-9]|25[0-5])$#U', $str);
}

?>