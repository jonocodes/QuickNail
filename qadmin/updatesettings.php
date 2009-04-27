<?php

function checkBounds($val, $var, $low, $high){
    if ($val < $low || $val > $high)
        die ("$var out of bounds $low .. $high");
}

function dirExists($val){
    if (!is_dir($val))
		die("directory does not exist");
}

function fileExists($val){
    if (!is_file($val))
		die("file does not exist");
}


include('acommon.php');

$sec = $_REQUEST[section];
$var = $_REQUEST[field];
$val = $_REQUEST[value];

//$checkBounds = 'checkBounds';
//$testarray = array("thumbsize"=> $checkBounds($val, $var, 10, 400) );

if ($sec == "general") {
	if ($var == "picturesdir")  dirExists("$basedir/$val");
	if ($var == "thumbsdir")    dirExists("$basedir/$val");
	if ($var == "template") fileExists("$basedir/$val");
}
else if ($sec == "gallery"){
    if ($var == "thumbsize")    checkBounds($val, $var, 10, 400);
    	// should remove thumbnail cache here
    if ($var == "picsperline")  checkBounds($val, $var, 1, 10);
    if ($var == "picsperpage")  checkBounds($val, $var, 1, 100);
    if ($var == "slidespeed")   checkBounds($val, $var, 1, 15);
}
else if ($sec == "image"){
    if ($var =="enlargesize")   checkBounds($val, $var, 100, 1000);
    if ($var =="lightbox_dim_background")   checkBounds($val, $var, 0, 1);
    if ($var =="lightbox_slidestyle")   checkBounds($val, $var, 1, 5);
}

// handle checkbox values
/*
if ($var == "show_image_names" || $var == "show_credit" || $var == "show_captions" || $var == "clickfull" || $var == "prevent_enlarged_overscaling" || $var == "lightbox"){
    if ($val == "on")
       $val = true;
    else
       $val = false;
}
*/


if (isset($conf_fromfile[$sec][$var]) && $conf_fromfile[$sec][$var] !== $val) {
	$conf_fromfile[$sec][$var] = $val;
	write_ini_file("$basedir/qconfig.ini", $conf_fromfile);
	print "updated";
}

?>
