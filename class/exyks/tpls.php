<?php

class tpls {
 const REPLACE = 1;
 const ERASE = 2;
 const TOP = 3;
 const STD = 0;

 static $nav=array();
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


 static function nav($tree){
    self::$nav = array_merge(self::$nav, $tree);
 }
 
 /**
 * 
 * @param DOMElement $old
 * @param DOMElement $new
 * @return mixed
 */
  static function copy_elem($old, $new){   
   foreach($old->childNodes as $node_child){
     if(gettype($node_child) == 'object' && get_class($node_child) == 'DOMElement')
      $new->appendChild($node_child->cloneNode(true));
   }
  }
 
 /**
  * 
  * @param DomDocument $doc
  * @param DomElement $elem
  */
  static function inline_field($doc, $elem){
       
    $type = $elem->getAttribute('type');
    $name = $elem->getAttribute('name') ? $elem->getAttribute('name') : $type;       
     
    //DebugBreak("1@172.19.20.35");
    $attr_list = array('id', 'class');
    
    $element = self::create_element($doc, 'p', NULL, $elem, $attr_list);
    
    $attr_title = specialchars_encode($elem->getAttribute('title')); // read
    
    if($attr_title){
      $title = self::create_element($doc, 'span', $attr_title.' : ');
      $element->appendChild($title);
    }
    
    self::copy_elem($elem, $element);
        
    if($type){
      $true_type = self::fall_base_type($type, $elem->getAttribute('mode'));

      //switch like
      if(in_array($true_type, array( 'string', 'int', 'time', 'date', 'sha1', 'hidden'))) {
          $el_type = 'text';
          if($true_type == 'sha1')
            $el_type = 'password';
          
          $new_attr = array(
            'type'  => $el_type,
            'name'  => $name,
            'class' => 'input_'.$true_type,
          );
          
          $tr_attr = array('value', 'style', 'name', 'id', 'disabled');        
          $field = self::create_element($doc, 'input', $elem->nodeValue, $new_attr, $elem, $tr_attr);
      }
      
      ///////file
      if($true_type == 'file'){
        $field = self::create_element($doc, 'input', $elem->nodeValue, array('name' => $name), $elem, array('style'));
      }
      
      ///////upload
      if($true_type == 'upload'){        
        //span
        $field = self::create_element($doc, 'span', $elem->nodeValue, NULL, NULL, NULL, '&#160;');
        
        //span > a 
        $new_attr = array(
          'href'   => $elem->getAttribute('href'),
          'target' => 'upload_file', 
        );
        
        $tr_attr = array(
          'onclick',
          'style',
        );
        
        $button = self::create_element($doc, 'a', NULL, $new_attr, $elem, $tr_attr);
        
        //span > a(button) > img
        $value = $elem->getAttribute('upload_title');
        $theme = $elem->getAttribute('theme');
        $class = $elem->getAttribute('class');
        $effects = $elem->getAttribute('effects');
        
        $new_attr = array(
          'src'   => "?/Yks/Scripts/Imgs/titles//{$theme}|{$value}",
          'class' => "button {$class} {$effects}",
          'alt'   => $value,
          'title' => $value,
        );
        
        $tr_attr = array(
          'src',
          'theme',
          'value',
        );
        
        $img = self::create_element($doc, 'img', NULL, $new_attr, $elem, $tr_attr);
                
        $new_attr = array(
          'class' => 'input_'.$true_type,
          'style' => 'display:none',
          'id'    => $name,
          'upload_type' => $elem->getAttribute('upload_type'),
        );
        
        $span = self::create_element($doc, 'span', NULL, $new_attr);
        
        $button->appendChild($img);
        $field->appendChild($button);
        $field->appendChild($span);
      }
      
      ////////html
      //?????? comment
      if($true_type == 'html'){
        
        $new_attr = array(
          'class' => 'wyzzie',
          'name'  => $name,
        );
        
        $tr_attr = array('style', 'id');
        
        $field = self::create_element($doc, 'textarea', $elem->nodeValue, $new_attr, $elem, $tr_attr);
      }
      
      //////textarea text
      //?????? comment
      if($true_type == 'textarea' || $true_type == 'text'){      
        $tr_attr = array('style', 'id');      
        $field = self::create_element($doc, 'textarea', $elem->nodeValue, array('name' => $name), $elem,  $tr_attr, $elem->nodeValue);      
        $element->appendChild($field);
      }
      
      if($true_type == 'bool' && $elem->getAttribute('mode') == 'checkbox'){
        
        $new_attr = array(
          'type' => 'checkbox',
          'name', $name,
        );
        
        $field = self::create_element($doc, 'input', NULL, $new_attr);
        
        if($elem->getAttribute('checked') == 'checked'){
          $field->setAttribute('checked', 'checked');
        }
      }
      
      ///////enum
      if($true_type == 'enum'){
        $value = $elem->getAttribute('value');
        $mode  = $elem->getAttribute('mode');
                
        $field = self::create_element($doc, 'div', $elem->nodeValue, array('class' => 'input_'.$mode.'s'));
        
        if($mode == 'checkbox' || $mode == 'radio'){
          $type_list = yks::$get->types_xml->$type;
          
          foreach(yks::$get->types_xml->$type->val as $val){
            
            $div  = self::create_element($doc, 'div');
            $id   = $name.'_'.$val;
            if($mode == 'checkbox')
              $name = $name.'[]';
                      
            $new_attr = array(
              'type'  => $mode,
              'value' => $val,
              'id'    => $id, 
              'name'  => $name,
            );
            
            $value = $elem->getAttribute('value');
            $pos   = strpos($value, $val);
            
            if($pos !== false || $value == $val)
              $new_attr['checked'] = 'checked';
            
            $input = self::create_element($doc, 'input', NULL, $new_attr);
            
            $label = self::create_element($doc, 'label', $val, array('for' => $val), NULL, NULL);
            
            $div->appendChild($input);
            $div->appendChild($label);
            
            $field->appendChild($div);        
          }
        }
        else{
          $tr_attr  = array('disabled', 'multiple');
          $new_attr = array('name' => $name);        
          $mykse    = yks::$get->types_xml->$type;
          
          if($mykse['set']){
            $new_attr['multiple'] = 'multiple';
            $new_attr['name']     = $name.'[]';
          }
          
          $select = self::create_element($doc,'select', NULL, $new_attr, $elem, $tr_attr);
          
          $attr_null = $elem->getAttribute('null');
          if($elem->getAttribute('null')){
            $option = self::create_element($doc, 'option', $attr_null, array('value' => ''), NULL, NULL);
            $select->appendChild($option);
          }
            
          foreach(yks::$get->types_xml->$type->val as $val){
            $new_attr = array();
            $pos   = strpos($value, $val);          
            if($pos !== false || $value == $val)
              $new_attr['selected'] = 'selected';
            
            $option = self::create_element($doc, 'option', $val, $new_attr, NULL, NULL);
            
            $select->appendChild($option);
            
            $field->appendChild($select);
          }
        }
      }
      if($field)
        $element->appendChild($field);
    }
    $elem->parentNode->replaceChild($element, $elem);
  }  
  
  /**
  * 
  * @param DOMDocument $doc
  * @param DOMElement $elem
  * @param string $type
  * @param array $transfer_attr
  * @param array $attr
  */
  private function create_element($doc,  $type, $value = NULL, $new_attr = NULL, $elem = NULL, $transfer_attr = NULL){
    $el = $doc->createElement($type, $value);
    
    if($new_attr)
      foreach($new_attr as $key => $value){
        $el->setAttribute($key, $value);
      }
    
    if($elem && $transfer_attr)
      foreach($transfer_attr as $attr){
        $attr_value = $elem->getAttribute($attr);

        if($attr_value)
          $el->setAttribute($attr, $attr_value);
      }
    
    return $el;
  }
  
  
  private static function fall_base_type($type, $mode){
     $base_types = array(
      "string", "int", "time", "date",
      "sha1", "hidden",
      "file", "upload",
      "html", "textarea", "text",
      "enum", "checkbox", //?
     ); 
     
     $type_parent = yks::$get->types_xml->$type;
     
     if(in_array($type, $base_types) || ($type == 'bool' && $mode == 'checkbox'))
      return $type;
      
     else return self::fall_base_type((string) $type_parent["type"], $mode);
      
  }
}
