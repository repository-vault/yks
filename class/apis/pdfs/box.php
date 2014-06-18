<?php



class Box {
    const H_LEFT=1;
    const H_CENTER=2;
    const H_RIGHT=4;
    
    const V_TOP=8;
    const V_MIDDLE=16;
    const V_BOTTOM=32;
    
    const POS_DEFAULT=9; //V_UP|H_LEFT;
    const BORDER_EMPTY=1;
    const BORDER=2;
    
    private $style;
    private $x;
    private $y;
    private $w;
    private $h;
    private $contents;
    
    function __construct($x,$y,$w,$h){
        $this->x=$x;
        $this->y=$y;
        $this->w=$w;
        $this->h=$h;
        $this->padding=1;
    }
    function inject($page){
        $this->page=$page;
    
    }
    function setStyle($style){
        $this->style = $style;
    }
    
    function printText($page, $contents, $format){
      $this->inject($page);
      $this->setContents($contents); 
      $this->setFormat($format);
      $this->drawText();
    }
    
    function drawText(){

        $textBox = $this->textBox($this->contents);
        $lines  = $textBox['lines'];
        
        $bottom = $this->h - $textBox['height']; //incompatible padding != UP&DOWN
        $middle = $bottom/2;

        $lines_y = $this->y +$this->padding; //+padding si l'on considere Zend Height OK
        if($this->format & self::V_MIDDLE) $lines_y+= $middle ;
        elseif($this->format & self::V_BOTTOM) $lines_y+= $bottom ;
        
        foreach($lines as $line_infos){
        
            $left = $this->w-$line_infos['width'];
            $center = $left/2;
            
            $x = $this->x;
            if($this->format & self::H_RIGHT) $x+= $left;
            elseif($this->format & self::H_CENTER) $x+= $center;
            
            $this->page->drawText($line_infos['str'], $x, $lines_y);

            $lines_y+=$line_infos['height'];
        }

    }
    
    function setFormat($format){
        $this->format = $format;
    }
    
    function drawBox($color=null){
        $this->page->drawRectangle($this->x, $this->y, $this->w, $this->h, $color);
        return $this->page->drawRectangle($this->x, $this->y, $this->w, $this->h);
    }
    
    function textBox($str){
        $contents = $this->page->textBox($str);
        $contents['height']+=$this->padding*2;
        return $contents;
    }
    function setContents($str){
        $this->contents = $str;
    }
    
    function move($x=null, $y=null, $w=null, $h=null){
        if(!is_null($x)) $this->x = $x;
        if(!is_null($y)) $this->y = $y;
        if(!is_null($w)) $this->w = $w;
        if(!is_null($h)) $this->h = $h;
    }
    
    function render($border=1){
        if($border) $this->drawBox();
        $this->drawText();
    }
}
