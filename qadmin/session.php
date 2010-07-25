<?php
session_start();
if ($_SESSION[qn3gath] === true)
	echo "active";
else
	echo "expired";
?>
