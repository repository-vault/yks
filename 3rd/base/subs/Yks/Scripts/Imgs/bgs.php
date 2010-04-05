<?php
header(HTTP_CACHED_FILE);



$color = hexdec($sub0);
$alpha = (int) $sub1;



$img   = imgs::imagecreatetruealpha(5,5);

$color = imgs::colordec($color);
$color = imgs::coloralpha($color['red'],$color['green'],$color['blue'],$alpha);

imagefill($img,0,0,$color);

header(TYPE_PNG);
imagepng($img);
imagedestroy($img);
die;
