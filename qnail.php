<?php
/**
 * QuickNail by Jono
 *  https://github.com/jonocodes/QuickNail
 */

$quicknail_homepage = "https://github.com/jonocodes/QuickNail";
$quicknail_version="0.5.2";

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
				else {

					if (is_bool($elem2)&&$elem2===true)
						$content .= $key2." = true\r\n";
					else if (is_bool($elem2)&&$elem2===false)
						$content .= $key2." = false\r\n";
					else
						$content .= $key2." = ".$elem2."\r\n";
				}

//					$content .= $key2." = ".$elem2."\r\n";
			}
		}
		else
			if (is_bool($elem)&&$elem===true)
				$content .= $key." = true\r\n";
			else if (is_bool($elem)&&$elem===false)
				$content .= $key." = false\r\n";
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

    
    
function generate_page($template, $head, $title, $content) {
	global $quicknail_homepage;

	$outarr = array();

	$out = "<!-- Page generated by QuickNail $quicknail_homepage -->\n" . $template;
	$to_replace = array(
		"% QUICKNAIL_HEAD %" => $head,
		"% QUICKNAIL_TITLE %" => $title,
		"% QUICKNAIL_MAINCONTENT %" => $content
		);
		
	foreach ($to_replace as $key => $val) {
		if ($out == str_replace($key, $val, $out)) 
			die("Error: Invalid template. Missing $key");
		$out = str_replace($key, $val, $out);
	}

	$outarr[wholepage] = $out;
	$outarr[head] = $head;
	$outarr[body] = $content;
	//print $out;
	return $outarr;
}

function chopext($in) {
	if (strcasecmp( substr($in, -3), "jpg")==0)
			$out = substr($in, 0, strlen($in)-4);
		else	$out = substr($in, 0, strlen($in)-5);
	return $out;
}

function printImage($filename) {
	global $w, $h, $max, $conf;
	
	if (!file_exists($filename)) die ("Error: file '$filename' does not exist");

	if ( (strcasecmp(substr($filename, -3), "jpg") != 0)&&
		(strcasecmp(substr($filename, -4), "jpeg") != 0) )
		die ("Error: This only works with jpg files");
		
	header("Content-type: image/jpeg");
	$source = imagecreatefromjpeg($filename);

	$origw=imagesx($source);
	$origh=imagesy($source);

	if (!empty($max))
	{
		# prevent over scaling if the image is smaller then $max
		if ($conf[image][prevent_enlarged_overscaling] && max($origw, $origh) < $max) {
			$w = $origw;
			$h = $origh;
		}
		else if ($origw > $origh)
		{
			$w = $max;
			$scale = $max/$origw;
			$h = round($scale*$origh);
		}
		else
		{
			$h = $max;
			$scale = $max/$origh;
			$w = round($scale*$origw);
		}
	}
	else if (empty($w) && empty($h))
	{
		$w = $origw;
		$h = $origh;
	}
	else if (empty($w))
	{
		$scale = $h/$origh;
		$w = round($scale*$origw);
	}
	else if (empty($h))
	{
		$scale = $w/$origw;
		$h = round($scale*$origh);
	}

	$dest = imagecreatetruecolor($w, $h)
		or die("Cannot Initialize new GD image stream");

	imagecopyresized($dest, $source, 0, 0, 0, 0, $w, $h,imagesx($source), imagesy($source));

	imagejpeg($dest);
	exit(0);
}



# generate filenames, thumbnails and captions lists
function generate_file_list($dir, $thumbsdir, $sortby, $fromadmin=false) {
	global $imagescript, $conf;

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
			else {
				if ($fromadmin)
					$filename = substr($filename, 3);

				$picture[thumbnail] = $imagescript . "?mode=image&filename=" . urlencode($filename) . "&max=" . $conf[gallery][thumbsize];
			}
			$pictures[] = $picture;
   		}
	}
	if (!empty($pictures))
		usort($pictures, "cmp_$sortby");

	return $pictures;
}

/**
 * Displays static page for the non-javascript/lightbox for enlarged images and slideshows.
 *
 * @global string $imagescript
 * @global string $altgalleryscript
 * @global array $pictures
 * @global array $captions
 * @global string $template_text
 * @global array $conf
 * @param string $filename
 * @param boolean $slideshow
 * @return array for gallery output
 */
