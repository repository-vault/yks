<?php

class cache_file implements iCache {
  private $dir = '/tmp';
  private $prefix = 'cache';
  
  public function __construct($conf){
    $this->dir = pick($conf['dir'], $this->tmp);
    $this->prefix = pick($conf['prefix'], $this->prefix);
  }
  
  private function filename($key){
    return sprintf("%s/%s_%s", $this->dir, $this->prefix, $key);
  }
  
  public function set($key, $value, $ttl=0){
    $file = $this->filename($key);
    if(!file_put_contents($file, $value))
      throw new Exception("Failed to write cache in $file");
  }
  
  public function get($key){
    $file = $this->filename($key);
    return file_get_contents($file);
  }
  
  public function delete($key){
    $file = $this->filename($key);
    unlink($file);
  }  
}