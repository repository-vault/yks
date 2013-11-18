<?php

class tpls {
  const REPLACE = 1;
  const ERASE = 2;
  const TOP = 3;
  const STD = 0;

  static $nav;
  static private $top    = array();
  static private $bottom = array();
  static $body = false; //if!tpls::$body
  private static $customs = array();

  static private $paths = array('search'=>array(), 'replace'=>array());

  static public $entities = array();
  static private $cmds = array(
    'top'    => array(
      'replace'=> 'array_pop',
      'action'=>'array_push' ),
    'bottom' => array(
      'replace'=> 'array_shift',
      'action'=>'array_unshift' ),
  );

  static function top($href, $where = tpls::STD, $render_mode = 'full'){
    self::tpl_add("top", $href, $where, $render_mode);
  }

  static function bottom($href, $where = tpls::STD, $render_mode = 'full'){
    self::tpl_add("bottom", $href, $where, $render_mode);
  }


  static function tpl_add($side, $href, $where, $render_mode){
    if($render_mode=='all') $render_mode = array_keys(self::$$side);
    if(is_array($render_mode)) {
      foreach($render_mode as $mode)self::tpl_add($side, $href, $where, $mode);
      return;
    }

    $tpl_ref = &self::${$side}[$render_mode]; if(!$tpl_ref) $tpl_ref = array();
    $cmds    = self::$cmds[$side];

    if($where==self::REPLACE)  call_user_func_array($cmds['replace'], array(&$tpl_ref));
    elseif($where==self::ERASE)  $tpl_ref = array();
    elseif($where==self::TOP) return array_unshift($tpl_ref, self::tpl($href));
    call_user_func_array($cmds['action'], array(&$tpl_ref, self::tpl($href)) );
  }


  static function export_list($render_mode = "full"){
    $list = array_merge(
      (array)tpls::$top[$render_mode],
      array(tpls::$body),
      (array)tpls::$bottom[$render_mode]); 
    return $list;
  }

  static function body($href, $raw=false) {
    self::$body = $raw?$href:self::tpl($href);
  }
  static function page_def($subs_file){
    exyks::$page_def = $subs_file;
  }
  public static function css_add($href, $media=false){
    if(starts_with($href, "path://")) $href = exyks_paths::expose($href, TYPE_CSS);

    $tmp = exyks::$head->styles->addChild("css");
    if($media)$tmp['media']=$media;
    $tmp['href']=$href;
  }
  public static function css_clean(){
    exyks::$head->styles = null;
  }

  /* register entities (k=>v) that will be available in &k; for .tpl file */
  public static function export($vals){
    if(!$vals) return;
    self::$entities = array_merge(self::$entities, $vals);
  }

  static function js_add($href, $defer=false){
    if($href == "/?/Yks/Scripts/Js") {
      $href .= "//".substr(data::fetch("JS_CACHE_KEY"),0,5);
      $href .= "|{'Yks-Language':'&USER_LANG;'}";
    }

    $tmp = exyks::$head->scripts->addChild("js");
    if($defer)$tmp['defer']="true";
    $tmp['src']=$href;
  }

  static function call($page, $vars=array()){
    global $href_fold, $class_path, $action;
    $href=$page;
    extract($vars);
    include "subs/$page.php";
    include "tpls/$page.tpl";
  }

  static function tpl($tpl){
    $tpl = "/".ltrim("$tpl.tpl","/");
    $tmp = preg_replace(self::$paths['search'], self::$paths['replace'], $tpl);

    return $tmp == $tpl?ROOT_PATH."/tpls$tpl":$tmp;
  }


  static function register_custom_element($tagName, $callback){
    self::$customs[$tagName] = $callback;
  }

  static function unregister_custom_element($tagName){
    unset(self::$customs[$tagName]);
  }

  static function process_customs_elements($doc){
    if(!self::$customs) return;

    $xpath = new DOMXPath($doc);
    foreach(self::$customs as $mask => $callback){
      $query = sprintf("//%s", $mask);
      $entries = $xpath->query($query);
      if(!$entries->length) continue ;
      foreach ($entries as $entry) 
        call_user_func($callback, $doc, $entry);
    }

  }


  static public function add_resolver($key, $path){
    //reaggregate paths
    if(self::$paths['search']) {
      $paths = array_combine(self::$paths['search'], self::$paths['replace']);
      $paths["#^$key#"] = $path;
      krsort($paths);
    } else $paths = array("#^$key#" => $path);

    self::$paths['search']  = array_keys($paths);
    self::$paths['replace'] = array_values($paths);
  }



