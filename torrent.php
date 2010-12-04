<?

class torrent implements ArrayAccess {
  const MIME_TYPE = "application/x-bittorrent";
  private $struct;  
  private $file_path;
  function __construct($file){
    $this->struct = bencode::decode($file);
  }

  static function from_file($file_path){
    $tor = new self(file_get_contents($file_path));
    $tor->file_path = $file_path;
    return $tor;
  }

  function bencode(){
    return bencode::encode($this->struct);
  }



  function save($file_path = null){
    $file_path = pick($file_path, $this->file_path);
    if(!$file_path) throw new Exception("Invalid file path");
    file_put_contents($file_path, $this->bencode());
  }

  function tracker_exclude($domain){
    if($this['announce-list'])
    foreach($this['announce-list'] as $tid=>$tracker){
      foreach($tracker as $aid=>$announce) {
        $announce = parse_url($announce);
        if(ends_with($announce['host'], $domain))
          unset($this->struct['announce-list'][$tid][$aid]);
      } if(!$this->struct['announce-list'][$tid])
          unset($this->struct['announce-list'][$tid]);
    }
    $announce = parse_url($this['announce']);
    if(ends_with($announce['host'], $domain)) {
      if(!$this['announce-list'])
        throw new Exception("No tracker to fallback");
       $this->struct['announce'] = reset(reset($this['announce-list']));
    }
  }




  function tracker_add($tracker){
    $this->struct['announce-list'][] = array($tracker);
  }

  function __toString(){
    $struct = $this->struct;
    unset($struct['info']['pieces']);
    return print_r($struct,1);
  }


  function offsetGet ($key){ return $this->struct[$key];}
  function offsetSet($key, $v) { }
  function offsetExists( $key){ }
  function offsetUnset($key){ }
  function __get($key){
    if(method_exists($this, $getter = "get_$key"))
      return $this->$getter();
    if(method_exists('torrents', $getter))
      return call_user_func(array('torrents', $getter), $this);
  }
}