function enlargeImage($filename, $slideshow=false)
{
	global $imagescript, $altgalleryscript, $pictures, $captions, $template_text, $conf;
	$size = $conf[image][enlargesize];
	$title = $conf[general][title];
	
	$navmode = "enlarge";
	if ($slideshow) $navmode = "slideshow";
	
	$choppedname = ereg_replace("(.*\/)([^\/]*)","\\2", chopext($filename));
	$totalfiles = count($pictures);
	
	$previous = $next = $altgalleryscript;
	
	$index = -1;
	for ($i = 0; $i<=count($pictures); $i++)
		if ($filename == $pictures[$i][file])	$index = $i;
	
	if ($index > 0) $previous = "$altgalleryscript?mode=$navmode&filename=". urlencode($pictures[$index-1][file]);
	if ($index < $totalfiles && $pictures[$index+1][file] != "") $next = "$altgalleryscript?mode=$navmode&filename=". urlencode($pictures[$index+1][file]);
	$index++;
	
	$caption = stripslashes($captions[$filename]);
	
	$img = "<img src=$imagescript?mode=image&filename=". urlencode($filename) ."&max=$size>";
	if ($conf[image][clickfull])
		$img = "<a href=\"". ($filename) ."\">$img</a>";
		
	if ($slideshow)
		$slide = "<META HTTP-EQUIV=\"refresh\" content=\"" . $conf[gallery][slidespeed] . "; URL=$next\">";

	$head =<<<HEAD
$slide
<script type=text/javascript>
document.onkeypress = handler;
function handler (e) {
   if (navigator.appName == "Netscape") { keyval = e.which; }
   else                                 { keyval = window.event.keyCode; }
   if (keyval == 112) { location ="$previous"; return true; }
   if (keyval == 105) { location ="."; return true; }
   if (keyval == 110) { location ="$next"; return true; }
   return; }
</script>
<style>
.quicknailcontent a {	text-decoration:none;	}
.quicknailcontent a:hover {  color: #FF9933; text-decoration: none; }
.quicknailcontent img { BORDER-style: none; }
</style>
HEAD;

	if ($title)
		$t = "<a href=$altgalleryscript>$title</a> /";

	$content =<<<CONTENT
<div class=quicknailcontent>
<table border=0 align=center><tr>
<td align=left><b>$t $choppedname</b></td>
<td align=right><a href=$previous>&#8592; previous</a> | <a href=$altgalleryscript>$index of $totalfiles</a> | <a href=$next>next &#8594;</a></td>
</tr><tr><td align=center colspan=2><br>
	<table cellpadding=0 cellspacing=7 class=picborder><tr><td>$img<br> $caption</td></tr></table>
</div></td></tr></table>
</div>
CONTENT;

	return generate_page($template_text, $head, $conf[general][title], $content);

}


function showGallery()
{
	global $altgalleryscript, $pictures, $captions, $template_text, $quicknail_homepage, $common_folder, $conf;

	$page = $_GET{page};

	if (empty($page)) $page = 1;

	$ss = $conf[gallery][slidespeed] * 1000;
	
	//1=borderless dark, 2=dark, 3=white, 4=glowing white, 5=drop shadow white
	switch($conf[image][lightbox_slidestyle]) {
	
		// for case 1 see default	
		
		case 2;
			$hso = 'glossy-dark';
			$hsw = 'dark';		
			break;
		
		case 3:
			$hso = 'rounded-white';
			$hsw = 'white';
			break;

		case 4:
			$hso = 'outer-glow';
			$hsw = 'white';
			break;
			
		case 5:
			$hso = 'drop-shadow';
			$hsw = 'white';
			break;
			
		default:
			$hsw = "dark borderless floating-caption";
			break;

	}
	
	if ($hso) $sstyle  = "hs.outlineType = '$hso';\n";
	if ($hsw) $sstyle .= "hs.wrapperClassName = '$hsw';\n";
	
	$dim = $conf[image][lightbox_dim_background];
	
	$gallery_header=<<<HEAD

<script type=text/javascript src=$common_folder/highslide-with-gallery.js></script>
<link rel="stylesheet" type="text/css" href="$common_folder/highslide.css" />

<script type=text/javascript>
	
	hs.graphicsDir = '$common_folder/graphics/';
	hs.align = 'center';
	hs.transitions = ['expand', 'crossfade'];
	
	$sstyle
	
	hs.fadeInOut = true;
	hs.dimmingOpacity = $dim;
	
	// Add the controlbar
	if (hs.addSlideshow) hs.addSlideshow({
		interval: $ss,
		repeat: false,
		useControls: true,
		fixedControls: 'fit',
		overlayOptions: {
			opacity: .6,
			position: 'bottom center',
			hideOnMouseOut: true
		}
	});

</script>


<style>


.quicknailcontent a {	text-decoration:none;	}
.quicknailcontent a:hover {  color: #FF9933; text-decoration: none; }
.quicknailcontent img { BORDER-style: none; }

.footer{
	clear: both;
	padding: 0 0 0 10px;
}

.numPages {
	float: left;
	text-transform: uppercase;
	font-size: 10px;
}

.paginate {
	float: left;
	font-size: 10px;
}

.pageNumber {
	border:1px solid #e3e3e3;
	font-size:14px;
	/*width:12px;*/
	height:16px;
	padding: 0px 5px 4px 3px;
	float: left;
	margin-right:3px;
}

.currentPage {
	background-color: red;
	color: white;
	font-size:14px;
	/*width:14px;*/
	height:18px;
	padding: 0px 5px 4px 5px;
	float: left;
	margin-right:3px;
}

.nav {
	display: inline-block;
}

.prev{
	float: left;
	padding: 0px 9px 2px 3px;
	margin-right:5px;
	font-size:14px;
	border:1px solid #e3e3e3;
}

.next{
	float: left;
	padding: 0px 3px 2px 3px;
	margin: 0 5px 0 0;
	font-size:14px;
	border:1px solid #e3e3e3;
}

.credit{
	font-size: .7em;
	margin-top:10px;
	color: #9d9d9d;
	text-transform: uppercase;
}

.credit a{
	color: #bd7d7d;
}



</style>
HEAD;

	$credit=<<<CREDIT
	gallery powered by <a href=$quicknail_homepage>QuickNail</a>
CREDIT;

	$content .= "<div class=quicknailcontent><h1>" . $conf[general][title] . "</h1>";
	$content .= "<p /><p class=\"subtitle\">" . $conf[general][subtitle] . "</p>";
	$content .= "<p /><div class=highslide-gallery>";

	$startindex = ($page - 1) * $conf[gallery][picsperpage];
	$endindex = $startindex + $conf[gallery][picsperpage] - 1 ;
	if (count($pictures) <= $endindex)
		$endindex = count($pictures) - 1;
	$numpages = ceil(count($pictures)/$conf[gallery][picsperpage]);

	for ($loop = $startindex; $loop<=$endindex; $loop++)
	{
		$caption = "";
		if ($conf[image][lightbox] && $conf[image][show_captions] && !empty($captions[$pictures[$loop][file]]))
			$caption = "<div class=highslide-caption>". stripslashes($captions[$pictures[$loop][file]]) ."</div>";
		
		if ($conf[image][lightbox])
			$imghref="<a class=linkopacity href=$altgalleryscript?mode=image&filename=" . urlencode($pictures[$loop][file]) . "&max=" . $conf[image][enlargesize] ." class=highslide onclick=\"return hs.expand(this)\">";
		else
			$imghref = "<a class=linkopacity href=$altgalleryscript?mode=enlarge&filename=" . urlencode($pictures[$loop][file]) . ">";
		
		$content .= "<div class=pic_div>$imghref";
		$content .= "<img src=\"" . $pictures[$loop][thumbnail] . "\" title=\"Click to enlarge\" /></a>$caption</div>";
		
		if ($conf[gallery][show_image_names] === true)
			$content .= ereg_replace("(.*\/)([^\/]*)","\\2", chopext($pictures[$loop][file]));

		if ( (($loop+1) % $conf[gallery][picsperline]) == 0) $content .= "</div><div class=highslide-gallery>";
	}

	$content .= "</div>"; //end containing div
	
	$content .= "<div class=footer>"; //begin footer div

	// $content .= "Viewing images " . ($startindex + 1) ." through " . ($endindex + 1) ." of ". count($pictures);
	$content .="<div class=nav>";
	if ($numpages > 1)
	{
		// $content .= "<div class=numPages >Pages: $numpages </div>";
		
		$content .= "<div class=paginate>";
		
		if ($page > 1)
		{
			$content .= "<div class=prev><a href=$altgalleryscript?page=1>&lt;&lt;</a></div><div class=prev> <a href=$altgalleryscript?page=". ($page - 1) .">Prev</a> </div>";
		}
		for ($i=1; $i<=$numpages; $i++)
		{
			if ($page == $i)	$content .= "<div class=currentPage>$i</div>";
			else	$content .= "<a href=$altgalleryscript?page=$i><div class=pageNumber>$i</div></a> ";
		}
		if ($page < $numpages)
		{
			$content .= "<div class=next><a href=$altgalleryscript?page=". ($page + 1) .">Next</a></div>";
			$content .= "<div class=next><a href=$altgalleryscript?page=$numpages>&gt;&gt;</a></div> ";
		}
		
		$content .= "</div>";
	}
	
	$content .="</div>";
	
	if (!$conf[image][lightbox])
		$content .= "<br /><a href=\"$altgalleryscript?mode=slideshow&filename=" . urlencode($pictures[0][file]) . "\">[slideshow]</a>";
	$content .="<div class=credit>";
	if ($conf[gallery][show_credit])	$content .= $credit;
	$content .= "</div>";

	$content .= "</div>";
	

	return generate_page($template_text, $gallery_header, $conf[general][title], $content);
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

function check_dependencies() {

	global $common_folder;
	
	# check for GD library
	if (!function_exists("imagejpeg"))
		die("Error: looks like GD is not installed on this server");

	# check for local libraries
	if (!is_dir($common_folder))
		die("Error: QuickNail needs the $common_folder folder in order to work.");
		
	$needed_files = array("$common_folder/highslide-with-gallery.js", "$common_folder/highslide.css");	# should check for all necessarry files
	
	foreach ($needed_files as $file)
		if (!file_exists($file))
			die("Error: QuickNail cannot be used without '$file'");
}

/**
 * Loads config from a file into to a global variable.
 *
 * @global string $PHP_SELF
 * @global string $template_text
 * @global array $conf
 * @param string $configfile
 */
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

	if (!is_bool($conf[image][lightbox])) $conf[image][lightbox] = false;
	if (!is_integer($conf[gallery][picsperline])) $conf[gallery][picsperline] = 3;
	if (!is_integer($conf[gallery][picsperpage])) $conf[gallery][picsperpage] = 12;
	if (!is_integer($conf[gallery][slidespeed])) $conf[gallery][slidespeed] = 4;
	if (!is_integer($conf[image][enlargesize])) $conf[image][enlargesize] = 600;
	if (!is_bool($conf[image][clickfull])) $conf[image][clickfull] = true;

	if (!is_bool($conf[gallery][show_credit])) $conf[gallery][show_credit] = true;
	if (!is_integer($conf[gallery][thumbsize])) $conf[gallery][thumbsize] = 180;
	if (!is_bool($conf[gallery][show_image_names])) $conf[gallery][show_image_names] = false;
	if (!is_bool($conf[image][show_captions])) $conf[image][show_captions] = true;

	if (empty($conf[general][picturesdir])) $conf[general][picturesdir] = ".";
	if (empty($conf[general][thumbsdir]))	$conf[general][thumbsdir] = "thumbs";
	if (!is_bool($conf[image][prevent_enlarged_overscaling]))	$conf[image][prevent_enlarged_overscaling] = true;

	if ($conf[image][lightbox_dim_background] < 0 || $conf[image][lightbox_dim_background] > 1)
		$conf[image][lightbox_dim_background] = 0.75;
}

function qnprinthead() {
	global $qnout, $headprinted;
	$headprinted = true;
	print $qnout[head];
}

function qnprintbody() {
	global $qnout, $headprinted;
	if (!$headprinted)
		die("QuickNail Error: head not printed");

	print $qnout[body];
}

# MAIN starts here

$headprinted = false;
$altgalleryscript = ereg_replace("(.*\/)([^\/]*)","\\2", $_SERVER["SCRIPT_FILENAME"]);	// since there can be an index or custom

if (!$galleryasinclude) {

	$imagescript="qnail.php";
	$common_folder= "qcommon";

	check_dependencies();
	load_config("qconfig.ini");	# populates $config

	# check too see if there are not any images
	$pictures = generate_file_list($conf[general][picturesdir], $conf[general][thumbsdir], $sortby);

	if (count($pictures) == 0 )
		die("Error: No valid images in directory.");

	foreach ($pictures as $picture)
		$captions[$picture[file]] = $picture[caption];

	$mode = $_GET{mode};
	$filename = $_GET{filename};
	$max = $_GET{max};
	$w = $_GET{w};
	$h = $_GET{h};

	//$qnout = array();	// head, body, wholepage

	if ($mode == "image") printImage($filename);
	else if ($mode == "enlarge")	$qnout = enlargeImage($filename);
	else if ($mode == "slideshow")	$qnout = enlargeImage($filename, true);
	else							$qnout = showGallery();

	if (!$qncustom)
		print ($qnout[wholepage]);
}

?>
