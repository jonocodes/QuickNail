<?

// depricated
function generate_htpasswd($password) {
	
	exec("htpasswd -cb .htpasswd admin $password");
}

// depricated
function generate_htaccess() {	// new file creation problem
	
	$cwd = getcwd();

	exec('echo AuthUserFile ' . $cwd . '/.htpasswd > .htaccess');
	exec('echo AuthType Basic >> .htaccess');
	exec('echo AuthName \"QuickNail Admin Area\" >> .htaccess');
	exec('echo Require valid-user >> .htaccess');
}

// depricated
function old_login() {
    
	$conf = parse_ini_file("../config.ini");	// should really use common function
	$password = trim($conf[password]);

	if (empty($password))
		die("The password must be set in the config file before this feature can be used.");

	generate_htpasswd($password);
	
	if (!file_exists(".htaccess"))
		generate_htaccess();
	
	header('Location: manage.php');
}

function logged_in() {
	if ($_SESSION[authenticated] === true) {
		return true;
	}
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

//print_r($_REQUEST);

if ($thisscript == "index.php")  # then run main
{
	$conf = parse_ini_file("../config.ini");	// should really use common function
	$password = trim($conf[password]);

	if (empty($password))
		die("The password must be set in the config file before this feature can be used.");

	if ($_REQUEST[password]) {
		if ($password == $_REQUEST[password])
			$_SESSION[authenticated] = true;
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
