<?


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
    $this->resolve_externals();
    $this->resolve_refs();
    return $this->file_contents;
  }
  
  private function resolve_externals(){
    $mask  = "#url\(\s*(?:\"([^\"]*)\"|'([^']*)\'|([^)]*))\s*\)#";
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

  private function resolve_refs() {
    $mask  = "#^@import\s+(?:\"([^\"]*)\"|'([^']*)$\')\s*(;|\$)#m";
    $match = preg_match_all($mask, $this->file_contents, $out, PREG_SET_ORDER);
    if(!$match) return;

    $matches = array();
    foreach($out as $m) $matches[$m[0]] = pick($m[1], $m[2]);

    foreach($matches as $replace=>&$val) {
        $val = exyks_paths::merge(dirname($this->file_uri).'/', $val);
        $val = exyks_paths::expose($val);
        $val = "@import \"$val\";";
     }

    $this->file_contents = strtr($this->file_contents, $matches);
  }
}