<?php

include('acommon.php');

$sec = $_REQUEST[section];
$var = $_REQUEST[field];
$val = $_REQUEST[value];

if ($sec == "general") {
	if ($var == "picturesdir") {
		if (!is_dir("$basedir/$val"))
			die("directory does not exist");
	}
	if ($var == "thumbsdir") {
		if (!is_dir("$basedir/$val"))
			die("directory does not exist");
	}
	if ($var == "template") {
		if (!is_file("$basedir/$val"))
			die("file does not exist");
	}

}

if (isset($conf_fromfile[$sec][$var]) && $conf_fromfile[$sec][$var] != $val) {
	$conf_fromfile[$sec][$var] = $val;
	write_ini_file("$basedir/qconfig.ini", $conf_fromfile);
	print "updated";
}

?>