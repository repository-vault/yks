<?php


        
class Page  {

  function __construct($zend_page){
    $this->zend_page = $zend_page;
    $this->zend_h  = $this->zend_page->getHeight();
    $this->zend_page->setLineWidth(0);
  }
  
  function drawRectangle($x,$y, $w, $h, $color=null){
        
        $yu = $this->zend_h - pdf::mm($y);
        $yd = $this->zend_h - pdf::mm( $y+$h );
        
        $xl = pdf::mm($x);
        $xr = pdf::mm($x+$w);
        
        $mode = Zend_Pdf_Page::SHAPE_DRAW_STROKE;
        
        if($color) {
            $this->zend_page->setFillColor(pdf::zend_color($color));
            $mode = Zend_Pdf_Page::SHAPE_DRAW_FILL;
        }
        
        $this->zend_page->drawRectangle($xl, $yu, $xr, $yd, $mode );
        
        if($color){
                $this->zend_page->setFillColor(pdf::zend_color(0));
        }
  }
  
  function drawText($str,$x,$y){
    $y = $this->zend_h - ( pdf::mm($y) + $this->zend_page->getFontSize() );
    $this->zend_page->drawText($str, pdf::mm($x), $y, 'UTF-8');
  }
  
  function setFont($font,$size){
    return $this->zend_page->setFont($font, $size);
  }
  function setStyle($style){
    return $this->zend_page->setStyle($style);
  }
  function getFontHeight(){
    return $this->zend_page->getFontSize()/2.88;
  }
  

  
  function textBox($str){
    $font      = $this->zend_page->getFont();
    $font_size = $this->zend_page->getFontSize();

    $font_path = $font->file_path;
    $font_height = $font_size/2.88;
    
  
    $lines_list = array_map("trim", explode("\n",$str) );
    $lines = array();
    
    $height = count($lines_list) * $font_height; //TODO : traiter les lignes trop longues
    
    $widths = array();
    foreach($lines_list as $str){
        if(false) {
            $size = imagettfbbox ( $font_size, 0, $font_path, $str);
            $widths[] = $width = ($size[2]-$size[0])/3.8;
        } else {
            if(get_class($font) == "Zend_Pdf_Resource_Font_Extracted") //unavalaible
              $widths[] = $width = null;
            else {
              $glyphs = $font->glyphNumbersForCharacters ( txt::utf8_to_cp($str) );
              $tmp = $font->widthsForGlyphs ( $glyphs );
              $textWidth = (array_sum ( $tmp ) / $font->getUnitsPerEm ()) * $font_size;
              $widths[] = $width = ($textWidth)/2.88;
            }
        }
        $lines[] = array('width'=>$width, 'str'=>$str, 'height'=>$font_height );
    }

    $width = max($widths);
    
    return compact('width', 'height', 'lines');
 }
}

