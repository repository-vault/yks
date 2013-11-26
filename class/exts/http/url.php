<?php

class url  extends __native {
  protected $_accessibles = array('query', 'host', 'port');

  function __construct($url){
    $this->data  = urls::parse($url);
    if(!$url) $this->empty = true;
  }

  static function from($url){
    return ($url instanceof url)?$url:new url($url);
  }

  function merge($url){
    if($url->empty) return $this;
    if($this->is_relative) return $url;

    if($url->host && $this->host != $url->host) return $url;
    if($url->scheme && $this->scheme  != $url->scheme) return $url;

    $path = files::paths_merge($this->path, $url->path);
    $merge = array_filter(array(
        'host'     => $this->host,
        'port'     => $this->port,
        'scheme'   => $this->scheme,
        'path'     => $path,
        'query'    => $url->query?$url->query:($path!=$this->path?false:$this->query),
        'fragment' => $url->fragment,
    ));
    return new url($merge);
  }

  function is_ssl(){
    return $this->scheme == 'https';
  }

  function is_relative(){
    return substr($this->path,0,1)!="/";
  }

  function is_absolute(){
    return !$this->is_relative;
  }
  function is_browsable(){
    return isset($this['scheme']) && isset($this['host']) && isset($this['path']);
  }

  function get_hash($full = false){
    if(!$this->scheme || !$this->host)
       $str = "[Partial url]";
    else $str = "{$this->scheme}://{$this->http_host}";
    $str .= $this->get_client_part($full);
    return $str;
  }

  function get_client_part($full = true){
    $str = $this->path;
    if($full){
        if($this->query)$str.="?{$this->query}";
        if($this->fragment)$str.="#{$this->fragment}";
    }
    return $str;
  }

// http://google.fr/test -> match (http://google/) == true
  function match($url){
    return starts_with((string)$this, (string)$url);
  }

  function get_http_query(){
    $str = $this->path;
    if($this->query)$str.="?".($this->query);
    $str = str_replace(" ", "%20", $str);

    //if(preg_match("#[0x80-0xFF]#", $str))die("$str!!");
    return $str;
  }

  //return unique scheme params
  protected function get_sock_lnk(){
    $enctype = $this->is_ssl?'ssl://':'';
    return "{$enctype}{$this->host}:{$this->http_host}";
  }

  //host:(nonstandart port)
  protected function get_http_host(){
    $http_host = $this->host;
    $port = pick($this->port, $this->is_ssl?443:80);
    if($port != ($this->is_ssl ? 443 : 80))
      $http_host .= ":$port";
    return $http_host;
  }


  function __toString(){
    return $this->get_hash(true);
  }
}
