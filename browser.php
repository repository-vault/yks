<?

class browser {
  public $document;
  private $windows_list = array();

  private $cookiejar;
  private $url;
  private $start_url;
  private $lnk;
  const sess_key = 'BROWSING_SESSION';

  function __construct(){
    $this->cookiejar = new cookiejar();
    $this->ua        = $this->forge_ua();
  }

  function open($url){
    $window = new window($this);
    $window->go($url);

    return $window;
  }


  function session_clean($session_key = browser::sess_key){
    $file = "$session_key.srz";
    unlink($file);
  }

  function session_save($session_key = browser::sess_key){
    $srz = serialize($this->cookiejar);
    $file = "$session_key.srz";

    file_put_contents($file, $srz);
  }
  function session_load($session_key = browser::sess_key){
    $file = "$session_key.srz";
    if(!is_file($file)) return false;
    $data = file_get_contents($file);
    $data = unserialize($data);
    $this->cookiejar = $data;
    return true;
  }


  function get_cookies($url){
    return $this->cookiejar->retrieve($url);
  }

  function store_cookie($url, $cookie){

    if(!$cookie->under_authority($url)){
        print_r($cookie);
        print_r($url);
        die;
    }
        //throw new Exception("Cookie has no authority");

    return $this->cookiejar->store($cookie);
  }

  function get_lnk($url){
    $lnk = new xhr($this, $url);
    return $lnk;
  }

  function close(){
    $this->lnk->close();
  }

  function forge_ua(){
    $ua = new stdClass();
    $ua->name     = "Mozilla/5.0 (Windows) Gecko/20090715 Firefox/3.5.1";
    $ua->language = "en-us,en;q=0.8,fr-fr;q=0.5,nl;q=0.3";
    $ua->headers  = array(
        'User-Agent'      => $ua->name,
        'Accept-Language' => $ua->language,
    );
    return $ua;

  }

}
