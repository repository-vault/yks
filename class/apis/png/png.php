<?php

class png {
  public static $PNG_HEADER;
  private $chunks;

  static function init(){
    self::$PNG_HEADER = pack('C*', 137,80,78,71,13,10,26,10);

    classes::register_class_paths(array(
      "png_chunk"  => CLASS_PATH."/apis/png/chunk.php",
      "png_parser" => CLASS_PATH."/apis/png/parser.php",
    ));

  }

  public function load_file($file){
    $contents = file_get_contents($file);
    return new self($contents);
  }

  public function load_string($contents){
    return new self($contents);
  }

  private function __construct($contents){
    $this->contents = $contents;
    $this->chunks = png_parser::parse($this->contents);
  }

  private function get_contents(){
    $str = self::$PNG_HEADER;
    foreach($this->chunks as $chunk)
      $str .= $chunk->contents;
    return $str;
  }


  private function find_chunk($ltype){
    foreach($this->chunks as $chunk) {
      if($chunk->ltype == "idat") break;
      if($chunk->ltype != $ltype) continue;
      return $chunk;
    }
  }

  function get_palette(){
    $chunk = $this->find_chunk("plte");
    if(!$chunk) return array();
    $palette = array();
    foreach(str_split($chunk->data,3) as $color)
      $palette[] = hexdec(bin2hex($color));

    return $palette;
  }

  function set_palette($palette){
    $chunk = $this->find_chunk("plte");
    if(!$chunk) return false;
    $palette_str = "";
    foreach($palette as $i=>$color)
      $palette_str .= substr("\0\0\0".pack("N",$color),-3);
    $chunk->data = $palette_str;
    //file_put_contents("o", $palette_str);die;
  }



//********** COMMENT ******************/
  function get_comment(){
    $chunk = $this->find_chunk("text");
    if(!$chunk) return null;
    list($ckey, $data) = explode("\0", $chunk->data,2);
    return $data;
  }

  function add_comment($data, $comment_key = "Comment"){
    $type = $comment_key == "Comment" ? "tEXt": "teXt";
    $chunk  = new png_chunk($type, "$comment_key\0$data");
    array_splice($this->chunks, 2, 0, array($chunk));
  }


  public function output($file = null){
    if(is_null($file))
      echo $this->get_contents();
     else if($file == '-')
        return $this->get_contents();
     else file_put_contents($file, $this->get_contents());
  }

}