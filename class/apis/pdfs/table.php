<?php



function array_colsum($array,$col){
    $ret=0;
    foreach($array as $tmp)
        $ret+=$tmp[$col];
    return $ret;
}


class Table {
    private $drawing_areas=array();
    private $current_page=0;
    private $lines=array();
    private $cols = array();
    private $headers= false;
    
    
  function __construct($options=array())
  {
        if(!isset($options['border'])) $options['border']=1;
        if(!isset($options['table_border'])) $options['table_border']=1;
        $this->options=$options;
        
  }

  function SetColsFormats($cols)
  {
    $this->cols = $cols;
    foreach($this->cols as &$col){
        $format=$col['format'];
        $col['mode']=$format;
        $col['format']=Box::POS_DEFAULT;

        if($format=='number'||$format=='price'||$format=='right')
            $col['format']|=Box::H_RIGHT;
        if($format=='id' || $format=='center')
            $col['format']|=Box::H_CENTER;

    }
    
  
  }
  function setDrawingZone($x, $y, $w, $h)
  {
    $this->w = $w;
    $this->y = $y;
    $this->x = $x;
    
    $this->drawing_areas['base'] = compact('x', 'y', 'w', 'h');
    $this->drawing_areas['pages'][0] = compact('x', 'y', 'w', 'h');
  }
  function setRepeatableZone($x, $y, $w, $h)
  {
    $this->drawing_areas['base'] = compact('x', 'y', 'w', 'h');
  }
  
  function draw($pdf)
  {
        if($this->current_page == 0){
           $page = new Page($pdf->pages[0]); 
           $this->page = $page;
           $area = $this->drawing_areas['base'];
        }
        $this->ponderate();
        
        if($this->options['table_border'])
              $this->page->drawRectangle($area['x'], $area['y'], $area['w'], $area['h']); //box this

        if($this->caption) $this->drawCaption();
        if($this->headers) $this->drawHeaders();
        $this->drawContents();
  }
  
  function setCaption($str){
    $this->caption = $str;
  }
  
  function setContents($str){
    $this->FeedLines(array(array($str)));
  }
  function ponderate()
  {
    if(!$this->cols) $this->cols=array(array('weight'=>1));
    $weight =  array_colsum($this->cols, "weight");
    $x=$this->x; //margin+border
    foreach($this->cols as &$col){
        $col['width']=$this->w * ($col['weight']/$weight);
        $col['start_x']=$x;
        $x+=$col['width'];
    } unset($col);
  
  }
  function drawCaption(){
    $height = $this->page->getFontHeight()*2;
    $caption = new Box($this->x, $this->y, $this->w, $height);
    $caption->inject($this->page);
    $caption->setContents($this->caption);
    $caption->drawBox( coloralpha(200,200,200) );
    $caption->setFormat(Box::H_CENTER|Box::V_MIDDLE);
    $caption->drawText();
    $this->y+=$height;
  }
  
  
  function drawContents()
  {
  
    foreach($this->lines as $tmp) {
        
            //searching max line height
        $heights = array();
        $cells = array();
        
        foreach($tmp as $k=>$cell_contents){

            $col = $this->cols[$k];
            $cells[$k] = $cell = new Box($col['start_x'], $this->y, $col['width'], 5); //height is unrevellant here
            $cell->inject($this->page);
            
            if($col['mode']=='price') 
                $cell_contents=number_format($cell_contents, 2,'.',' ').' €';

            if($col['mode']=='key') 
                $cell_contents.=" :";
            
                
            $cell->setContents($cell_contents);
            $cell->setFormat($col['format']);
            $heights[] = array_get($cell->textBox($cell_contents),'height');
        }
        
        $height = max($heights); //updating cell height
        
        foreach($cells as $k=>$cell){
            $cell->move(null, $this->y, null, $height);
            $cell->render($this->options['border']);
        }
        $this->y += $height;

    }
  
  }
  
  function drawHeaders()
  {
        $height = $this->page->getFontHeight()*2;
        
        foreach($this->headers as $k=>$header_title){
            $col = $this->cols[$k];
            $cell = new Box($col['start_x'], $this->y, $col['width'],$height);
            $cell->inject($this->page);
            $cell->setContents($header_title);
            $cell->drawBox( coloralpha(125,125,125) );
            $cell->setFormat(Box::H_CENTER|Box::V_MIDDLE);
            $cell->drawText();
        }
        
        $this->y+=$height;
  }
  
  function getHeight()
  {
    return count($this->lines)*10;
  }
  
  function AddHeader($header)
  {
    $this->headers=$header;
      
  }
  
  function FeedLines($lines)
  {
    $this->lines=array_merge($this->lines, $lines);
  
  }
  function FeedLine($line)
  {
    $this->lines[]=$line;
  
  }
  
  function setEndPage($str)
  {
  
  }
  
}
