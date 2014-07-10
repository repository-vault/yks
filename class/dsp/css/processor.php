<?

class css_processor {

  const CB_INLINE_EXTERNALS = 'inline_externals';
  const CB_RESOLVE_IMPORTS  = 'resolve_imports';

  private static $entities;
  private $file_uri;
  private $file_base;

  private $file_contents;
  public $css;
  private $hooks = array();

  static function init(){
    classes::register_class_path("css_parser", "exts/css/parser.php");
    classes::register_class_path("css_box", "exts/css/mod/box.php");
    classes::register_class_path("css_crop", "exts/css/mod/crop.php");
    self::$entities = array();
    foreach(yks::$get->config->themes->exports->iterate('val') as $val)
        self::$entities["&{$val['key']};"] = $val['value'];
  }

  public function __construct($uri = "path://public"){
    $this->file_uri   = $uri;
    $this->file_base  = ends_with($this->file_uri, '/')
        ? $this->file_uri
        : dirname($this->file_uri).'/';

    css_parser::register_entities(self::$entities);

  }

  private function register_std_hooks() {
   $this->register_hook(array($this, "resolve_boxes" ));
   $this->register_hook(array($this, "resolve_crops" ));
   $this->register_hook(array($this, "resolve_imports" ));
   $this->register_hook(array($this, "resolve_externals" ));

   $this->register_hook(array($this, "resolve_names" ));
   $this->register_hook(array($this, "resolve_values" ));
  }

  function register_hook($callback){
    $this->hooks[] = $callback;
  }

  private function apply_hooks() {
    foreach($this->hooks as $callback)
      call_user_func($callback);
  }

  private function parse($contents = null) {
    if(!is_null($contents))
        $this->css    = css_parser::load_string($contents, $this->file_uri);
    else $this->css   = css_parser::load_file($this->file_uri);
  }

  function process($contents = null) {
    $this->parse($contents);

    $this->apply_hooks();
    return $this->css->output();
  }

  static function delivers($path){
    $process = new css_processor($path);
    $process->register_std_hooks();
    echo $process->process();
  }

  private function resolve_boxes(){
    $boxes = $this->css->xpath("//atblock[@keyword='box']//rule[starts-with(@name,'box')]/ancestor::ruleset[1]");

    foreach($boxes as $box) try {
        $box = new css_box($this->css, $box);
        $box->write_cache();
    }catch(Exception $e){
    syslog(LOG_WARNING, "Invalid css box : ".$e->getMessage()); }
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

            foreach($this->hooks as $cb)
              if(is_array($cb)
                && $cb[0] === $this
                && method_exists ($this, $cb[1])
                && true)
                  $process->register_hook(array($process, $cb[1]));

            $process->parse();
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

  private function inline_externals(){
    $externals = $this->css->xpath("//atblock[@keyword='font-face']//val[starts-with(.,'url')]/ancestor::rule"); // yeah
    foreach($externals as $external) {
      foreach($external->values_groups as $gid=>$values) foreach($values as $i=>$value) {
        $uri = css_parser::split_string((string)$value); $uri = $uri['uri'];
        $uri = exyks_paths::resolve($uri);
        $val = "url(\"$uri\")";
        $external->set_value($val, $i, $gid);
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

  /* Cross navigator resolvers */
  private function resolve_names() {
    $names = array(
      'border-radius' => array(
        'webkit' => '-webkit-border-radius',
        'gecko' => 'border-radius',
        'presto' => '-o-border-radius',
        'trident' => '-khtml-border-radius',
      ),
      'box-shadow' => array(
        'webkit' => '-webkit-box-shadow',
        'gecko' => 'box-shadow',
        'presto' => '-o-box-shadow',
        'trident' => '-khtml-box-shadow',
      ),
      'transition' => array(
        'webkit' => '-webkit-transition',
        'gecko' => '-moz-transition',
        'presto' => '-o-transition',
        'trident' => '-khtml-transition',
      ),
      'appearance' => array(
        'webkit' => '-webkit-appearance',
        'gecko' => '-moz-appearance',
        'presto' => '-o-appearance',
        'trident' => '-khtml-appearance',
      ),
      'text-shadow' => array(
        'webkit' => '-webkit-text-shadow',
        'gecko' => '-moz-text-shadow',
        'presto' => '-o-text-shadow',
        'trident' => '-khtml-text-shadow',
      ),
    );

    foreach ($names as $name => $changes) {
      $new_name = array_key_exists(ENGINE, $changes) ? $changes[ENGINE] : $name;
      $boxes = $this->css->xpath("//rule[@name='$name']");
      foreach ($boxes as $box) {
        $box->set_name($new_name);
      }
    }
  }

  private function resolve_values() {
    $values = array(
      'linear-gradient' => array(
        'webkit' => '-webkit-linear-gradient',
        'gecko' => '-moz-linear-gradient',
        'presto' => '-o-linear-gradient',
        'trident' => '-khtml-linear-gradient',
      ),
    );

    foreach ($values as $value => $changes) {
      $new_name = array_key_exists(ENGINE, $changes) ? $changes[ENGINE] : $value;
      $boxes = $this->css->xpath("//val[starts-with(.,'$value')]/ancestor::rule");
      foreach ($boxes as $box) {
        $new_values = self::replace_tree($value, $new_name, $box->get_values_groups(), false);
        $box->set_values_group($new_values);
      }
    }
  }

//inline style rewrite callback
  function style_rewrite($doc, $node){
    $contents = $node->nodeValue;

    try {
      $css = new self();
      $css->register_std_hooks();
      $contents = $css->process($contents);
    } catch(Exception $e){
      syslog(LOG_INFO, "Corrupted inline css...");
      return;
    }
    $node->nodeValue= $contents;
  }

  private static function replace_tree($search = '', $replace = '', $array = false, $replace_keys = false) {
    if (!is_array($array)) return str_replace($search, $replace, $array);

    $new_array = array();
    foreach ($array as $key => $value) {
      $new_key = $replace_keys ? str_replace($search, $replace, $key) : $key;
      $new_array[$new_key] = self::replace_tree($search, $replace, $value, $replace_keys);
    }

    return $new_array;
  }

}





