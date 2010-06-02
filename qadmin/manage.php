<?
/**
 *
 * QuickNail by Jono - jonojuggles@gmail.com
 *
 */

include_once("acommon.php");

session_start();

$script = ereg_replace("(.*\/)([^\/]*)","\\2", $_SERVER["SCRIPT_FILENAME"]);

$mode = $_REQUEST{mode};

//if (empty($_SESSION[pictures]))
	$_SESSION[pictures] = generate_file_list($conf[general][picturesdir], $conf[general][thumbsdir], $sortby, true);

$pictures = $_SESSION[pictures];


if ($mode == "logout") {
	print "You are now logged out.<br><a href=index.php>Log in</a><br><a href=../qnail.php>View Gallery</a>";
	$_SESSION[qn3gath] = false;
	unset($_SESSION[qn3gath]);
	exit;
}


if (empty($pictures))
	die("There are no pictures in pictures directory.");

?>

<style type="text/css">

body{
margin: 0;
padding: 0;
border: 0;
overflow: hidden;
height: 100%; 
max-height: 100%; 
}

#framecontent{
position: absolute;
top: 0;
bottom: 0; 
left: 0;
width: 200px; /*Width of frame div*/
height: 100%;
overflow: hidden; /*Disable scrollbars. Set to "scroll" to enable*/
background: #cc9;
color: white;
}

#maincontent{
position: fixed;
top: 0; 
left: 200px; /*Set left value to WidthOfFrameDiv*/
right: 0;
bottom: 0;
overflow: auto; 
background: #fff;
}

.innertube{
margin: 15px; /*Margins for inner DIV inside each DIV (to provide padding)*/
}

#framecontent h1 {
color: #330;
font-size: 22px;
}

#framecontent h2 {
color: #330;
font-size: 18px;
}

ul.qmenu{
	list-style: none;
	margin-left: 0;
	padding-left: 1em;
	text-indent: -1em;
}

ul.qmenu li:before {
	content: "\00BB \0020";
}


* html body{ /*IE6 hack*/
padding: 0 0 0 200px; /*Set value to (0 0 0 WidthOfFrameDiv)*/
}

* html #maincontent{ /*IE6 hack*/
height: 100%; 
width: 100%; 
}

</style>


<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="acommon.js"></script>

</head>

<body>


<div id="framecontent">
<div class="innertube">

<h1>QuickNail Admin</h1>
<h2><? echo $conf[general][title]; ?></h2>

<br>
<ul class=qmenu>
	<li><a href=<? echo $script ?>>Summary</a></li>
	<li><a href=<? echo $script ?>?mode=settings>Settings</a></li>
	<li><a href=<? echo $script ?>?mode=showcaptions>Manage Images</a></li>
	<li><a href=<? echo $script ?>?mode=upload>Upload</a></li>
	<li><a href=<? echo $script ?>?mode=checkthumbs>Thumbnails</a>
		<ul>
			<li><a href=<? echo $script ?>?mode=checkthumbs>Check</a></li>
			<li><a href=<? echo $script ?>?mode=generatemissingthumbs>Generate Missing</a></li>
			<li><a href=# onclick="confirmation('regenerate all thumbnails', '<? echo $script ?>?mode=regeneratethumbs')">Re-generate All</a></li>
			<li><a href=# onclick="confirmation('delete all thumbnails', '<? echo $script ?>?mode=deletethumbs')">Delete All</a></li>
		</ul>
	</li>
</ul>


<ul class=qmenu>
	<li><a href=../qnail.php >View gallery</a></li>
	<li><a href=<? echo $quicknail_homepage ?> >QuickNail Home Page</a></li>
	<li><a href=<? echo $quicknail_homepage ?>/updates.php?fromversion=<? echo $quicknail_version ?> >QuickNail Updates</a></li>
	<li><a href=<? echo $script ?>?mode=logout>Logout</a></center></li>
</ul>

</div>
</div>


<div id="maincontent">
<div class="innertube">

<table border=0 width=85% height=90% align=center><tr><td td valign=top>

<?


if ($mode == "showcaptions") {
	print "<h3>Manage Images</h3>";
	show_all_captions( $pictures );
}
else if ($mode == "updatecaptions") {

	print "<h3>Manage Images</h3>";

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
else if ($mode == "upload") {
?>
<h3>Upload</h3>
<p>Select a file to upload. It must be a jpg or gif.</p>
	<form action="" enctype="multipart/form-data" method="post">
		File <input type="file" name="image" value="" />
		<br />
		<br />
		<input type="submit" name="submit" value="Upload" />
	</form>

<?php

	  $allowedExtensions = array("gif", "jpg", "jpeg");
	  foreach ($_FILES as $file) {
	    if ($file['tmp_name'] > '') {
	      if (!in_array(end(explode(".",
	            strtolower($file['name']))),
	            $allowedExtensions)) {
	       die(
		$file['name'].' is an invalid file type!<br/>'
	    	);
	      }
	    }
	  }
	move_uploaded_file($_FILES['image']['tmp_name'],"../gallery_images/".$_FILES['image']['name']);

}
else if ($mode == "checkthumbs") {
	print "<h3>Thumbnails</h3>";
	checkthumbs($pictures);
}
else if ($mode == "generatemissingthumbs"){
	print "<h3>Thumbnails</h3>";
	generatethumbs($pictures, $conf[general][picturesdir], $conf[general][thumbsdir], false);
}
else if ($mode == "regeneratethumbs"){
	print "<h3>Thumbnails</h3>";
	generatethumbs($pictures, $conf[general][picturesdir], $conf[general][thumbsdir], true);
}
else if ($mode == "deletethumbs"){
	print "<h3>Thumbnails</h3>";
	deletethumbs($pictures, $conf[general][picturesdir], $conf[general][thumbsdir]);
} 
else if ($mode == "settings") {
	print "<h3>Settings</h3>";
	//showgeneralsettings();
	settings();
} else {
	showsummary($pictures, $conf[general][picturesdir], $conf[general][thumbsdir]);
}

?>

</td></tr></table>


</div>
</div>

</body>
</html>

