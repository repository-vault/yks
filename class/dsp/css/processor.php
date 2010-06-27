<?


class css_processor {
  private $file_path;
  private $file_name;
  private $file_directory;
  private $file_contents;

  static function init(){
    classes::register_class_path("css_parser", "exts/css/parser.php");
    classes::register_class_path("css_box", "exts/css/box.php");
  }

  function __construct($uri, $contents = false){
    $this->file_uri       = $uri;

    //$this->file_path      = exyks_paths::resolve($this->file_uri);

    $this->file_name      = basename($file);
    $this->file_directory = dirname($file);

    //if(!$contents) $contents = file_get_contents($this->file_path);

    $this->css            = css_parser::parse_file($this->file_uri);
  }

  function output(){
    $this->resolve_boxes();
    $this->resolve_imports();
    $this->resolve_externals();
    echo $this->css->output();
  }

  private function resolve_boxes(){
    $boxes = $this->css->xpath("//rule[starts-with(@name,'box')]/parent::*");
    foreach($boxes as $box) {
        $box = new css_box($this->css, $box);
        $box->write_cache();
    }
  }

  private function resolve_imports() {
    $imports = $this->css->xpath("//atblock[@keyword='import']");
    foreach($imports as $import) {
        $url = trim($import->expressions, "'\"");
        $path = exyks_paths::merge(dirname($this->file_uri).'/', $url);
        $path = exyks_paths::expose($path);
        $import->set_expression("\"$path\"");
    }
  }

  private function resolve_externals(){
    $externals = $this->css->xpath("//rule[starts-with(.,'url')]"); //!
    foreach($externals as $external) {
        $mask  = "#url\(\s*(?:\"([^\"]*)\"|'([^']*)\'|([^)]*))\s*\)#";
        if(!preg_match($mask, (string)$external, $out))
            continue;
        $url  = pick($out[1], $out[2], $out[3]);
        $val = exyks_paths::merge(dirname($this->file_uri).'/', $url);
        $val = exyks_paths::expose($val);
        $val = "url(\"$val\")";
        $external->set_value($val);
     }
  }

//inline style rewrite callback
  function style_rewrite($doc, $node){
    $contents = $node->nodeValue;
    $css = new self("path://public", $contents);
    $contents = $css->output();
    $node->nodeValue= $contents;
  }

}