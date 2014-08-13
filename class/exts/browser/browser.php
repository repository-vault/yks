<?php

class browser {
  public $document;
  private $windows_list = array();

  private $cookiejar;
  private $proxy;
  private $url;
  private $start_url;
  private $lnk;

  private $session_key = 'BROWSING_SESSION';

  function init(){
    classes::extend_include_path(dirname(__FILE__));
    http::init();
  }

  function __construct($session_key = false){
    $this->cookiejar = new cookiejar();
    $this->ua        = $this->forge_ua();
    if($session_key) $this->session_load($session_key);
  }

  function set_proxy($proxy_url){
    $this->proxy = $proxy_url;
  }

  function open($url = false){ //can open an empty tab
    $window = new window($this);
    if($url) $window->go($url);

    return $window;
  }

    //alias for new window go
  function go(){
    $args = func_get_args();
    return call_user_func_array(array($this->open(), 'go'), $args);
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
    if(!is_a($data, "cookiejar")) return false;

    $this->cookiejar   = $data;
    return true;
  }


  function get_cookies($url, $cookie_name = false){
    return $this->cookiejar->retrieve($url, $cookie_name);
  }



  private $credentials = array();
  public function register_credential($host_url, $login, $pswd){
    $url = url::from($host_url);
    $this->credentials[$url->http_host] = compact('login', 'pswd', 'url');
  }

  public function get_credentials($url){
    $credentials = array();
    foreach($this->credentials as $host => $cred)
      if($url->match($cred['url'])) $credentials[] = $cred;
    return $credentials;
  }

    // public
  public function adopt_cookies($url, $cookies){
    $url = url::from($url);
    foreach($cookies as $name=>$value)
        $this->store_cookie($url, new cookie($name, $value, $url->host));
  }

    //internal
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
    if($this->proxy)
      $lnk->set_proxy($this->proxy);
    return $lnk;
  }


  function download($url, $out = false, $headers = array()){
    $url = url::from($url);

    if(!$url->is_browsable)
        throw new Exception("Invalid url");

    if(is_resource($out))
      $out_stream = $out;
    elseif(is_string($out)) {
      $out_stream = fopen($out, "w");
    } else $out_stream = fopen('php://temp', 'w');


    $lnk = $this->get_lnk($url);
    $query = new request($url, "GET");
    if($headers)
      $query->addHeaders($headers);

    $lnk->execute($query);
    $lnk->receive($out_stream);

    if(is_resource($out))
      return;
    elseif(is_string($out)) {
      fclose($out_stream);
      return;
    } else {
      rewind($out_stream);
      return stream_get_contents($out_stream);
    }

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
