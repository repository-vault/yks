<?php

class pdf {

  static function init(){

    if(!classes::init_need(__CLASS__)) return;
    
    $__DIR__ =  dirname(__FILE__);

    require_once 'Zend/Pdf.php';

    require_once CLASS_PATH."/exts/iconv.php";
    classes::register_class_paths(array(
        'box'   => $__DIR__."/box.php",
        'table' => $__DIR__."/table.php",
        'page'  => $__DIR__."/page.php",
    ));
  }


  public static function mm($mm){
    return $mm*2.88;
  }

  public static function zend_color($color){
    $tmp = imgs::colordec($color);
    return new Zend_Pdf_Color_Rgb(
        $tmp['red']/255,
        $tmp['green']/255,
        $tmp['blue']/255
    );
  }



 
}