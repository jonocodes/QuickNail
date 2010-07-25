<?php
/**
 *
 * QuickNail by Jono - jonojuggles@gmail.com
 *
 */

function logged_in() {
	if ($_SESSION[qn3gath] === true)
		return true;
	
	return false;
}

$loginform =<<<LOGINFORM
<html><body><br><br><br>
<center>
<form method=post action=index.php> Enter password: 
<input type=password size=20 name=password>
<input type=submit value=Submit>
</form>
</center>
</body></html>
LOGINFORM;

session_start();

$thisscript = ereg_replace("(.*\/)([^\/]*)","\\2", $_SERVER["SCRIPT_FILENAME"]);

$galleryasinclude=true;

include_once("../qnail.php");

if ($thisscript == "index.php")  # then run main
{	
	read_ini_file("../qconfig.ini", $conf);

	$password = trim($conf[general][password]);

	if (empty($password))
		die("The password must be set in the config file before this feature can be used.");

	if ($_REQUEST[password]) {
		if ($password == $_REQUEST[password])
			$_SESSION[qn3gath] = true;
		else
			print "incorrect password<br>";
	}

	if (logged_in()) {
		header('Location: manage.php');
	} else {
		print $loginform;
	}
}	# else, it is an include
?>
