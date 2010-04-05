<?php

function mm($mm){
    return $mm*2.88;
}


function zend_color($color){
    $tmp = imgs::colordec($color);
    return new Zend_Pdf_Color_Rgb(
        $tmp['red']/255,
        $tmp['green']/255,
        $tmp['blue']/255
    );
}


function utf16_decode($str){
    return mb_convert_encoding($str,"UTF-8","UTF-16");
}

 
 
        
class Page  {

  function __construct($zend_page){
    $this->zend_page = $zend_page;
    $this->zend_h  = $this->zend_page->getHeight();
    $this->zend_page->setLineWidth(0);
  }
  
  function drawRectangle($x,$y, $w, $h, $color=null){
        
        $yu = $this->zend_h - mm($y);
        $yd = $this->zend_h - mm( $y+$h );
        
        $xl = mm($x);
        $xr = mm($x+$w);
        
        $mode = Zend_Pdf_Page::SHAPE_DRAW_STROKE;
        
        if($color) {
            $this->zend_page->setFillColor(zend_color($color));
            $mode = Zend_Pdf_Page::SHAPE_DRAW_FILL;
        }
        
        $this->zend_page->drawRectangle($xl, $yu, $xr, $yd, $mode );
        
        if($color){
                $this->zend_page->setFillColor(zend_color(0));
        }
  }
  
  function drawText($str,$x,$y){
    $y = $this->zend_h - ( mm($y) + $this->zend_page->getFontSize() );
    $this->zend_page->drawText($str,mm($x), $y,'UTF-8');
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
    $font_size = $this->zend_page->getFontSize();
    $font_path = $this->zend_page->getFont()->file_path;
    $font_height = $font_size/2.88;
    
  
    $lines_list = array_map("trim", explode("\n",$str) );
    $lines = array();
    
    $height = count($lines_list) * $font_height; //TODO : traiter les lignes trop longues
    
    $widths = array();
    foreach($lines_list as $str){
        
        $size = imagettfbbox ( $font_size, 0, $font_path, $str);
        $widths[] = $width = ($size[2]-$size[0])/3.8;
        $lines[] = array('width'=>$width, 'str'=>$str, 'height'=>$font_height );
    }

    $width = max($widths);
    
    return compact('width', 'height', 'lines');
 }
}

