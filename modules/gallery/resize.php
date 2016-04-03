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

define('ATCMS', 'угу');
require '../../includes/atcms.inc.php';
$atc = new atcmain(0);

function atcms_gallery_spacer()
{
	global $atc;
	$w = $atc->cfgvar('gallery:thumbnail_width');
	$h = $atc->cfgvar('gallery:thumbnail_height');
	header("Content-Type: image/png");
	$img = imagecreatetruecolor($w, $h);
	$white = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);
	$gray = imagecolorallocate($img, 0x99, 0x99, 0x99);
	function_exists('imageantialias') && imageantialias($img, true);
	imagefill($img, 0, 0, $white);
	imagerectangle($img, 0, 0, $w-1, $h-1, $gray);
	imageline($img, 0, 0, $w-1, $h-1, $gray);
	imageline($img, $w-1, 0, 0, $h-1, $gray);
	imagepng($img);
	imagedestroy($img);
	$atc->finalization();
	die();
}

function atcms_gallery_assign_unused_cache_filename($type)
{
	global $atc;
	do
	{
		$s = $atc->session->generate_random_string(8);
	}
	while(file_exists(GALLERY_CACHE . '/' . ($retval = $s . '.' . $type)));
	return $retval;
}

function atcms_gallery_cached($id_image, $filename)
{
	global $atc;
	$sql = 'UPDATE ' . GALLERY_TABLE . ' SET cached=1, cached_filename=\'' . $filename . '\' WHERE id_image=' . $id_image;
	$atc->db->db_query($sql);
}

if(!isset($_GET['img']) || !preg_match(PCREGEXP_INTEGER, $_GET['img'])) atcms_gallery_spacer();

$sql = 'SELECT filename, cached, cached_filename FROM ' . DB_TABLES_PREFIX . 'gallery WHERE id_image=' . $_GET['img'];
$p = $atc->db->db_query($sql);
$p_res = $atc->db->db_fetchassoc($p);
$atc->db->db_freeresult($p);

if(!isset($p_res['filename'])) atcms_gallery_spacer();

if( (bool) $p_res['cached'] ) atcms_gallery_spacer();
elseif($p_res['cached_filename'] != '') @unlink(GALLERY_CACHE . '/' . $p_res['cached_filename']);

$im = @getimagesize($f = ATCMS_ROOT . '/images/' . $p_res['filename']);
if($im[0] == 0) atcms_gallery_spacer();
$type = atcms_determine_image_type($im[2]);
$cached_filename = atcms_gallery_assign_unused_cache_filename($type);
$mime = 'image/' . ($type == 'jpg' ? 'jpeg' : $type);
$resizer = $atc->cfgvar('gallery:interpolation') ? 'imagecopyresampled' : 'imagecopyresized';

$maxwidth = $atc->cfgvar('gallery:thumbnail_width');
$maxheight = $atc->cfgvar('gallery:thumbnail_height');

if($im[0] > $maxwidth || $im[1] > $maxheight)
{
	$fx = 'imagecreatefrom' . $append = ($type == 'jpg' ? 'jpeg' : $type);
	$fy = 'image' . $append;
	$src = $fx($f);
	if(($w = $im[0] / $maxwidth) > ($h = $im[1] / $maxheight))
	{
		$destheight = floor($im[1] / $w);
		$dest = imagecreatetruecolor($maxwidth, $destheight);
		imagefill($dest, 0, 0, imagecolorallocate($dest, 0xFF, 0xFF, 0xFF));
		if($resizer($dest, $src, 0, 0, 0, 0, $maxwidth, $destheight, $im[0], $im[1]))
		{
			imagedestroy($src);
			//header('Content-Type: ' . $mime);
			//$fy($dest);
			$fy($dest, GALLERY_CACHE . '/' . $cached_filename);
			atcms_gallery_cached($_GET['img'], $cached_filename);
			imagedestroy($dest);
			header('Location: ' . ATCMS_WEB_PATH . '/modules/gallery/cache/' . $cached_filename);
		}
		else
		{
			imagedestroy($src);
			imagedestroy($dest);
			atcms_gallery_spacer();
		}
	}
	else
	{
		$destwidth = floor($im[0] / $h);
		$dest = imagecreatetruecolor($destwidth, $maxheight);
		if($resizer($dest, $src, 0, 0, 0, 0, $destwidth, $maxheight, $im[0], $im[1]))
		{
			imagedestroy($src);
			header('Content-Type: ' . $mime);
			//$fy($dest);
			$fy($dest, GALLERY_CACHE . '/' . $cached_filename);
			atcms_gallery_cached($_GET['img'], $cached_filename);
			imagedestroy($dest);
			header('Location: ' . ATCMS_WEB_PATH . '/modules/gallery/cache/' . $cached_filename);
		}
		else
		{
			imagedestroy($src);
			imagedestroy($dest);
			atcms_gallery_spacer();
		}
	}
}
else
{
	//header('Content-type: ' . $mime);
	@copy(IMAGES_DIRECTORY . '/' . $p_res['filename'], GALLERY_CACHE . '/' . $cached_filename);
	atcms_gallery_cached($_GET['img'], $cached_filename);
	//$f = fopen($f, 'r');
	//fpassthru($f);
	//fclose($f);
	header('Location: ' . ATCMS_WEB_PATH . '/modules/gallery/cache/' . $cached_filename);
}

$atc->db->db_freeresult($p);
$atc->finalization();

?>