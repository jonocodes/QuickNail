<?php
/**
 *
 * QuickNail by Jono - jonojuggles@gmail.com
 *
 */


function newIPTC($image_name, $fields)
{
	# $fields[ credit, caption, keywords ] , keywords = "word1, word2, word3"

	$iptc_new="";
	$size = GetImageSize ("$image_name",&$info);
	$iptc= iptcparse ($info["APP13"]);

	if (array_key_exists("caption", $fields))
		$iptc["2#120"][0] = $fields[caption];

	if (array_key_exists("credit", $fields))
		$iptc["2#080"][0] = $iptc["2#110"][0] = $iptc["2#115"][0] = $fields[credit];

	if (array_key_exists("keyword", $fields)) {
		$kwlist = split(",", $fields[keywords]);
		$iptc["2#025"] = array();
		foreach ($kwlist as $thisword)
			$iptc["2#025"][] = ltrim($thisword);
	}

	// Making the new IPTC string
	foreach (array_keys($iptc) as $s){
		$tag = str_replace("2#", "", $s);
		foreach (array_keys($iptc[$s]) as $ns)
			$iptc_new .= iptc_maketag(2, $tag, $iptc[$s][$ns]);
	}

	$content = iptcembed($iptc_new, $image_name, 0);

	$fp = fopen($image_name, "wb");
	fwrite($fp, $content);
	fclose($fp);

	#Caption = 128 character limit
	#Keywords = 856 character limit
	#Description = 3000 character limit
}

function iptc_maketag($rec,$dat,$val){
         $len = strlen($val);
         if ($len < 0x8000)
                 return chr(0x1c).chr($rec).chr($dat).
                 chr($len >> 8).
                 chr($len & 0xff).
                 $val;
         else
                 return chr(0x1c).chr($rec).chr($dat).
                 chr(0x80).chr(0x04).
                 chr(($len >> 24) & 0xff).
                 chr(($len >> 16) & 0xff).
                 chr(($len >> 8 ) & 0xff).
                 chr(($len ) & 0xff).
                 $val;
}


function dump_iptc($filename) {
	$size = GetImageSize ($filename,$info);
	$iptc = iptcparse ($info["APP13"]);

	if (isset($info["APP13"])) {
		$iptc = iptcparse($info["APP13"]);
		if (is_array($iptc))
			print_r($iptc);
	}
}



function update_iptc($image_name, $fields) {

	print "<br>updating $image_name... ";

	$existing_fields_before = get_iptc($image_name);

	# check to see if new fields are different from existing ones
	if ( count(array_diff_assoc($fields, $existing_fields_before)) +
		 count(array_diff_assoc($existing_fields_before, $fields)) == 0)
		return;

	newIPTC($image_name, $fields);

	$existing_fields_after = get_iptc($image_name);

	# check to see if IPTC was not written by checking changed fields
	if ( count(array_diff_assoc($existing_fields_after, $existing_fields_before)) +
		 count(array_diff_assoc($existing_fields_before, $existing_fields_after)) == 0) {

		print "(duplicating picture)";
		# if unchanged, duplicate picture and try again
		$img = imagecreatefromjpeg($image_name);
		imagejpeg($img,$image_name,80);
		imagedestroy($img);

		newIPTC($image_name, $fields);
		$existing_fields_after = get_iptc($image_name);
	}

	if ( count(array_diff_assoc($existing_fields_after, $existing_fields_before)) +
		 count(array_diff_assoc($existing_fields_before, $existing_fields_after)) == 0)
		print "did not work";
	else
		print "worked";
}

function show_all_captions($pictures) {
	global $script;

	$picnum=0;
	print "<form method=post action=$script><input type=hidden name=mode value=updatecaptions><table border=0 cellpadding=10>";

	foreach ($pictures as $pid => $picture)
	{
		$thisfile = $picture[file];
		$thiscaption = $picture[caption];
		$thisthumb = $picture[thumbnail];

		$thisfile_showname = $script = ereg_replace("(.*\/)([^\/]*)","\\2", $thisfile);

		$fieldname = preg_replace("/ /","____", preg_replace("/\./","---",$thisfile));

		if (file_exists($thisfile)) {

			$operations = "<a href=updateimage.php?mode=rotateleft&picnum=$pid>rotate left</a> | <a href=updateimage.php?mode=rotateright&picnum=$pid>rotate right</a> " .
					"| <a href=# onclick=\"confirmation('delete image', 'updateimage.php?mode=delete&picnum=$pid')\">delete image</a>";

			print "<tr><td align=center><img src=\"$thisthumb\"></td>";
			print "<td>$thisfile_showname<br /><br />$operations<br />";

			if (!is_readable($thisfile))
				print "<font color=red>Warning: File is not readable.<br>Change permissions in order to view picture.</font><br />";
			if (!is_writable($thisfile))
				print "<font color=red>Warning: File is not writable.<br>Change permissions in order to update caption.</font><br />";

			print "<br />Caption: <input type=text size=40 maxlength=125 name=\"$fieldname\" value=\"" . stripslashes($thiscaption) . "\">";
			print "</td></tr>\n";
		}

	}
	print "<tr><td></td><td><input type=Submit value=Update></td></tr>";
	print "</table></form>";

}

