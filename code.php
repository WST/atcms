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

define('ATCMS', 'yes');
require './includes/atcms.inc.php';

$atc = new atcmain(0, false, false);
header('Content-Type: image/png');
//header('Content-Type: text/plain');

$sid = empty($_GET['sid']) ? '' : $atc->db->db_escape($_GET['sid']);
$small = isset($_GET['small']);


$sql = 'SELECT code FROM ' . SPAM_TABLE . ' WHERE sid=\'' . $sid . '\' AND stime > ' . (time() - $atc->cfgvar('session_length'));
$c = $atc->db->db_query($sql);
$code = $atc->db->db_numrows($c) == 0 ? 'AT CMS' : $atc->db->db_result($c, 0, 'code');
$atc->db->db_freeresult($c);

$img = $small ? imagecreatetruecolor(80, 22) : imagecreatetruecolor(120, 32);
if(function_exists('imageantialias')) imageantialias($img, true);
$white = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);
imagefill($img, 0, 0, $white);

$textcolors = array();
$flag = function_exists('imagettftext');

if($flag)
{
	for($i=0; $i<6; $i++)
	{
		//array imagettftext ( resource image, float size, float angle, int x, int y, int color, string fontfile, string text )
		$textcolors[$i] = imagecolorallocate($img, mt_rand(0x20, 0x80), mt_rand(0x20, 0x80), mt_rand(0x20, 0x80));
		imagettftext($img, ($small ? 16.0 : 24.0), mt_rand(-20,20), ($small ? $i*12+2 : $i*16+4), ($small ? 16 : 24), $textcolors[$i], ATCMS_ROOT . '/misc/accid___.ttf', $code[$i]);
	}
}
else
{
	//bool imagestring  ( resource $image  , int $font  , int $x  , int $y  , string $string  , int $color  )
	$black = imagecolorallocate($img, 0x00, 0x00, 0x00);
	imagestring($img, 3, 4, 4, $code, $black);
}

$k = mt_rand(0, 255);
mt_srand(time() + (double) microtime() * $k);
$sx = imagesx($img) - 1;
$sy = imagesy($img) - 1;

if($flag)
{
	$dots = $small ? 8 : 64;
	
	foreach($textcolors as $color)
	{
		for($j=0;$j<$dots;$j++)
		{
			$x = mt_rand(0, $sx);
			$y = mt_rand(0, $sy);
			imagesetpixel($img, $x, $y, $color);
		}
	}
	
	for($i=0; $i<4; $i++)
	{
		//bool imageline ( resource image, int x1, int y1, int x2, int y2, int color )
		$x1 = mt_rand(0, $sx);
		$y1 = mt_rand(0, $sy);
		$x2 = mt_rand(0, $sx);
		$y2 = mt_rand(0, $sy);
		imageline($img, $x1, $y1, $x2, $y2, $textcolors[array_rand($textcolors)]);
	}
}

imagepng($img);
imagedestroy($img);

$atc->finalization();
?>