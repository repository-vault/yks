<?

$path = $argv0;

//expose only themes' files (css/png)

$full = exyks_paths::resolve_public($path);
if(!is_file($full))
    die("No file here");

define('TYPE_ICO', "Content-Type: image/x-icon");


switch(files::ext($full)) {
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
  case 'png':
    header(TYPE_PNG);
    files::delivers($full);
    break; 
  case 'cur':
    header(TYPE_ICO);
    files::delivers($full);
    break; 
  case 'ico':
    header(TYPE_ICO);
    files::delivers($full);
    break; 
  case 'gif':
    header("Content-type:image/gif");
    files::delivers($full);
    break; 
  case 'jpeg': case 'jpg' :
    header(TYPE_JPEG);
    files::delivers($full);
    break; 
  case 'ttf' :
    header("Content-type:application/x-font-ttf");
    files::delivers($full);
    break; 
}


die;
