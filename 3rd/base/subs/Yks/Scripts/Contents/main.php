<?

$path = $argv0;

//expose only themes' files (css/png)

$full = exyks_paths::resolve_public($path);
if(!is_file($full))
    die("No file here");


switch(files::ext($full)) {
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
  case 'jpeg': case 'jpg' :
    header(TYPE_JPEG);
    files::delivers($full);
    break; 
}


die;