<?

class xlsx_style {
  private $classes = array();


  private $_fonts        = array();
  private $_borders      = array();
  private $_fills        = array(array('type'=>'none'), array('type' => 'gray125')); //placeholders
  private $_alignments   = array();

  private $_styles = array(); //compact('border', 'fill', 'font', 'alignment');

  private static $border_styles = array('solid' => 'thin');
  private static $valign_styles = array('middle' => 'center');

  function __construct($css_str){
    $this->parse_css($css_str);
    $this->pick(''); //init zero style

  }

  private $css_default = array(
    'font-size'        => 11,
    'font-family'      => 'Calibri',
    'font-weight'      => null,
    'background-color' => null,
    'text-align'       => null,
    'vertical-align'   => null,
  );

  private $css_short = array(
    '#^font$#'    => array('font-family', 'font-size'),
    '#^border-(bottom|top|left|right)$#' => array('border-%s-width', 'border-%s-style', 'border-%s-color'),
  );

  private $css_styles = array(); //cache

  function css_merge($base, $css){
    $dest = $base;
    if($css['border'])
      foreach(array('top', 'bottom', 'left', 'right') as $side)
        $css["border-$side"] = $css['border'];
    foreach(array('width', 'style', 'color') as $battr)
      if(isset($css["border-$battr"]))
        foreach(array('top', 'bottom', 'left', 'right') as $side)
          $css["border-$side-$battr"] = $css["border-$battr"];

    foreach($css as $expr=>$values)
      foreach($this->css_short as $mask=>$fill)
        if(preg_match($mask, $expr, $out))
          foreach($fill as $i=>$k)
            if(isset($values[$i]))
              $css[sprintf($k, $out[1])] = array($values[$i]);

    $keys = array_keys($this->css_default);
    foreach(array('top', 'bottom', 'left', 'right') as $side)
    foreach(array('width', 'style', 'color') as $battr)
      $keys[] = "border-$side-$battr";

    foreach($keys as $k){
      if(isset($css[$k])) $dest[$k] = $css[$k][0];
    }

    return $dest;
  }

  function pick($css_expr){
    $classes      = array_filter(array_map('trim', explode(' ', $css_expr)));
    $classes_hash = join(' ', $classes);
    if(isset($this->css_styles[$classes_hash]))
      return $this->css_styles[$classes_hash];

    $css = $this->css_default;
    foreach($this->classes as $class_name=>$class){
      if(!in_array($class_name, $classes)) continue;
      $css = $this->css_merge($css, $class);
    }


    $font = $border = $fill = $alignment = null;
    foreach($this->_fonts as $k=>$v){ if($this->same('font', $v, $css)) $font = $k ;break;}
    if(is_null($font)) $font = $this->save('font', $css);

    foreach($this->_fills as $k=>$v) {if($this->same('fill', $v, $css)) $fill = $k;break;}
    if(is_null($fill)) $fill = $this->save('fill', $css);

    foreach($this->_borders as $k=>$v){ if($this->same('border', $v, $css)) $border = $k;break;}
    if(is_null($border)) $border = $this->save('border', $css);

    foreach($this->_alignments as $k=>$v){ if($this->same('alignment', $v, $css)) $alignment = $k;break;}
    if(is_null($alignment)) $alignment = $this->save('alignment', $css);

    $style = compact('font', 'border', 'fill', 'alignment');
    $style_id = array_search($style, $this->_styles, true);
    if($style_id === false) $style_id = array_push($this->_styles, $style) - 1;    

    rbx::ok("Pushing $classes_hash into hash");
    $this->css_styles[$classes_hash] = $style_id;
    return $style_id;
  }

  function save($type, $a){
    $format = "format_{$type}";
    $dest   = "_{$type}s";
    $a = $this->$format($a);
    $length  = array_push($this->$dest, $a);
    return $length-1;
  }
  function same($type, $a, $b){
    $format = "format_{$type}";
    $a = $this->$format($a);
    $b = $this->$format($b);
    return $a===$b;
  }


  function format_font($css){
    return array(
      'font-family'   => $css['font-family'],
      'font-size'     => $css['font-size'],
      'font-weight'   => $css['font-weight'],
    );
  }


  function format_fill($css){
    return array(
      'background-color' => $css['background-color'],
    );
  }

  function format_border($css){
    $out = array();
    foreach(array('top', 'bottom', 'left', 'right') as $side) {
      $out["border-$side-style"] = $css["border-$side-style"];
      $out["border-$side-color"] = $css["border-$side-color"];
    }
    return $out;
  }


  function format_alignment($css){
    return array(
      'text-align'     => $css['text-align'],
      'vertical-align' => $css['vertical-align'],
    );
  }



