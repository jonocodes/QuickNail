<?
#
# QuickNail 3.3 by Jono - jfinger@gmail.com (February 2009)
# http://quicknail.foodnotblogs.com
#
#

// INI file read/write functions

function isInteger($input){
	if (is_int($input))	return true;	// for integers
	return preg_match('@^[-]?[0-9]+$@',$input) === 1;	// for strings
}	

function isBool($input) {
	return in_array($input, array('true', 'True', 'TRUE', true, 1, 'false', 'False', 'FALSE', 'false', 0), true);
}

function makeBool($input) {
	return (in_array($input, array('true', 'True', 'TRUE', true, 1), true));
}

function read_ini_file($f, &$r)
{
	$null = "";
	$r=$null;
	$first_char = "";
	$sec=$null;
	$comment_chars=";#";
	$num_comments = "0";
	$num_newline = "0";

	//Read to end of file with the newlines still attached into $f
	$f = @file($f);
	if ($f === false)
		return -2;

	// Process all lines from 0 to count($f)
	for ($i=0; $i<@count($f); $i++)
	{
		$w=@trim($f[$i]);
		$first_char = @substr($w,0,1);
		if ($w)
		{
			if ((@substr($w,0,1)=="[") and (@substr($w,-1,1))=="]") {
				$sec=@substr($w,1,@strlen($w)-2);
				$num_comments = 0;
				$num_newline = 0;
			}
			else if ((stristr($comment_chars, $first_char) == true)) {
				$r[$sec]["Comment_".$num_comments]=$w;
				$num_comments = $num_comments +1;
			}			   
			else {
				// Look for the = char to allow us to split the section into key and value
				$w=@explode("=",$w);
				$k=@trim($w[0]);
				unset($w[0]);
				$v=@trim(@implode("=",$w));
				// look for the new lines
				if ((@substr($v,0,1)=="\"") and (@substr($v,-1,1)=="\"")) {
					$v=@substr($v,1,@strlen($v)-2);
				}
					
				// check for type and convert to them
				if (isBool($v))
					$v = makeBool($v);
				else if (isInteger($v))
					$v = (int)$v;
				// else, it is a string
				
				$r[$sec][$k]=$v;
				   
			}
		}
		else {
			$r[$sec]["Newline_".$num_newline]=$w;
			$num_newline = $num_newline +1;
		}
	}
	return 1;
}



function beginsWith( $str, $sub ) {
	return ( substr( $str, 0, strlen( $sub ) ) === $sub );
}

function write_ini_file($path, $assoc_arr) {
	$content = "";

	foreach ($assoc_arr as $key=>$elem) {
		if (is_array($elem)) {
			if ($key != '') 
				$content .= "[".$key."]\r\n";
		   
			foreach ($elem as $key2=>$elem2) {
				if (beginsWith($key2,'Comment_') == 1 && beginsWith($elem2,';'))
					$content .= $elem2."\r\n";
				else if (beginsWith($key2,'Newline_') == 1 && ($elem2 == ''))
					$content .= $elem2."\r\n";
				else
					$content .= $key2." = ".$elem2."\r\n";
			}
		}
		else 
			$content .= $key." = ".$elem."\r\n";
	}

	if (!$handle = fopen($path, 'w'))
		return -2;
	if (!fwrite($handle, $content))
		return -2;
		
	fclose($handle);
	return 1;
}



function load_config($configfile) {
	global $PHP_SELF, $template_text, $conf;

	read_ini_file($configfile, $conf);

	# prepare template
	if (file_exists($conf[general][template]))
		$template_text = file_get_contents($conf[general][template]);
	else {
		$template_text =<<<TMPL
	<html><head><title>% QUICKNAIL_TITLE %</title>
	<style type=text/css>IMG { BORDER-style: none; }</style>
	% QUICKNAIL_HEAD %
	</head><body bgcolor=white>
	% QUICKNAIL_MAINCONTENT %
	</body></html>
TMPL;
	}

	if (empty($conf[general][title])) {
		$dirs = split("/", $PHP_SELF);
		$conf[general][title] = $dirs[count($dirs)-2];
	}

	# check user defined vars

	if (empty($conf[general][title])) $conf[general][title] = "My Photo Gallery";
	if (!is_bool($conf[image][lightbox])) $conf[image][lightbox] = false;
	if (!is_integer($conf[gallery][picsperline])) $conf[gallery][picsperline] = 3;
	if (!is_integer($conf[gallery][picsperpage])) $conf[gallery][picsperpage] = 12;
	if (!is_integer($conf[gallery][slidespeed])) $conf[gallery][slidespeed] = 4;
	if (!is_integer($conf[image][enlargesize])) $conf[image][enlargesize] = 600;
	if (!is_bool($conf[image][clickfull])) $conf[image][clickfull] = true;

	if (!is_bool($conf[image][show_credit])) $conf[image][show_credit] = true;
	if (!is_integer($conf[image][thumbsize])) $conf[image][thumbsize] = 180;
	if (!is_bool($conf[gallery][show_image_names])) $conf[gallery][show_image_names] = false;
	if (!is_bool($conf[image][show_captions])) $conf[image][show_captions] = true;

	if (empty($conf[general][picturesdir])) $conf[general][picturesdir] = ".";
	if (empty($conf[general][thumbsdir]))	$conf[general][thumbsdir] = "thumbs";
	if (!is_bool($conf[image][prevent_enlarged_overscaling]))	$conf[image][prevent_enlarged_overscaling] = true;

	if ($conf[image][lightbox_dim_background] < 0 || $conf[image][lightbox_dim_background] > 1)
		$conf[image][lightbox_dim_background] = 0.75;
}

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

