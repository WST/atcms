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
define('ATCMS_VERSION', '2.0.0');
define('EULA', 'http://www.gnu.org/licenses/gpl-2.0.txt');
require '../includes/regexps.inc.php';
require '../includes/interface_basic.inc.php';
require '../includes/functions.inc.php';

function system_check()
{
	// Мы пока ещё находимся на процедурном этапе, поэтому можно проверить версию PHP (мало ли, вдруг она четвёртая :-)
	if(version_compare('5.0.0', PHP_VERSION) == 1) simple_die('Your PHP version is too old to install AT CMS.');
	//В работоспособности GD удостовериться тоже не помешает
	if(!function_exists('imagecreate')) simple_die('Not good! Your server has a problem with the GD extension. AT CMS cannot work without it. Please, install something like <b>php-gd</b> into your server’s operating system or ask the system administrator for this action.');
	//Проверяем существование и доступность конфигурационного файла. Если получается, создаём оный.
	if(!file_exists('../config.inc.php'))
	{
		($f = @fopen('../config.inc.php', 'w')) || simple_die('Cannot create <b>config.inc.php</b>. Please, create an empty file named <b>config.inc.php</b> in the root of your AT CMS installation and chmod it to 0666.');
		if($f)
		{
			fclose($f);
			chmod('../config.inc.php', 0600);
		}
	}
	if(!is_writable('../config.inc.php')) simple_die('OK, <b>config.inc.php</b> was found, but, unfortunately, it isn’t currently available in RW mode. <b>chmod</b> it to 0666 and try to refresh this page.');
	//if(!is_writable('./install.lock')) simple_die('');
	//Вот теперь мы готовы к переходу к следующему шагу
}

function show_eula()
{
	$text = htmlspecialchars(@file_get_contents(EULA));
	if(empty($text)) $text = htmlspecialchars(@file_get_contents('../COPYING'));
	simple_information('<p>AT CMS ' . ATCMS_VERSION. ' is a free website engine developed by Ilja “WatchRooster” Averkov in association with the <a href="http://shamangrad.net">shamangrad.net</a> coders club. This sowtware is provided “as is”, without any warranty under the terms of the GNU General Public License version 2.0. You have to agree this licence in order to continue the installation.</p><textarea class="eula" disabled>' . $text . '</textarea><form method="GET" action="install.php"><input type="hidden" name="act" value="install"><div class="buttons"><input type="submit" value="Next step" class="button"></div></form>', 'License agreement');
}

function extract_webpath($s)
{
	return preg_replace('#^(.*)/installer/install\.php$#', '$1', $s);
}

