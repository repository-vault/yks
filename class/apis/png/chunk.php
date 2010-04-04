<?php


class png_chunk {
  private $type;
  public $data;
  function __construct($type, $data){
    $this->type = $type;
    $this->data = $data;
  }

  public function __toString(){
    return $this->type;
  }

  public function __get($key){
    if(method_exists($this, $getter = "get_$key"))
        return $this->$getter();
    die("noo $key");
  }

  private function get_ltype(){
    return strtolower($this->type);
  }

  private function get_contents() {
    return $this->size.$this->type.$this->data.$this->crc;
  }

  private function get_size(){
    return pack("N", strlen($this->data));
  }

  private function get_crc(){
    return pack("N", crc32($this->type.$this->data));
  }
}