  /**
  * 
  * @param DomDocument doc
  * @param DomElement field
  */
  static function inline_field($doc, $field){

    $type = $field->getAttribute('type');
    $name = $field->hasAttribute('name') ? $field->getAttribute('name') : $type;

    $container = self::create_element($doc, 'p');
    self::clone_args($container,  $field, array('id', 'class'));

    $attr_title = specialchars_encode($field->getAttribute('title')); // read

    if($attr_title){
      $title = self::create_element($doc, 'span', $attr_title.' : ');
      $container->appendChild($title);
    }


    if(!$type) {
      foreach($field->childNodes as $node_child)
          $container->appendChild($node_child->cloneNode(true));

      $field->parentNode->replaceChild($container, $field);
      return;
    }

    $true_type = self::fall_base_type($type, $field->getAttribute('mode'));
    $output_element = null;

    //switch like
    if(in_array($true_type, array( 'string', 'int', 'time', 'date', 'sha1', 'hidden', 'password'))) {

      $new_attr = array(
        'type'  => in_array($true_type, array('sha1', 'password') ) ? "password" :  "text",
        'name'  => $name,
        'class' => 'input_'.$true_type,
      );
      $output_element = self::create_element($doc, 'input', null, $new_attr);
        self::clone_args($output_element,  $field, array('value', 'style', 'name', 'id', 'disabled'));
    }

    ///////file
    if($true_type == 'file'){
      $output_element = self::create_element($doc, 'input', null, array('name' => $name, 'type' => 'file'));
      self::clone_args($output_element,  $field, array('style'));
    }

    ///////upload
    if($true_type == 'upload'){
      //span
      $output_element = self::create_element($doc, 'span');

      //span > a 
      $new_attr = array(
        'value'  => specialchars_encode($field->getAttribute('upload_title')),
        'target' => 'upload_file', 
        'href'   => '#0',
      );

      $button = self::create_element($doc, 'button', $new_attr['value'], $new_attr);
      self::clone_args($button,  $field, array( 'onclick', 'style', 'src', 'theme', 'value', 'effects', 'href', 'ext'));


      $new_attr = array(
        'class' => 'input_'.$true_type,
        'style' => 'display:none',
        'id'    => $name,
        'upload_type' => $field->getAttribute('upload_type'),
      );

      $span = self::create_element($doc, 'span', '&#160;', $new_attr);

      $output_element->appendChild($button);
      $output_element->appendChild($span);
    }

    if(in_array($true_type, array('html', 'text', 'textarea')) ){
      $new_attr = array(
        'name'  => $name,
      );
      if($true_type == "html")
        $new_attr[ 'class' ]  = 'wyzzie';

      $output_element = self::create_element($doc, 'textarea', pick($field->nodeValue, $field->getAttribute('value')), $new_attr);
      self::clone_args($output_element,  $field, array('style', 'id'));
    }

    if($true_type == 'bool'){

      $new_attr = array(
        'type' => 'checkbox',
        'name' => $name,
      );
      $output_element = self::create_element($doc, 'input', NULL, $new_attr);

      $checked = $field->getAttribute('checked') == 'checked'
                 || bool($field->getAttribute('value'));

      if($checked)
        $output_element->setAttribute('checked', 'checked');
    }

    ///////enum
    if($true_type == 'enum') {
      $values = explode(',', $field->getAttribute('value'));
      $mode  = $field->getAttribute('mode');

      $mykse    = yks::$get->types_xml->$type;

      if($mode == 'checkbox' || $mode == 'radio'){

        $output_element = self::create_element($doc, 'div', null, array('class' => "input_{$mode}s"));

        foreach($mykse->val as $val){

          $div  = self::create_element($doc, 'div');

          $new_attr = array(
            'type'  => $mode,
            'value' => $val,
            'id'    => $name.'_'.$val, 
            'name'  => ($mode == 'checkbox') ? "{$name}[]" : $name,
          );

          if(in_array($val, $values))
            $new_attr['checked'] = 'checked';

          $input = self::create_element($doc, 'input', NULL, $new_attr);

          $label = self::create_element($doc, 'label', $val, array('for' => $new_attr['id']));

          $div->appendChild($input);
          $div->appendChild($label);

          $output_element->appendChild($div);
        }

      } else {

        $new_attr = array('name' => $name); 

        if($mykse['set']){
          $new_attr['multiple'] = 'multiple';
          $new_attr['name']     = $name.'[]';
        }

        $select = self::create_element($doc,'select', NULL, $new_attr);
        self::clone_args($select,  $field, array('disabled', 'multiple'));

        if($field->hasAttribute('null')){
          $null = self::create_element($doc, 'option', $field->getAttribute('null'), array('value' => ''));
          $select->appendChild($null);
        }

        foreach($mykse->val as $val){
          $new_attr = array('value' => $val);
          if(in_array($val, $values))
            $new_attr['selected'] = 'selected';

          $option = self::create_element($doc, 'option', $val, $new_attr);
          $select->appendChild($option);
        }

        $output_element = $select;
      }
    }

    if($output_element)
      $container->appendChild($output_element);

    $field->parentNode->replaceChild($container, $field);
  }


  private function create_element($doc, $type, $value = NULL, $new_attr = array()){
    $el = is_null($value) ? $doc->createElement($type) : $doc->createElement($type, $value);

    foreach($new_attr as $key => $value)
      $el->setAttribute($key, $value);

    return $el;
  }

  private function clone_args($dst, $src, $attrs){
    foreach($attrs as $attr_name)
      if($src->hasAttribute($attr_name))
        $dst->setAttribute($attr_name, $src->getAttribute($attr_name));
  }


  private static function fall_base_type($type, $mode){

    if(!$type)
      return "string"; //zero fallback

    $base_types = array(
      "string", "int", "time", "date",
      "sha1", "hidden", "password", 
      "file", "upload",
      "bool",
      "html", "textarea", "text",
      "enum", "checkbox", //?
    ); 

    $type_parent = yks::$get->types_xml->$type;

    if(in_array($type, $base_types) || ($type == 'bool' && $mode == 'checkbox'))
      return $type;

    else return self::fall_base_type((string) $type_parent["type"], $mode);

  }
}
