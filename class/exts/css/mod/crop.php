<?

class css_crop extends ibase {

  private $base_img;
  private $rule_img;
  function __construct($stylesheet, $ruleset) {
    $this->css = $stylesheet;

    $selector = $ruleset->selector;
    $theme_name = trim(trim($selector),'.');

    foreach($ruleset->rules as $rule) {
        if($rule['name'] == 'background-image') {
            $this->set_image($rule);
        }
        if($rule['name'] == 'background-crop') {
            $this->set_grid((string)$rule);
            $rule->dispose();
        }
    }



  }
  function set_image($rule_img){
    $this->rule_img  = $rule_img;
    $uri = css_parser::split_string((string) $rule_img);
    $uri = $uri['uri'];
    $val = exyks_paths::merge(dirname($this->css->get_path()).'/', $uri);
    $this->base_img = $val;
  }

  

  function get_stylesheet(){
    return $this->css;
  }
  
  public function write_cache(){ 
    $img = imgs::imagecreatefromfile($this->base_img);

    $img = imgs::imagecrop($img, $this->box_grid['x'], $this->box_grid['y'],
                $this->box_grid['w'], $this->box_grid['h']);

    $cache_rel  = "/imgs/crops";
    $cache_path = CACHE_PATH.$cache_rel;
    files::create_dir($cache_path);
    $crop_cache    = substr(md5($this->base_img.print_r($this->box_grid,1)),0,10).".png";
    $crop_filepath = "$cache_path/$crop_cache";
    imagepng($img, $crop_filepath);
    $crop_uri      = "path://cache{$cache_rel}/$crop_cache";
    $this->rule_img->set_value("url($crop_uri)");
  }



  function set_grid($values){
    list($x, $y, $w, $h) = explode(' ', $values);
    $this->box_grid = compact('x', 'y', 'w', 'h');
  }



}

