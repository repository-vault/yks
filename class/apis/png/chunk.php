<?php


class png_chunk {
  private $type;
  private $data;
  function __construct($type, $data){
    $this->type = $type;
    $this->data = $data;
  }

  public function __toString(){
    return $this->type;
  }

  public function get_contents() {
    return $this->get_size().$this->type.$this->data.$this->get_crc();
  }
  public function get_size(){
    return pack("N", strlen($this->data));
  }
  public function get_crc(){
    return pack("N", crc32($this->type.$this->data));
  }
}
