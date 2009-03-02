
<h2>Configuration Variables</h2>

The following variables can be found in config.ini. Feel free to change the values in your config file.<br><br>

<table border=1 cellpadding=2 cellspacing=0 align=center>
<tr><th>variable</th><th>values/type</th><th>default</th><th>description</th></tr>
<?
$lines = file("vars.txt");

foreach ($lines as $line) {
	$cols = split("\t", $line);
	
	if (sizeof($cols) == 1)
		print "<tr><td colspan=4 height=40 align=center>" . $cols[0] . "</td></tr>";
	else {
		print "<tr>";
		foreach ($cols as $thiscol)	print "<td align=left valign=top>$thiscol</td>";
		print "</tr>";
	}
}
?>
</table>


<h2>Templates</h2>

Users can define a layout template in an HTML file. Here is an example:<br><br>

<font color=blue>
<?
	$templ_lines = file("qtemplate.html");
	foreach ($templ_lines as $line)
		print htmlspecialchars($line). "<br>";
?>
</font>

Templates must contain the % QUICKNAIL_TITLE %, % QUICKNAIL_HEAD %, and % QUICKNAIL_MAINCONTENT % sections in order to work. These are needed by QuickNail. You are free to change everything around those sections.


<h2>Thumbnails and Captions</h2>

Thumbnails are generated every time the gallery is visited. Generating them on the fly is convenient if you change pictures often. However, these images are slower to load and are not the best quality. To avoid these problems, users can pre-cache their thumbnails. This is done by having QuickNail pre-generate thumbnails one time only. These images are stored in a thumbnail directory.
<br><BR>
Users can also create captions for each image. Pre-caching and captions are managed through the admin interface. To set up the interface, do the following:
<ul>
<li>Edit <i>config.ini</i> to set a password.
<li>Upload the <i>qadmin</i> directory to the server in the same directory as <i>index.php</i> if you have not already.
<li>Visit <i>qadmin</i> in your browser. The username is admin.
</ul>
Note: When generating thumbnails for hundreds of images, the webserver may time out part way through. If this happens, just generate them again and the script will pick up where it left off.

<h2>Troubleshooting</h2>

If you have any questions or comments please feel free to contact me.

<h3>File Permissions</h3>
If there are any errors about permissions, you might need to change permissions of the images. This can be done easily in an FTP client by changing a file's attributes or setting the CHMOD. We recommend setting the permission to 666. Or, if you have SSH access, you can type "chmod 666 *.jpg" in the directory. Captions are embedded directly into the jpg files via something called an IPTC. This means images must have writeable permissions in order to be able to make caption changes.