function update_images($updates) {
	if (count($updates) == 0)
		print "no image captions changed by user";
	else
		foreach ($updates as $image => $caption)
			update_iptc($image, array("caption"=>htmlentities($caption, ENT_QUOTES)) );
}


	function resizeimage($filename, $newfile, $maxdim)
	{
		$maxwidth = $maxheight = $maxdim;

		if (!file_exists($filename)) exit("error: file does not exist\n");

		if ( (strcasecmp(substr($filename, -3), "jpg") != 0)&&
			(strcasecmp(substr($filename, -4), "jpeg") != 0) )
			die ("Error: This only works with jpg files");

		if (!function_exists("imagejpeg"))
			die ("Error: looks like GD is not installed on this server");

		list($width, $height) = getimagesize($filename);
		$scale = 1;

		$new_width = $width;
		$new_height = $height;

		if ($new_width > $maxwidth){
			$new_height = round($maxwidth / $new_width * $new_height);
			$new_width = round($maxwidth);
		}

		if ($new_height > $maxheight){
			$new_width = round($maxheight / $new_height * $new_width);
			$new_height = round($maxheight);
		}

		$image_p = imagecreatetruecolor($new_width, $new_height);
		$image = imagecreatefromjpeg($filename);
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

		imagejpeg($image_p, $newfile, 75);
	}

function checkthumbs($pictures) {
	global $conf;
	print "checking thumbnails<br><br>";

	if ($conf[general][thumbsdir] == $conf[general][picturesdir])
		die("thumbsdir is the same as picturedir. Thumbnails cannot be generated unless they are in a different directory.");

	foreach ($pictures as $pid => $picture) {
		print "for " . $picture[file] . " .... ";
		if (preg_match("/php/", $picture[thumbnail])) {
			print "thumbnail not found";
		}
		else {

			list($width, $height, $type, $attr) = getimagesize($picture[thumbnail]);
			$d = max($width, $height);
			print "thumbnail found $picture[thumbnail] ($width, $height)";

			if (max($width, $height) > $conf[gallery][thumbsize]) {
				print " ... needs resizing";
			}
		}
		print "<br>";
	}
}

function generatethumbs($pictures, $picturesdir, $thumbsdir, $fullrefresh=false) {
	global $conf;
	print "generating thumbnails<br><br>";

	if (!is_dir($thumbsdir))
		if (mkdir($thumbsdir, 0777) === FALSE)
			die("Error: Unable to create missing directory $thumbsdir");

	foreach ($pictures as $pid => $picture) {

		$thisthumb = "$thumbsdir/." . substr($picture[file], strlen($picturesdir));

		print "from ". $picture[file] . " to $thisthumb  ";

		# skip files thumbnails that are already generated with the correct size
		if (file_exists($thisthumb)) {
			list($width, $height, $type, $attr) = getimagesize($thisthumb);
			max($width, $height);

			if (max($width, $height) <= $conf[gallery][thumbsize] && !$fullrefresh) {
				print " .... skipping because $thisthumb ($width, $height) already exists<br>";
				continue;
			}
		}

		print " .... generating " . "$thisthumb ...";
		resizeimage($picture[file], $thisthumb, $conf[gallery][thumbsize]);

		if (!file_exists($thisthumb))
			die("There was an error creating the new thumbnail. Try changing permissions of $thumbdir.");

		list($width, $height, $type, $attr) = getimagesize($thisthumb);
		print "  thumbnail generated ($width, $height)";

		print "<br>";
	}
}




function deletethumbs($pictures, $picturesdir, $thumbsdir) {
	global $conf;
	print "deleting thumbnails<br><br>";

	foreach ($pictures as $pid => $picture) {

		$thisthumb = "$thumbsdir/." . substr($picture[file], strlen($picturesdir));

		if (file_exists($thisthumb)) {
			print " removing " . "$thisthumb <br />";
			unlink($thisthumb);
		}
	}
}

function showsummary($pictures, $picturesdir, $thumbsdir) {
	global $conf;

	$thumbcount=0;
	$captioncount=0;

	foreach ($pictures as $pid => $picture) {

		$thisthumb = "$thumbsdir/." . substr($picture[file], strlen($picturesdir));

		if (file_exists($thisthumb))
			$thumbcount++;

		if ($picture[caption] != "")
			$captioncount++;
	}

	print "<table align=center width=200 height=95% border=0><tr><td valign=center>";
	print "<h1>" . $conf[general][title] . "</h1><b>";
	print "Pictures: " . count($pictures) . "<br><br>";
	print "Thumbnails: $thumbcount <br><br> Captions: $captioncount";
	print "</td></tr></table>";


}

function showgeneralsettings() {
	global $conf_fromfile;


	print "<table align=center border=0 cellpadding=5>";

	foreach ($conf_fromfile as $sectitle => $thissection)
	if ($sectitle != "") {
		print "<tr><td colspan=2 align=center height=70><h2>" . ucfirst($sectitle) . " Settings</h2></td></tr>";

		foreach ($thissection as $key => $val) {
			if ($val != "" && $key != "password"){
				$formattedkey = ereg_replace("_", " ", ucfirst($key));
				$formattedval = $val;
				if (is_bool($val) && $val==0)
					$formattedval = "true";
				else if (is_bool($val) && $val==1)
					$formattedval = "false";

				print "<tr><td align=right width=50%><b>". $formattedkey ."</b></td><td>" . $formattedval . "</td></tr>";
			}
		}
	}

	print "</table>";


}

# main
# set various variables

$basedir = "..";

$imagescript=$basedir . "/qnail.php";

$galleryasinclude=true; # needed for including the gallery home functions

include("index.php");	# for authentication
include($imagescript);	# to get functions


load_config("$basedir/qconfig.ini");
$conf_fromfile = $conf;
$conf[general][picturesdir] = "$basedir/" . $conf[general][picturesdir];
$conf[general][thumbsdir] = "$basedir/" . $conf[general][thumbsdir];

if (!logged_in()) {
	header('Location: index.php');
	exit;
}

?>
