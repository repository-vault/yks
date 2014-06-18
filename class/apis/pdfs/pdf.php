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

  
/* Generate a dummy pdf that embed all necessary glypf for a list of font */
  public static function make_font_template($fonts, $entities){
    $pdf = new Zend_Pdf();
    $page = $pdf->newPage('A4');
    $pdf->pages[] = $page;

    $font_size = pdf::mm(6);

    foreach($fonts as $font){
      $page->setFont($font, $font_size);

      foreach($entities as $v)
        $page->drawText($v, 60, 450, 'UTF-8');
    }

    return $pdf->render();
  }
/* Load a dummy pdf file and returns its fonts
*  Usage pdf::extracts_fonts_from_template('template.pdf', array(
*   'classic_font' => 'Calibri',
*   'chinese_font' => 'ArialUnicodeMS',
* );
*/

  public static function load_font_template($file_path, $aliases){
    $fonts = array();
    $pdf = Zend_Pdf::load($file_path);

    foreach ($pdf->extractFonts() as $font) {
      $font_name = $font->getFontName(Zend_Pdf_Font::NAME_POSTSCRIPT, 'en', 'UTF-8');
      $key = array_search($font_name, $aliases);
      if(!$key) continue;
      $fonts[$key] = $font;
    }
    $pdf->pages = array();
    return array($pdf, $fonts);
  }
  
  public static function load_font($file_path){
    if(!is_file($file_path)) {
        $file_path = CONFIG_PATH."/fonts/".strip_end($file_path, ".ttf").".ttf";
        if(!is_file($file_path))
          throw new Exception("Could not find font $file_path");
    }
    
    $font = Zend_Pdf_Font::fontWithPath($file_path,  Zend_Pdf_Font::EMBED_DONT_EMBED);
    $font->font_name = $font->getFontName(Zend_Pdf_Font::NAME_POSTSCRIPT, 'en', 'UTF-8');
    $font->file_path = $file_path;
    return $font;
  }



 
}