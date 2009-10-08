<?

class browser {
  public $document;
  private $windows_list = array();

  private $cookiejar;
  private $url;
  private $start_url;
  private $lnk;

  private $session_key = 'BROWSING_SESSION';

  function __construct($session_key = false){
    $this->cookiejar = new cookiejar();
    $this->ua        = $this->forge_ua();
    if($session_key) $this->session_load($session_key);
  }

  function open($url){
    $window = new window($this);
    $window->go($url);

    return $window;
  }


  function session_clean(){
    $file = "{$this->session_key}.srz";
    unlink($file);
  }

  function session_save(){
    $srz = serialize($this->cookiejar);
    $file = "{$this->session_key}.srz";

    file_put_contents($file, $srz);
  }
  function session_load($session_key){
    $this->session_key = $session_key;
    $file = "$session_key.srz";
    if(!is_file($file)) return false;
    $data = file_get_contents($file);
    $data = unserialize($data);
    $this->cookiejar   = $data;
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

  function download($url){

    $url = url::from($url);

    if(!$url->is_browsable)
        throw new Exception("Invalid url");

    $lnk = $this->get_lnk($url);
    $query = new request($url, "GET");
    $lnk->execute($query);

        //if($lnk->url != $this->url)//has been redirected
        //    $this->url = $lnk->url;

    return  $lnk->receive();
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
