<?

class browser {
  public $document;
  private $windows_list = array();

  private $cookiejar;
  private $url;
  private $start_url;
  private $lnk;


  function __construct(){
    $this->cookiejar = new cookiejar();
    $this->ua        = $this->forge_ua();
  }

  function open($url){
    $window = new window($this);
    $window->go($url);

    return $window;
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
