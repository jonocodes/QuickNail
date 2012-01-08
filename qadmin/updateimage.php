<?php

include("acommon.php");

function rotate_right90($im)
{
 $wid = imagesx($im);
 $hei = imagesy($im);
 $im2 = imagecreatetruecolor($hei,$wid);

 for($i = 0;$i < $wid; $i++)
 {
  for($j = 0;$j < $hei; $j++)
  {
   $ref = imagecolorat($im,$i,$j);
   imagesetpixel($im2,$hei - $j,$i,$ref);
  }
 }
 return $im2;
}

function rotate_left90($im)
{
 $wid = imagesx($im);
 $hei = imagesy($im);
 $im2 = imagecreatetruecolor($hei,$wid);

 for($i = 0;$i < $wid; $i++)
 {
  for($j = 0;$j < $hei; $j++)
  {
   $ref = imagecolorat($im,$i,$j);
   imagesetpixel($im2,$j, $wid - $i,$ref);
  }
 }
 return $im2;
}

function mirror($im)
{
 $wid = imagesx($im);
 $hei = imagesy($im);
 $im2 = imagecreatetruecolor($wid,$hei);

 for($i = 0;$i < $wid; $i++)
 {
  for($j = 0;$j < $hei; $j++)
  {
   $ref = imagecolorat($im,$i,$j);
   imagesetpixel($im2,$wid - $i,$j,$ref);
  }
 }
 return $im2;
}

function flip($im)
{
 $wid = imagesx($im);
 $hei = imagesy($im);
 $im2 = imagecreatetruecolor($wid,$hei);

 for($i = 0;$i < $wid; $i++)
 {
  for($j = 0;$j < $hei; $j++)
  {
   $ref = imagecolorat($im,$i,$j);
   imagesetpixel($im2,$i,$hei - $j,$ref);
  }
 }
 return $im2;
}

/**
 *
 * Rotates an image 90 degrees.
 * 
 * @param image resource $image
 * @param string $direction (must be 'cw' or 'ccw')
 * @return image resource
 */
function rotateImage($image, $direction) {
    $direction = strtolower($direction);
    $degrees = $direction == 'cw' ? 270 : ($direction == 'ccw' ? 90 : NULL);
    if(!$degrees)
        return $image;

    $width = imagesx($image);
    $height = imagesy($image);
    $side = $width > $height ? $width : $height;
    $imageSquare = imagecreatetruecolor($side, $side);
    imagecopy($imageSquare, $image, 0, 0, 0, 0, $width, $height);
    imagedestroy($image);

	if ($direction == 'cw')
		$imageSquare = rotate_right90($imageSquare);
	else # assume ccw
		$imageSquare = rotate_left90($imageSquare);

    $image = imagecreatetruecolor($height, $width);
    $x = $degrees == 90 ? 0 : ($height > $width ? 0 : ($side - $height));
    $y = $degrees == 270 ? 0 : ($height < $width ? 0 : ($side - $width));
    imagecopy($image, $imageSquare, 0, 0, $x, $y, $height, $width);
    imagedestroy($imageSquare);
    return $image;
}

function rotatepicture($pictid, $pictures, $direction) {

	$img = $pictures[$pictid][file];
	$thumb = $pictures[$pictid][thumbnail];

	if ($picnum<0 || $picnum > count($pictures))
		die("Invalid update parameter");

	if (!file_exists($img))
		die("error: image does not exist");


	print "rotating image...";

	$source = imagecreatefromjpeg($img);
    $width = imagesx($source);
    $height = imagesy($source);	
	$target = rotateImage($source, $direction);
	imagejpeg($target, $img, 95);

	print "done.";

	# only generate a thumbnail if the large one changed
	if (file_exists($thumb) && !preg_match("/mode\=image/", $thumb) )
		if ($width == imagesy($target) && $height == imagesx($target))	# bad assumption for square images
		{
			print " rotating thumbnail...";

			$source = imagecreatefromjpeg($thumb);
			$target = rotateImage($source, $direction);
			imagejpeg($target, $thumb, 95);

			print "done.";
		}

}

function deletepicture($pictid, $pictures, $deletethumb=true) {	// would be better to use the filename then the id

	$thumb = $pictures[$pictid][thumbnail];
	$img = $pictures[$pictid][file];

	if ($picnum<0 || $picnum > count($pictures))
		die("Invalid update parameter.");

	if (!file_exists($img))
		die("error: image does not exist.");

	if (unlink($img))
		print "image deleted. ";
	else
		print "there was an error deleting " . $img . ".";
		
	if ($deletethumb && file_exists($thumb) && !preg_match("/mode\=image/", $thumb) )
		if (unlink($thumb))
			print "thumbnail deleted.";
		else
			print "error deleting thumbnail.";

}


session_start();


$mode = $_REQUEST{mode};

$pictures = $_SESSION[pictures];
//$pictures = generate_file_list($conf[general][picturesdir], $conf[general][thumbsdir], $sortby);


switch ($mode) {
	case "delete":
		deletepicture($_REQUEST[picnum], $pictures, true);
		break;

	case "rotateright":
		rotatepicture($_REQUEST[picnum], $pictures, 'cw');
		break;

	case "rotateleft":
		rotatepicture($_REQUEST[picnum], $pictures, 'ccw');
		break;

	default:
		print "invalid mode";
		break;
}

//print "<br><a href=$_SERVER[HTTP_REFERER]>Back</a>";

?>
