<?		
	session_start();

	print "You are now logged out.<br><a href=index.php>Log in</a>";
	$_SESSION[authenticated] = false;
	unset($_SESSION[authenticated]);
?>
