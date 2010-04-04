<?php

class png {
  public static $PNG_HEADER;
  private $chunks;

  static function init(){
    self::$PNG_HEADER = pack('C*', 137,80,78,71,13,10,26,10);
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
      $str .= $chunk->get_contents();
    return $str;
  }

  function add_comment($data, $comment_key = "Comment"){
    $type = $comment_key == "Comment" ? "tEXt": "teXt";
    $chunk  = new png_chunk($type, "$comment_key\0$data");
    array_splice($this->chunks, 2, 0, array($chunk));
  }

  public function write($file){
    file_put_contents($file, $this->get_contents());
  }


}