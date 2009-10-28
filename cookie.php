<?php

    //http://www.ietf.org/rfc/rfc2109.txt

class cookie {
  private $name;
  private $value;

  private $url;
  private $expire;
  private $domain_restricted;

  function __construct($name, $value, $host, $extras){
    $this->name  = $name;
    $this->value = $value;

    $this->domain_restricted = substr($host,0,1)!='.';
    $host = trim($host, '.');

    $this->expire  = isset($extras['expires'])?strtotime($extras['expires']):0;
    $cookie_scheme = isset($extras['secure'])?"https":"http";
    $cookie_url    = "$cookie_scheme://{$host}{$extras['path']}";
    $this->url     = new url($cookie_url);
  }

  function __get($key){
    if(in_array($key, array('name', 'value', 'expire')))
        return $this->$key;

    if(in_array($key, array('domain', 'sub', 'path')))
        return $this->url->$key;

    if(method_exists($this, $getter = "get_$key"))
        return $this->$getter();
  }

  function is_valid(){
    return !($this->is_expired() ||
        in_array($this->value, array("deleted", "false", false)));
  }

  function is_expired(){
    return $this->expire ? $this->expire < _NOW : false;
  }

  function match($url){

    $match = $this->domain == $url->domain
        && ( $this->sub ? substr($url->sub, -strlen($this->sub)) == $this->sub : true )
        && ( $this->domain_restricted ? $url->sub == $this->sub : true)
        && ( substr($url->path,0,strlen($this->path))==$this->path );

    return $match;
  }

  function get_hash(){
    $dot = ($this->$domain_restricted?"":".");
    $str = "{$this->url->scheme}://{$this->name}@{$dot}{$this->url->host}";
    return $str;
  }

  function __toString(){
    $expire  = $this->expire?date('[d/m/Y H:i:s]', $this->expire):"[session]";
    return "{$this->hash}/$expire";
  }

  public static function from_header($header_str, $extras, $url){

    list($name, $value) = explode('=', $header_str, 2);
    $extras = array_change_key_case((array) $extras, CASE_LOWER);

    $host = $extras['domain']; if(!$host) $host = $url->host;
    return new cookie($name, $value, $host, $extras);
  }

  function under_authority($url){
    $auth = $this->domain == $url->domain;
        //&& substr($this->sub, -strlen($url->sub)) == $url->sub;
    return $auth;
  }

}


