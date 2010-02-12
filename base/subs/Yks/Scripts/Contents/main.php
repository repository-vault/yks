<?

$hash = $sub0;
$path = $argv0;


$full = exyks_paths::resolve($path);

$check = crpt("$path/$full", FLAG_FILE);
if($check != $hash) die("Invalid hash");

$ext = files::ext($full);
if($ext == "css") {


    header(TYPE_CSS);

    $process = new css_processor($path);
    $process->resolve_externals();
    echo $process->output();
    die;
}

if($ext == "png")
    header(TYPE_PNG);


class css_processor {
  private $file_path;
  private $file_name;
  private $file_directory;
  private $file_contents;

  function __construct($uri){
    $this->file_uri       = $uri;

    $this->file_path      = exyks_paths::resolve($this->file_uri);

    $this->file_name      = basename($file);
    $this->file_directory = dirname($file);
    $this->file_contents  = file_get_contents($this->file_path);
  }

  function output(){
    return $this->file_contents;
  }
  
  function resolve_externals(){
    $mask  = "#url\(\s*(?:\"([^\"]*)\"|'([^']*)$\'|([^)]*))\s*\)#";
    $match = preg_match_all($mask, $this->file_contents, $out, PREG_SET_ORDER);
    if(!$match) return;
    $matches = array();
    foreach($out as $m) $matches[$m[0]] = pick($m[1], $m[2], $m[3]);

    //path://users/www/Users/users.css

    foreach($matches as $replace=>&$val) {
        $val = exyks_paths::merge(dirname($this->file_uri).'/', $val);
        $val = exyks_paths::expose($val);
        $val = "url(\"$val\")";
     }
    $this->file_contents = strtr($this->file_contents, $matches);

  }
}

files::delivers($full);