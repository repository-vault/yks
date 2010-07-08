<?

class css_processor {

  private $file_uri;
  private $file_base;

  private $file_contents;
  private $css;

  static function init(){
    classes::register_class_path("css_parser", "exts/css/parser.php");
    classes::register_class_path("css_box", "exts/css/box.php");
  }

  private function __construct($uri, $contents = false){

    $this->file_uri   = $uri;
    $this->file_base  = ends_with($this->file_uri, '/')
        ? $this->file_uri
        : dirname($this->file_uri).'/';


    if($contents) 
        $this->css    = css_parser::load_string($contents, $this->file_uri);
    else $this->css   = css_parser::load_file($this->file_uri);
  }


  static function delivers($path){
    $process = new css_processor($path);
    echo $process->output();
  }


  private function output(){
    $this->resolve_boxes();
    $this->resolve_imports();
    $this->resolve_externals();
    return $this->css->output();
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
        $path = exyks_paths::merge($this->file_base, $url);
        $path = exyks_paths::expose($path);
        $import->set_expression("\"$path\"");
    }
  }

  private function resolve_externals(){
    $externals = $this->css->xpath("//val[starts-with(.,'url')]/ancestor::rule"); // yeah

    foreach($externals as $external) {
        foreach($external->values_groups as $gid=>$values) foreach($values as $i=>$value) {

            $mask  = "#url\(\s*(?:\"([^\"]*)\"|'([^']*)\'|([^)]*))\s*\)#";
            if(!preg_match($mask, (string)$value, $out))
                continue;
            $url  = pick($out[1], $out[2], $out[3]); $start = $out[0];
            $val = exyks_paths::merge($this->file_base, $url);
            $val = exyks_paths::expose($val);
            $val = "url(\"$val\")";
            $external->set_value($val, $i, $gid);
        }
     }
  }

//inline style rewrite callback
  function style_rewrite($doc, $node){
    $contents = $node->nodeValue;
    $css = new self("path://public/a", $contents);
    $contents = $css->output();
    $node->nodeValue= $contents;
  }

}