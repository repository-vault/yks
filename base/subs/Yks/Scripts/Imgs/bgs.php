<?php
header(HTTP_CACHED_FILE);


include_once CLASS_PATH."/imgs/functions.php";
include_once CLASS_PATH."/imgs/imgs.php";


$color=hexdec($sub0);
$alpha=(int) $sub1;



$img=imagecreatetruealpha(5,5);

$color=colordec($color);
$color=coloralpha($color['red'],$color['green'],$color['blue'],$alpha);

imagefill($img,0,0,$color);

header(TYPE_PNG);
imagepng($img);
imagedestroy($img);
die;
