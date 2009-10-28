<?

// xhr, sock derivation to wrap $browser specs (and nothing more)

class xhr extends sock {
  private $browser;
  private $url;

  function __construct($browser, $url){
    $this->browser = $browser;

    if(!$url->host)
        throw new Exception("Invalid host");
    $this->set_url($url);
  }

  function __get($key){
    if($key=="url") return $this->url;
  }

  function execute(request $query){
    $this->request($query->url->http_query, $query->method, $query->data, $query->headers);
  }


  private function set_url($url){
    if($this->url->host && $url->host != $this->url->host)
        $this->close();

    $this->url     = $url;

    $port    = $url->is_ssl?443:80;
    $enctype = $url->is_ssl?'ssl://':'';

    parent::__construct($url->host, $port, $enctype);
  }


  function process_response(){
    $this->response['headers'] = http::parse_headers($this->response['raw']);

    $cookies_headers = (array) $this->response['headers']['Set-Cookie'];
    foreach($cookies_headers as $header){
        $cookie = cookie::from_header($header->value, $header->extras, $this->url);
        $this->browser->store_cookie($this->url, $cookie);
    }

    if(in_array($this->response['code'], array(302,301) ) )
        $this->follow_redirect();
  }


  function follow_redirect(){
    $this->end_headers();
    $location = new url((string) $this->response['headers']['Location']);
    $url = $this->url->merge($location);
    $this->set_url($url);
    $this->request($this->url->http_query);
  }

  function forge_query_headers($headers = array()){

    $headers = parent::forge_query_headers($headers);

    $headers = array_merge($headers, $this->browser->ua->headers);

    //$query_url = $this->query['url']; and $this->url are the same
    $cookies = $this->browser->get_cookies($this->url);
    if($cookies) {
        $cookies_tmp = array();
        foreach($cookies as $cookie)
            $cookies_tmp []= "$cookie->name=$cookie->value";
        $headers['Cookie'] = join('; ', $cookies_tmp);
    }    

    return $headers;
  }

}