  function output_fills(){
    $fills = "<fills count='".count($this->_fills)."'>";
    foreach($this->_fills as $fill) {
      $fill_str = "<fill>";
      if(isset($fill['type'])) {
        $fill_str .="<patternFill patternType='{$fill['type']}'/>";
      } else {
        $color = $this->acolor($fill['background-color']);
        $fill_str .= "<patternFill patternType='solid'>
            <fgColor rgb='{$color}'/>
            <bgColor indexed='64'/>
          </patternFill>";
      }
      $fill_str .= "</fill>";
      $fills .= $fill_str;
    }
    $fills .= "</fills>";
    return $fills;
  }

  function output_borders(){
    $borders = "<borders count='".count($this->_borders)."'>";
    foreach($this->_borders as $border){
      $border_str = "<border>";
      foreach(array('left', 'right', 'top', 'bottom') as $side) {
        $style = $border["border-$side-style"];
        $color = $border["border-$side-color"];
        if(is_null($style)) {$border_str.="<$side/>"; continue; }
        $style = pick(self::$border_styles[$style], 'thin');
        $color = $this->acolor($color);
        $border_str .= "<$side style='$style'><color rgb='{$color}'/></$side>";
      }
      $border_str .="</border>";
      $borders .= $border_str;
    }
    $borders .="</borders>";
    return $borders;
  }

  function acolor($in){
    $in = strtoupper(substr($in,-6));
    if(!preg_match("#^[A-F0-9]{6}$#", $in))
      return "FF000000";
    return "FF".$in;
  }

  function output_styles(){
    $styles = "<cellXfs count='".count($this->_styles)."'>";
    foreach($this->_styles as $style){

      $css = array_merge(
          $this->_fonts[$style['font']],
          $this->_borders[$style['border']],
          $this->_fills[$style['fill']],
          $this->_alignments[$style['alignment']]
      );

      $style_str = "<xf numFmtId='0' xfId='0' applyFont='1'";
      $style_str .=" fontId='{$style['font']}'";
      $style_str .=" fillId='{$style['fill']}'";
      $style_str .=" borderId='{$style['border']}'";

      if($css['text-align'] || $css['vertical-align']) {
        $style_str .=" applyAlignment='1'><alignment";
        if($css['vertical-align'])
          $style_str .=" vertical='".pick(self::$valign_styles[$css['vertical-align']], $css['vertical-align'])."'";
        if($css['text-align'])
          $style_str .=" horizontal='{$css['text-align']}'";
        $style_str .= "/>";
        $style_str .= "</xf>";
      } else {
        $style_str .= "/>";
      }
      $styles .= $style_str;
    }

    $styles .= "</cellXfs>";
    return $styles;
  }

  function output_fonts(){
    //x14ac:knownFonts="1"
    $fonts =   "<fonts count='".count($this->_fonts)."'>";
    foreach($this->_fonts as $font) {
      $font_str = "<font>";
      if($font['font-weight'] == 'bold') $font_str .= "<b/>";
      $font_str .= "<sz val='{$font['font-size']}'/>";
      $font_str .= "<color theme='1'/>";
      $font_str .= "<family val='2'/>";
      $font_str .= "<scheme val='minor'/>";
      $font_str .= "<name val='{$font['font-family']}'/>";
      $font_str .= "</font>";
      $fonts .= $font_str;
    }
    $fonts .="</fonts>";
    return $fonts;
  }



  private function parse_css($str){
    $css    = css_parser::load_string($str);
    $css_xml = simplexml_load_string($css->outputXML());
    $classes = array();
    foreach($css_xml->ruleset as $ruleset){
      $selector = trim($ruleset['selector']);
      if(!preg_match("#\.([a-z0-9_-]+)#", $selector, $out)){
        rbx::error("Unsupported complexe selector '$selector', skipping");
        continue;
      } $class_name = $out[1];
      foreach($ruleset->declarations->rule as $rule){
        $values = array();
        foreach($rule->valuegroup->val as $val) $values[] = (string)$val;
        $classes[$class_name][(string)$rule['name']] = $values;
      }
    }
    $this->classes = $classes;
  }



  function output($file_path = false){
    $str = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.CRLF;
    $str .='<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac">'.CRLF;

    $str .= $this->output_fonts().CRLF;
    $str .= $this->output_fills().CRLF;
    $str .= $this->output_borders().CRLF;

    $str .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'.CRLF;
    $str .= $this->output_styles().CRLF;
    $str .= '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'.CRLF;
    $str .= '</styleSheet>';
    if(!$file_path)
      return $str;
    else file_put_contents($file_path, $str);
  }

  public static function init(){
    classes::register_class_path("css_parser", "exts/css/parser.php");
  }

}