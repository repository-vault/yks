<?php

class png_parser {
  private $i=0;
  private $contents;
  private $chunks;

  public static function parse($contents){
    $parser = new self($contents);
    return $parser->chunks;
  }

  private function __construct($contents){
    $this->contents = $contents;
    if($this->feed(8) != png::$PNG_HEADER)
      throw new Exception("This is no PNG file");

    while($this->parse_chunk());
  }

  private function parse_chunk(){
    $size = $this->feed(4);
    if(!$size)
      return false;

    $type = $this->feed(4);
    if($type == "IHDR") $size = 13;
    else  $size = hexdec(bin2hex($size));

    $data = $this->feed($size);

    $chunk = new png_chunk($type, $data);
    $crc  = $this->feed(4);
    $signed = $chunk->get_crc() == $crc;
    $this->chunks[] = $chunk;

    return true;
  }

  private function seekfw($n){
    $this->i+=$n;
    return $n;
  }

  private function feed($len=1){
    return substr($this->contents, $this->i, $this->seekfw($len));
  }

}