# currently only checks captions
function get_iptc($filename) {

	$iptc_fields = array();
	$size = GetImageSize ($filename,$info);
	$iptc = iptcparse ($info["APP13"]);

	if (isset($info["APP13"])) {
		$iptc = iptcparse($info["APP13"]);
		$iptc_fields[caption] = $iptc["2#120"][0];
	}
	return $iptc_fields;
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
	
	print "<form method=post action=$script><input type=hidden name=mode value=updatecaptions><table border=0 cellpadding=10 align=center>";
	
	foreach ($pictures as $picture)
	{
		$thisfile = $picture[file];
		$thiscaption = $picture[caption];
		$thisthumb = $picture[thumbnail];
		
		$thisfile_showname = $script = ereg_replace("(.*\/)([^\/]*)","\\2", $thisfile);
		
		$fieldname = preg_replace("/ /","____", preg_replace("/\./","---",$thisfile));

		print "<tr><td align=center><img src=\"$thisthumb\"></td>";
		print "<td>$thisfile_showname<br />";
		if (!is_readable($thisfile))
			print "<font color=red>Warning: File is not readable.<br>Change permissions in order to view picture.</font><br />";
		if (!is_writable($thisfile))
			print "<font color=red>Warning: File is not writable.<br>Change permissions in order to update caption.</font><br />";
		print "<br /><input type=text size=50 maxlength=125 name=\"$fieldname\" value=\"" . stripslashes($thiscaption) . "\"></td></tr>\n";
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



# generate filenames, thumbnails and captions lists
function generate_file_list($dir, $thumbsdir, $sortby) {
	global $conf;

	function cmp_date($a, $b) {
	//	return strcmp($a["sortfield"], $b["sortfield"]);

		if ($a["sortfield"] == $b["sortfield"])	return 0;
		return ($a["sortfield"] < $b["sortfield"]) ? -1 : 1;
	}

	function cmp_fname($a, $b) {
		return strnatcasecmp($a["sortfield"], $b["sortfield"]);
	}

	if ($sortby != "date")	$sortby = "fname";

	$dh  = opendir($dir);
	while (false !== ($fn = readdir($dh))) {
		if ( (strcasecmp( substr($fn, -3), "jpg")==0) ||
			(strcasecmp( substr($fn, -4), "jpeg")==0) ) {
			
			$filename = "$dir/$fn";
			$thumbname = "$thumbsdir/$fn";
			
			$tprename = ereg_replace("(.*\/)([^\/]*)","\\1", $conf[general][quicknail_script]);
			
			$tfilename = urlencode(substr($filename, strlen($tprename)));
			
			$fields = get_iptc($filename);
			$caption = $fields[caption];
			
			$picture[caption] = $caption;
			$picture[file] = $filename;
			
			if ($sortby == "date")
				$picture[sortfield] = filemtime($filename);	//filectime($fn);
			else
				$picture[sortfield] = $filename;
			
			if (file_exists($thumbname))
				$picture[thumbnail] = $thumbname;
			else
				$picture[thumbnail] = $conf[general][quicknail_script] . "?mode=image&filename=" . $tfilename . "&max=" . $conf[gallery][thumbsize];
				
			$pictures[] = $picture;
   		}
	}
	if (!empty($pictures))
		usort($pictures, "cmp_$sortby");

	return $pictures;
}


# main()

include("index.php");

session_start();

if (!logged_in()) {
	header('Location: index.php');
	exit;
}

$basedir = "..";

load_config("$basedir/config.ini");

$conf[general][picturesdir] = "$basedir/" . $conf[general][picturesdir];
$conf[general][thumbsdir] = "$basedir/" . $conf[general][thumbsdir];
$conf[general][quicknail_script] = "$basedir/" . $conf[general][quicknail_script];

$script = ereg_replace("(.*\/)([^\/]*)","\\2", $_SERVER["SCRIPT_FILENAME"]);

$mode = $_REQUEST{mode};

$pictures = generate_file_list($conf[general][picturesdir], $conf[general][thumbsdir], $sortby);


print "<center><h1>Manage Images</h1></center><br />";

if (empty($pictures))
	die("There are no pictures in pictures directory.");

//print_r($_SESSION);

print "<center><a href=$script?mode=showcaptions>captions</a> | <a href=$script?mode=checkthumbs>thumbnails</a> | <a href=" . $conf[general][quicknail_script] . ">gallery</a> | <a href=logout.php>logout</a></center><br>";

if ($mode == "showcaptions")
	show_all_captions( $pictures );

elseif ($mode == "updatecaptions") {
	if (count($_POST) != 0) {

		foreach ($pictures as $picture)
			$captions[$picture[file]] = $picture[caption];
	
		foreach ($_POST as $file => $caption) {
			$file = preg_replace("/---/",".", preg_replace("/____/"," ",$file));
		
			if ( $captions[$file] != htmlentities($caption, ENT_QUOTES) && file_exists($file))
				$updates[$file] = $caption;
		}

		update_images($updates);
	}
}
else if ($mode == "checkthumbs") {
	checkthumbs($pictures);
	
	print "<br><form method=post action=$script><input type=hidden name=mode value=generatethumbs> <input type=Submit value=\"Generate New Thumbnails\"></form>";
}
else if ($mode == "generatethumbs"){
	generatethumbs($pictures, $conf[general][picturesdir], $conf[general][thumbsdir]);
}

?>
