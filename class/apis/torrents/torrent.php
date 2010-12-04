<?

class torrent {
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

  function filter_tracker($domain){
    foreach($this->struct['announce-list'] as $tid=>$tracker){
      foreach($tracker as $aid=>$announce) {
        $announce = parse_url($announce);
        if(ends_with($announce['host'], 'thepiratebay.org'))
          unset($this->struct['announce-list'][$tid][$aid]);
      } if(!$this->struct['announce-list'][$tid])
          unset($this->struct['announce-list'][$tid]);
    }
  }

  function add_tracker($tracker){
    $this->struct['announce-list'][] = array($tracker);
  }

}