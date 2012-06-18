<?php

class cache_memory implements iCache {
  private $host = 'localhost';
  private $port = 11211;
  private $cache = null;
  
  public function __construct($conf){
    $this->host = pick((string)$conf['host'], $this->host);
    $this->port = pick((int)$conf['port'], $this->port);
    $this->cache = new Memcached();
    $this->cache->addServer($this->host, $this->port);
  }
  
  public function set($key, $value, $ttl=0){
    $this->cache->set($key, $value, $ttl);
  }
  
  public function get($key){
    return $this->cache->get($key);
  }
  
  public function delete($key){
    $this->cache->delete($key);
  } 
  
}