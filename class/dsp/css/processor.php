<?

class css_processor {

  private static $entities;
  private $file_uri;
  private $file_base;

  private $file_contents;
  private $css;

  static function init(){
    classes::register_class_path("css_parser", "exts/css/parser.php");
    classes::register_class_path("css_box", "exts/css/mod/box.php");
    classes::register_class_path("css_crop", "exts/css/mod/crop.php");
    self::$entities = array();
    foreach(yks::$get->config->themes->exports->iterate('val') as $val)
        self::$entities["&{$val['key']};"] = $val['value'];
  }

  private function __construct($uri, $contents = false){
    $this->file_uri   = $uri;
    $this->file_base  = ends_with($this->file_uri, '/')
        ? $this->file_uri
        : dirname($this->file_uri).'/';

    css_parser::register_entities(self::$entities);

    if($contents) 
        $this->css    = css_parser::load_string($contents, $this->file_uri);
    else $this->css   = css_parser::load_file($this->file_uri);
  }


  static function delivers($path){
    $process = new css_processor($path);
    echo $process->output();
  }


  private function apply_hooks(){
    $this->resolve_boxes();
    $this->resolve_crops();
    $this->resolve_imports();
    $this->resolve_externals();
  }

  private function output(){
    $this->apply_hooks();
    return $this->css->output();
  }

  private function resolve_boxes(){
    $boxes = $this->css->xpath("//rule[starts-with(@name,'box')]/ancestor::ruleset[1]");

    foreach($boxes as $box) {
        $box = new css_box($this->css, $box);
        $box->write_cache();
    }
  }


  private function resolve_crops(){
    $boxes = $this->css->xpath("//rule[@name='background-crop']/ancestor::ruleset[1]");
    foreach($boxes as $box) {
        $box = new css_crop($this->css, $box);
        $box->write_cache();
    }
  }

  private function resolve_imports() {
    $imports = $this->css->xpath("//atblock[@keyword='import']");
    $replace = true;

    foreach($imports as $import) {
        $url = trim($import->expressions, "'\"");
        $path = exyks_paths::merge($this->file_base, $url);

        if($replace) { try {
            if(!is_file($path)) continue;
            $process = new css_processor($path);
            $process->apply_hooks();
            $this->css->replaces_statement($import, $process->css);
          } catch(Exception $e){
            //something is fuck up in the file behind, leave it.. :/
            $path = exyks_paths::expose($path);
            $import->set_expression("\"$path\"");
            $this->css->remove_child($import);
            $this->css->stack_at($import);
          }
        } else { 
          $path = exyks_paths::expose($path);
          $import->set_expression("\"$path\"");
        }
    }
  }

  private function resolve_externals(){
    $externals = $this->css->xpath("//val[starts-with(.,'url')]/ancestor::rule"); // yeah

    foreach($externals as $external) {
        foreach($external->values_groups as $gid=>$values) foreach($values as $i=>$value) {

            $uri = css_parser::split_string((string)$value); $uri = $uri['uri'];
            if(!$uri || preg_match("#^(/|https://)#", $uri))
                continue;
            
            $url  = pick($out[1], $out[2], $out[3]); $start = $out[0];
            $val = exyks_paths::merge($this->file_base,$uri);
            $val = exyks_paths::expose($val);
            $val = "url(\"$val\")";
            $external->set_value($val, $i, $gid);
        }
     }
  }

//inline style rewrite callback
  function style_rewrite($doc, $node){
    $contents = $node->nodeValue;

    try {
      $css = new self("path://public/a", $contents);
      $contents = $css->output();
    } catch(Exception $e){
      error_log("Corrupted inline css..."); 
      return;
    }
    $node->nodeValue= $contents;
  }

}





