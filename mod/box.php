<?

class css_box extends ibase {
  private $box_theme;

  private $box_grid;
  private $box_image;
  private $css;
  private $box_crops = array();

  function __construct($stylesheet, $ruleset) {
    $this->css = $stylesheet;

    $selector = $ruleset->selector;
    $theme_name = trim(trim($selector),'.');

    foreach($ruleset->rules as $rule) {
        if($rule['name'] == 'box-image')
            $this->set_image((string)$rule);
        if(starts_with($rule['name'], 'box-grid'))
            $this->set_grid(trim(strip_start($rule['name'],'box-grid'),'-'), (string)$rule);

        if($rule['name'] == 'box-crop') //key from, x, y, w, h
            $this->box_crops[] = $rule->values;
    }
    $this->set_theme($theme_name);

    $ruleset->dispose();
  }

  function set_theme($theme){
    $this->box_theme = $theme;
  }

  function set_image($css_target){
    $uri = css_parser::split_string($css_target); $uri = $uri['uri'];
    $val = exyks_paths::merge(dirname($this->css->get_path()).'/', $uri);
    $this->box_image = $val;
  }


  function get_stylesheet(){
    return $this->css;
  }
  
  public function write_cache(){ 

    $theme_name = $this->box_theme;
    $theme_path = "path://cache/themes/$theme_name";
    $css_file   = "$theme_path/box.css";

    files::create_dir($theme_path);
    $base_img = imgs::imagecreatefromfile($this->box_image);

    $box_w = imagesx($base_img); $box_h = imagesy($base_img);

    list($box_xl, $box_xm, $box_xr) = $this->box_grid['x'];
    list($box_yu, $box_ym, $box_yd) = $this->box_grid['y'];


    $todo = array(
        'lu' => array(0, 0, $box_xl, $box_yu),
        'ld' => array(0, $box_h-$box_yd, $box_xl, $box_yd),
        'lm' => array(0, $box_yu, $box_xl, $box_ym),

        'ru' => array($box_w-$box_xr, 0, $box_xr, $box_yu),
        'rm' => array($box_w-$box_xr, $box_yu, $box_xr, $box_ym),
        'rd' => array($box_w-$box_xr, $box_h-$box_yd, $box_xr, $box_yd),

        'mu' => array($box_xl,0,$box_xm,$box_yu),
        'md' => array($box_xl, $box_h-$box_yd, $box_xm, $box_yd),
        'mm' => array($box_xl,$box_yu,$box_xm,$box_ym),
    );
        //extras parts
    foreach($this->box_crops as $crop_infos){
        $crop_key = $crop_base = $crop_x = $crop_y = $crop_w = $crop_h = null;
        list($crop_key, $crop_base, $crop_x, $crop_y, $crop_w, $crop_h) = $crop_infos;
        $box = array($crop_x, $crop_y, $crop_w, $crop_h);
        if($from = $todo[$crop_base]) 
            $box = array(pick($crop_x, $from[0]) + $from[0], pick($crop_y, $from[1]),
                         pick($crop_w, $from[2]) - $crop_x, pick($crop_h, $from[3]));
        $todo[$crop_key] = $box;
    }

    $theme_css = "";
    foreach($todo as $key=>$data) {
        $theme_css .= ".{$theme_name}_$key {background-image:url($key.png);}\n";
        $tmp        = imgs::imagetrunk($base_img, $data[0], $data[1], $data[2], $data[3]);
        $file       = "$theme_path/$key.png";
        imagepng($tmp, exyks_paths::resolve($file));
        imagedestroy($tmp);
    }
    $theme_css .= ".{$theme_name}_d {line-height:0px}";
    $theme_css .= ".{$theme_name}_d * {background-repeat:repeat-x}";

    $theme_css.=".{$theme_name}_lm {width:{$box_xl}px;} .{$theme_name}_rm {width:{$box_xr}px;}\n";
    $theme_css.=".{$theme_name}_mu {height:{$box_yu}px;} .{$theme_name}_md {height:{$box_yd}px;}\n";
    $theme_css.=".{$theme_name}_rd {height:{$box_yd}px;width:{$box_xr}px; }\n";


    $at_rule = new at_rule("import", "\"$css_file\"");
    $this->css->stack_at($at_rule);

    file_put_contents($css_file, $theme_css);
  }



  function set_grid($space, $values){
    $this->box_grid[$space] = explode(' ', $values);
  }



}