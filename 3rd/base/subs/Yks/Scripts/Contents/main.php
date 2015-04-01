<?php

$path = $argv0;

//expose only themes' files (css/png)

$full = exyks_paths::resolve_public($path);
if(!is_file($full))
    die("No file here");

define('TYPE_ICO',  'Content-Type: image/x-icon');
define('TYPE_GIF',  'Content-type: image/gif');
define('TYPE_SVG',  'Content-Type: image/svg+xml');
define('TYPE_TTF',  'Content-Type: application/x-font-ttf');
define('TYPE_OTF',  'Content-Type: application/x-font-opentype');
define('TYPE_WOFF', 'Content-Type: application/font-woff');
define('TYPE_EOT',  'Content-Type: application/vnd.ms-fontobject');


$dummies = array(
    'png'  => TYPE_PNG,
    'json' => TYPE_JSON,
    'cur'  => TYPE_ICO,
    'ico'  => TYPE_ICO,
    'gif'  => TYPE_GIF,
    'jpeg' => TYPE_JPEG,
    'jpg'  => TYPE_JPEG,
    'svg'  => TYPE_SVG,
    'ttf'  => TYPE_TTF,
    'otf'  => TYPE_OTF,
    'woff' => TYPE_WOFF,
    'eot'  => TYPE_EOT
);

$ext = files::ext($full);

if(isset($dummies[$ext])) {
    header($dummies[$ext]);
    files::delivers($full);
    die;
} switch($ext) {
  case 'styl':
    header(TYPE_CSS);
    files::highlander();
    try {
        css_processor::delivers_stylus($path);
    } catch(Exception $e){
        error_log($e->getMessage());
        files::delivers($full);
    }
    break;
  case 'css':
    header(TYPE_CSS);
    files::highlander();
    try {
        css_processor::delivers($path);
    } catch(Exception $e){
        error_log($e->getMessage());
        files::delivers($full);
    }
    break;
  default:
    throw new Exception("Cannot render file of type $ext");
}


die;
