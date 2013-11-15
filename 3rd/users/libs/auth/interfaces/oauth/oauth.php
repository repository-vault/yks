<?

class oAuth {
  protected $client_id;
  protected $client_secret;

  function __construct($client_id, $client_secret){
    $this->client_id     = $client_id;
    $this->client_secret = $client_secret;
    $this->credentials   = compact('client_id', 'client_secret');

  }

  public function sign($str){
    return hash_hmac("sha1", $str, $this->client_secret);
  }

  public static function forge_url($url, $data) {
    return $url."?".http_build_query($data, "" ,"&");
  }

  public static function json_call($url, $data, $method = "GET"){
    return json_decode(self::call_url($url, $data, $method), true);

  }
  public static function rest_call($url, $data, $method = "GET"){
    parse_str(self::call_url($url, $data, $method), $ttoken); 
    return $ttoken;    
  }

  private static function call_url($url, $data, $method){
    $query = http_build_query($data, "" ,"&");
    if($method == "GET") $url.="?$query";

    $opts = array('http' => array(
        'method'  => $method,
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $method == "POST" ? $query : "",
    )); $ctx = stream_context_create($opts);


    $result = file_get_contents($url, false, $ctx);

    return $result;
  }

}