function install_form($message)
{
	$dbs = '';
	
	for($d=opendir('../includes/db'); ($f = readdir($d)) !== false; true)
	{
		$m = array();
		if($f == '.' || $f == '..') continue; //Если это . или ..
		if(!preg_match('#^([0-9a-z_-]+)\.inc\.php$#i', $f, $m)) continue; //Если неверное имя
		if(!file_exists('./schemes/' . $m[1] . '.ini')) continue; //Если не существует ini-файл таблиц для данной СУБД
		$sel = (isset($_POST['dbtype']) && $m[1] == $_POST['dbtype']) ? ' selected' : '';
		$dbs .= '<option' . $sel . ' value="' . $m[1] . '">' . $m[1] . '</option>';
	}
	closedir($d);
	
	$langs = '';
	
	for($d=opendir('../languages'); ($f=readdir($d))!==false; true)
	{
		if($f == '.' || $f == '..') continue; //Если это . или ..
		$m = array();
		if(!preg_match(PCREGEXP_LANGFILE_NAME, $f, $m)) continue;
		$sel = (isset($_POST['lang']) && $m[1] == $_POST['lang']) ? ' selected' : '';
		$langs .= '<option' . $sel . ' value="' . $m[1] . '">' . $m[1] . '</option>';
	}
	closedir($d);
	
	$dbserver = isset($_POST['dbserver']) ? htmlspecialchars($_POST['dbserver']) : 'localhost';
	$dbuser = isset($_POST['dbuser']) ? htmlspecialchars($_POST['dbuser']) : '';
	$dbpasswd = isset($_POST['dbpasswd']) ? htmlspecialchars($_POST['dbpasswd']) : '';
	$password = isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '';
	$dbname = isset($_POST['dbname']) ? htmlspecialchars($_POST['dbname']) : '';
	$dbprefix = isset($_POST['dbprefix']) ? htmlspecialchars($_POST['dbprefix']) : '';
	$login = isset($_POST['login']) ? htmlspecialchars($_POST['login']) : 'admin';
	$email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
	$path = isset($_POST['path']) ? htmlspecialchars($_POST['path']) : extract_webpath($_SERVER['PHP_SELF']);
	
	$mes = empty($message) ? '' : '<p class="warning">Error: ' . $message . '</p>';
	
	$install_form = '
	<p>Please, fill in this form. All fields should be filled in. If you want to use unusual database servers port, type the hostname in the format: “name:port”.</p>
	' . $mes . '
	<form method="POST" action="install.php">
	<input type="hidden" name="act" value="install_save">
	<table cellpadding="4" cellspacing="0">
		<tr>
			<td align="right">Default system language:</td>
			<td><select name="lang" class="inp">' . $langs  . '</select></td>
		</tr>
		<tr>
			<td align="right">Name of the database server:</td>
			<td><input type="text" name="dbserver" maxlength="64" class="inp" value="' . $dbserver . '"></td>
		</tr>
		<tr>
			<td align="right">Database server type:</td>
			<td><select name="dbtype" class="inp">' . $dbs  . '</select></td>
		</tr>
		<tr>
			<td align="right">Database user:</td>
			<td><input type="text" name="dbuser" maxlength="64" class="inp" value="' . $dbuser . '"></td>
		</tr>
		<tr>
			<td align="right">Database user’s password:</td>
			<td><input type="password" name="dbpasswd" maxlength="64" class="inp" value="' . $dbpasswd . '"></td>
		</tr>
		<tr>
			<td align="right">Database name:</td>
			<td><input type="text" name="dbname" maxlength="64" class="inp" value="' . $dbname . '"></td>
		</tr>
		<tr>
			<td align="right">Table names prefix:</td>
			<td><input type="text" name="dbprefix" maxlength="32" class="inp" value="atc_" value="' . $dbprefix . '"></td>
		</tr>
		<tr>
			<td align="right">AT CMS administrators login:</td>
			<td><input type="text" name="login" maxlength="32" class="inp" value="' . $login . '"></td>
		</tr>
		<tr>
			<td align="right">AT CMS administrators password:</td>
			<td><input type="password" name="password" maxlength="16" class="inp" value="' . $password . '"></td>
		</tr>
		<tr>
			<td align="right">AT CMS administrators email:</td>
			<td><input type="text" name="email" maxlength="64" class="inp" value="' . $email . '"></td>
		</tr>
		<tr>
			<td align="right">AT CMS web location (do not edit in most of cases):</td>
			<td><input type="text" name="path" class="inp" value="' . $path . '"></td>
		</tr>
	</table>
	';
	return $install_form;
}

function install($message = '')
{
	$install_form = install_form($message);
	simple_information($install_form . '<div class="buttons"><input type="submit" class="button" value="Next step"></div>', 'AT CMS installer');
}

