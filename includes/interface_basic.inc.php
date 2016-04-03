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

function default_style()
{
	echo file_get_contents(dirname(dirname(__FILE__)) . '/misc/system_style.css');
}

function simple_die($message)
{
	header('Content-Type: text/html;charset=utf-8');
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8">
	<title>General error</title>
	<style type="text/css">
	<!--
	<?php default_style(); ?>
	-->
	</style>
</head>
<body>
<div class="body">
<h1>General error</h1>
<p class="warning"><?php echo $message; ?></p>
<p class="sorry">This means that AT CMS installed at this site cannot work properly because of an error in its files or database. You may contact the website administrator and ask him for this trouble. We are sorry for the inconvenience.</p>
</div>
<div class="system">AT&nbsp;CMS <?php echo ATCMS_VERSION; ?> at <?php echo $_SERVER['HTTP_HOST']; ?></div>
</body>
</html>
	<?php
	die();
}

function geterrtypebynum($type)
{
	$retval = 'error';
	switch($type)
	{
		case E_ERROR: $retval = 'E_ERROR'; break;
		case E_WARNING: $retval = 'E_WARNING'; break;
		case E_PARSE: $retval = 'E_PARSE'; break;
		case E_NOTICE: $retval = 'E_NOTICE'; break;
		case E_CORE_ERROR: $retval = 'E_CORE_ERROR'; break;
		case E_CORE_WARNING: $retval = 'E_CORE_WARNING'; break;
		case E_COMPILE_ERROR: $retval = 'E_COMPILE_ERROR'; break;
		case E_COMPILE_WARNING: $retval = 'E_COMPILE_WARNING'; break;
		case E_USER_ERROR: $retval = 'E_USER_ERROR'; break;
		case E_USER_WARNING: $retval = 'E_USER_WARNING'; break;
		case E_USER_NOTICE: $retval = 'E_USER_NOTICE'; break;
		case E_ALL: $retval = 'E_ALL'; break;
		case E_STRICT: $retval = 'E_STRICT'; break;
	}
	return $retval;
}

function _atcms_bug_handler($type, $msg, $file, $line, $context)
{
	simple_die('<b>PHP '. geterrtypebynum($type).'</b>: ' . $msg . ' in ' . $file . ' on line ' . $line . '.');
}

function simple_information($text, $title)
{
	header('Content-Type: text/html;charset=utf-8');
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8">
	<title><?php echo $title ?></title>
	<style type="text/css">
	<!--
	<?php default_style(); ?>
	-->
	</style>
</head>
<body>
<div class="body">
<h1><?php echo $title ?></h1>
<p><?php echo $text; ?></p>
</div>
<div class="system">AT&nbsp;CMS <?php echo ATCMS_VERSION; ?> at <?php echo $_SERVER['HTTP_HOST']; ?></div>
</body>
</html>
	<?php
	die();
}

function puts($str)
{
	echo $str;
}

?>
