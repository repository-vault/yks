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

  private function get_ratio(){
    return (microtime(true) - $this->start_time) / $this->delay;
  }
  private function is_expired(){
    return (microtime(true) - $this->start_time) > $this->delay;
  }

  public function sleep($sec){
    usleep(1000* 1000 * $sec);
    return true;
  }

  function __get($key){
    if($key=="expired")
        return $this->is_expired();
    if($key=="ratio")
        return $this->get_ratio();
  }
}