function install_save()
{
	preg_match('#^[0-9a-z_-]+$#i', $_POST['dbtype']) || install('Invalid driver name!');
	file_exists($driver = '../includes/db/' . $_POST['dbtype'] . '.inc.php') || install('This driver doesnt exist!');
	
	// Дальше, кстати, начинаем работать объектно
	require $driver;
	
	$m = array();
	isset($_POST['dbserver']) && preg_match(PCREGEXP_HOSTNAME, $_POST['dbserver'], $m) || install('Wrong database server name!');
	isset($_POST['dbuser']) && preg_match('#^[a-z0-9_-]+$#i', $_POST['dbuser']) || install('Wrong database user');
	isset($_POST['dbname']) && preg_match('#^[a-z0-9_-]+$#i', $_POST['dbname']) || install('Wrong database name');
	isset($_POST['dbprefix']) && preg_match('#^[a-z0-9_-]*$#i', $_POST['dbprefix']) || install('Wrong table names prefix');
	isset($_POST['login']) && preg_match(PCREGEXP_USERNAME, $_POST['login']) || install('Your login is too complex. You must use only latin letters, numbers and &#0147;_&#0148;');
	isset($_POST['password']) && preg_match(PCREGEXP_PASSWORD, $_POST['password']) || install('Administrators password must be a string with 3..16 characters');
	isset($_POST['email']) && preg_match(PCREGEXP_EMAIL, $_POST['email']) || install('Wrong e-mail address');
	isset($_POST['path']) && preg_match(PCREGEXP_WEBPATH, $_POST['path']) || install('Wrong web path');
	isset($_POST['lang']) && preg_match(PCREGEXP_LANGNAME, $_POST['lang']) && file_exists('../languages/' . $_POST['lang'] . '.php') || install('Wrong default language');
	
	isset($m[3]) || $m[3] = '3306';
	
	$db = new dbserver($m[1], $m[3], $_POST['dbuser'], $_POST['dbname'], $_POST['dbpasswd'], 'install'); //Соединение с СУБД посредством драйвера
	
	file_exists($scheme = './schemes/' . $_POST['dbtype'] . '.ini') || install('Installation scheme was not found!');
	
	$scheme = atcms_parse_scheme_data(file_get_contents($scheme));
	
	foreach($scheme as $table=>$cols)
	{
		$pk = $uk = NULL;
		if(isset($cols['__PK']))
		{
			$pk = $cols['__PK'];
			unset($cols['__PK']);
		}
		if(isset($cols['__UK']))
		{
			$uk = $cols['__UK'];
			unset($cols['__UK']);
		}
		$db->create_table($_POST['dbprefix'] . $table, $cols, $pk, $uk);
	}
	
	$sql = 'INSERT INTO ' . $_POST['dbprefix'] . 'users (name, password, level, email, regtime) VALUES (\'' . $_POST['login'] . '\', \''. md5($_POST['password']) .'\', 1, \''. $_POST['email'] .'\', \'' . time() . '\')';
	$db->db_query($sql);
	
	$sql = 'INSERT INTO '. $_POST['dbprefix'] . 'languages (file) VALUES (\'' . $_POST['lang'] . '\')';
	$db->db_query($sql);
	
	$config = array
	(
		'layout'=>'default',
		'structure_recursion_limit'=>'3',
		'site_title'=>'AT CMS site',
		'site_description'=>'Yet another AT CMS site<br>&copy; site admin',
		'keywords'=>'atcms,cms,php,mysql',
		'session_length'=>'600',
		'date_format'=>'d.m.Y H:i',
		'users_per_page'=>'30',
		'process_emoticons'=>'1',
		'timezone'=>'3',
		'avatar_maxwidth'=>'100',
		'avatar_maxheight'=>'100',
		'avatar_maxfilesize'=>'16384',
		'system_icq_uin'=>'',
		'system_icq_password'=>'',
		'system_jid'=>'',
		'system_jid_password'=>'',
		'display_online_users'=>'1',
		'guests_can_view_users_list'=>'1'
	);
	$ins = array();
	foreach($config as $k=>$v)
	{
		$ins[] = '(\'' . $k . '\', \'' . $v . '\')';
	}
	$sql = 'INSERT INTO ' . $_POST['dbprefix'] . 'config (param_name, param_value) VALUES ' . implode(', ', $ins);
	$db->db_query($sql);
	
	$emo = array
	(
		'0_o'=>'smile001.png',
		';-)'=>'smile011.png',
		':-@'=>'smile004.png',
		':-)'=>'smile005.png',
		'o_o'=>'smile002.png',
		'-_-'=>'smile010.png',
		':\\\'('=>'smile009.png',
		':-p'=>'smile008.png',
		'>_<'=>'smile003.png',
		':-('=>'smile007.png',
		':-|'=>'smile006.png'
	);
	$ins = array();
	foreach($emo as $k=>$v)
	{
		$ins[] = '(\'' . $k . '\', \'' . $v . '\')';
	}
	$sql = 'INSERT INTO ' . $_POST['dbprefix'] . 'emoticons (emoticon_code, emoticon_file) VALUES ' . implode(', ', $ins);
	$db->db_query($sql);
	
	$sql = 'INSERT INTO ' . $_POST['dbprefix'] . 'events (timestamp, id_user, type, message) VALUES (' . time() . ', 1, 1, \'AT CMS installed\')';
	$db->db_query($sql);
	
	$configuration = "<?php\n\n" .
	"define('DB_DRIVER', '" . $_POST['dbtype'] . "');\n" .
	"define('DB_SERVER', '" . $m[1] . "');\n" .
	"define('DB_SERVER_PORT', $m[3]);\n" .
	"define('DB_DATABASE', '" . $_POST['dbname'] . "');\n" .
	"define('DB_USER', '" . $_POST['dbuser'] . "');\n" .
	"define('DB_PASSWORD', '" . $_POST['dbpasswd'] . "');\n" .
	"define('DB_TABLES_PREFIX', '" . $_POST['dbprefix'] . "');\n" .
	"define('DEBUG', false);\n" .
	"define('LANGUAGE', '" . $_POST['lang'] . "');\n" .
	"define('ATCMS_WEB_PATH', '" . $_POST['path'] . "');\n\n" .
	"?>";
	
	file_put_contents('../config.inc.php', $configuration);
	simple_information('<p>Congratulations, AT CMS installation is now complete. Don’t forget to remove the <b>installer</b> directory!</p><div class="buttons"><form method="GET" action="../index.php"><input type="submit" class="button" value="Go to the site"></form></div>', 'Complete');
}

system_check();

$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
switch($act)
{
	default: show_eula(); break;
	case 'install': install(); break;
	case 'install_save': install_save(); break;
}

?>
