<?php

class timeout {
  private $start_time;
  private $delay;

  function __construct($delay){
    $this->start_time = microtime(true);
    $this->delay      = $delay;
  }


  public static function from_seconds($s){
    return new self($s);
  }
  public static function from_ms($ms){
    return new self($ms/1000);
  }

  private function is_expired(){
    return (microtime(true) - $this->start_time) > $this->delay;
  }

  function __get($key){
    if($key=="expired")
        return $this->is_expired();
  }
}