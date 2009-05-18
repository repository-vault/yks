<?
include "$class_path/dsp/css/compressor.php";
include_once "$class_path/stds/files.php";


$file_url = "http://commons.newdev.local/css/Ivs/global.css";
//$file_url="/css/Ivs/global.css";
$str=compute_css($file_url);
die($str);