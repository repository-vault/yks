<?php

class ocr {
  private $lib = array();
  function __construct($teach){
    foreach($teach as $file_path){
      $ext = files::ext($file_path);
      $name = strip_end(basename($file_path), ".$ext");
      $this->lib[$name] = imgs::imagecreatefromfile($file_path);
    }
  }
  function resolve($scr){
    $score = array();
    foreach($this->lib as $name=>$img) 
      $score[$name] = self::imagecompare($scr, $img);
    return array_search(max($score), $score);
  }



  static function imagecompare($img0, $img1){
    list($img0_w, $img0_h) = array(imagesx($img0), imagesy($img0));
    list($img1_w, $img1_h) = array(imagesx($img1), imagesy($img1));
    $min_w = min($img0_w, $img1_w); $min_h = min($img0_h, $img1_h);
    $same = 0; $diff = 0;
    for($x=0;$x<$min_w;$x++)
      for($y=0;$y<$min_h;$y++)
        $same += imagecolorat($img0, $x, $y) == imagecolorat($img1, $x, $y) ? 1 : -1;
    return max(0, $same/($min_h * $min_w));
  }
}
