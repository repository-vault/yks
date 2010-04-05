<?php

class png_map {

  private $png;
  private $hash ;
  private $palette;
  private $img;

  function __construct($file) {
    $this->png     = png::load_file($file);
    $comment       = $this->png->get_comment();
    $hash          = json_decode($comment, true);
    $this->palette = $this->png->get_palette();

    foreach($hash as $key=>&$color){
      $color = array_search(hexdec($color), $this->palette);
      if(!$color) throw new Exception("Invalid color in map $key");
    }
    $this->hash    = $hash;

    $this->img     = imagecreatefrompng($file);
  }

  function set_color($hash_key, $color){
    $index = $this->hash[$hash_key];
    $this->palette[$index] = $color;
  }



  function fill($color){
    foreach($this->hash as $hash_key=>$index)
        $this->palette[$index] = $color;
  }

  function hash_key_at($x, $y){
    $index = imagecolorat($this->img, $x, $y);
    return array_search($index, $this->hash);
  }

  function output(){
    $this->png->set_palette($this->palette);
    $this->png->output();
  }
  
}