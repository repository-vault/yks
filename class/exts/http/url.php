<?

class url  extends __native {

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
        'host'=>$this->host,
        'scheme'=>$this->scheme,
        'path'=>$path,
        'query'=> $url->query?$url->query:($path!=$this->path?false:$this->query),
        'fragment'=>$url->fragment,
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
    if(!$this->scheme || !$this->host) return false;
    $str = "{$this->scheme}://{$this->host}";
    $str .=$this->path;
    if($full){
        if($this->query)$str.="?{$this->query}";
        if($this->fragment)$str.="#{$this->fragment}";
    } return $str;
  }

  function get_http_query(){
    $str = $this->path;
    if($this->query)$str.="?".($this->query);
    return $str;
  }


  function __toString(){
    return $this->get_hash(true);
  }
}