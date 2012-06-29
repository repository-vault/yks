<?php

//Hi, I'm sock, i'm as smart as an oyster

class sock   {

  const TRANSPORT_USER = 1;
  const TRANSPORT_NATIVE = 2; //file_get_contents
  const HTTP_1_0 = "1.0"; //Or 1.0
  const HTTP_1_1 = "1.1"; //Or 1.0


  private $lnk;
  protected $host;
  protected $port;
  protected $enctype;

  private $proxy;
  private $proxy_infos;
  public static $trace = false;

  public static $transport_type = sock::TRANSPORT_USER;
  public static $http_version   = sock::HTTP_1_1;

  public $response; //headers
  private $contents; //contents


  function __construct($host, $port=80, $enctype=""){
    $this->host = $host;
    $this->port = $port;
    $this->enctype = $enctype;
  }
  function __destruct(){
    $this->close();
  }


  function set_proxy($proxy){
    $this->proxy = $proxy;
    $this->proxy_infos = parse_url($this->proxy);
    $this->proxy_infos['port'] = pick($this->proxy_infos['port'], 8080);
    //$this->proxy_infos['auth'] = pick($this->proxy_infos['port'], 8080);
  }

  static function trace($msg){
    if(!self::$trace) return;
    if(self::$trace == "error_log") error_log($msg);
    elseif(is_string(self::$trace)) file_put_contents(self::$trace, $msg, FILE_APPEND);
    else echo $msg;
  }



  function request($url, $method = 'GET', $data = '', $extra_headers = array() ){

    $this->forge_query($url, $method, $data, $extra_headers);
    $this->response = array();

    switch(self::$transport_type) {
      case self::TRANSPORT_USER:
        $this->request_user($url, $method, $data, $extra_headers);
        break;
      case self::TRANSPORT_NATIVE:
        $this->request_native($url, $method, $data, $extra_headers);
        break;
    }


    if(self::$trace) {  
        $str  = CRLF.self::$transport_type." ".str_repeat('-', 60).CRLF.$this->query['raw_url'].CRLF;
        $str .= print_r($this->query['raw'],1).CRLF;
        $str .= print_r($this->response['raw'], 1);
        $str .= CRLF.str_repeat('-', 60).CRLF;
        self::trace($str);
    }


    $this->response['headers'] = http::parse_headers($this->response['raw']);
    $this->process_response();
  }


  private function request_native(){
    global $http_response_header;

    if (version_compare(PHP_VERSION, '5.3.0') < 0) 
      throw new Exception("Need at lease PHP 5.3 (chunk filter)");

    $opts = array('http' => array(
        'method'  => $this->query['method'],
        'header'  => $this->query['headers_str'],
        'content' => $this->query['data'],

        'ignore_errors'    => true,
        'timeout'          => 10,
        'max_redirects'    => 1,
        'protocol_version' => self::$http_version,
    ));

    if($this->proxy)
        $opts['http']['proxy'] = (string)$this->proxy;


    $ctx = stream_context_create($opts);

    $this->contents = file_get_contents($this->query['url'], false, $ctx);

    $response = join(CRLF, $http_response_header);

    preg_match("#HTTP/... ([0-9]{3}) #", $response, $out); $code = (int)$out[1];

    $this->response['raw']     = $response;
    $this->response['code']    = $code;
  }

  private function request_user(){
    if(!$this->lnk) $this->connect();
    stream_set_timeout($this->lnk, 3);
    fputs($this->lnk, $this->query['raw']);

    $try=0; do {
        $head_str = "";
        $start_time = time();
        while(($tmp = fgets($this->lnk)) != CRLF) {
            $head_str .= $tmp;
            if(time() - $start_time > 3)
                throw new Exception("Too much time for receiving headers");
        }
        preg_match("#HTTP/... ([0-9]{3}) #", $head_str, $out); $code=(int)$out[1];
    } while((!$code) && ($try++<10));

    $this->response['raw']     = $head_str;
    $this->response['code']    = $code;
  }


  protected function process_response() { }


  private function host_str(){
    $host_str = $this->host;
    if(!in_array($this->port, array(80,443)))
      $host_str .= ":{$this->port}";
    return $host_str;
  }

  function forge_query_headers($extra_headers){
    $headers = array(
        'Host'          => $this->host_str(),
        'Connection'    => 'close',
    ); // -- 'Referer'       =>'',

    if($this->proxy) {
        
    } elseif (self::$http_version == self::HTTP_1_1
      && self::$transport_type == self::TRANSPORT_USER)
      $headers = array_merge($headers, array(
        'Connection'    =>'keep-alive',
        'Keep-Alive'    =>300,
      ));

    if($extra_headers)
        $headers = array_merge($headers, $extra_headers);

    return $headers;
  }


  private function forge_query($url, $method, $data, $headers){
    $host_str = $this->host_str();

    $this->query = array();
    $this->query['url']         = ($this->enctype=="ssl://"?"https":"http")."://{$host_str}{$url}";
    $this->query['raw_url']     = $url;
    $this->query['data']        = $data;
    $this->query['headers']     = $this->forge_query_headers($headers);
    $this->query['headers_str'] = mask_join('', $this->query['headers'], '%2$s: %1$s'.CRLF);
    $this->query['method']      = $method;

    if($this->proxy) {
      $this->query['raw'] = "$method {$this->query['url']} HTTP/1.0".CRLF;
    } else {
      $this->query['raw'] = "$method $url HTTP/".(self::$http_version).CRLF;
    }
    $this->query['raw'] .= $this->query['headers_str'];
    $this->query['raw'] .= CRLF;

    $this->query['raw'].= $this->query['data'];
    return $this->query['raw'];
  }



  private function receive_chunked($out){
    stream_filter_append($this->lnk, "dechunk", STREAM_FILTER_READ);
    stream_copy_to_stream($this->lnk, $out);
  }

  private function receive_classic($out, $file_size){
    stream_copy_to_stream($this->lnk, $out, $file_size);
  }

  private function receive_end_lnk($out){
    stream_copy_to_stream($this->lnk, $out);
  }

  function receive($out){

    $file_size = $this->response['headers']['Content-Length']->value; //might be zero
    $chunked   = (string) $this->response['headers']['Transfer-Encoding']->value == "chunked";

    if(self::$transport_type == self::TRANSPORT_USER) {

        if(!$this->lnk) return false;

        if(is_numeric($file_size))
            $this->receive_classic($out, $file_size, $filter, $ret);
        elseif($chunked)
            $this->receive_chunked($out, $filter, $ret);
        elseif($this->proxy)
            $this->receive_end_lnk($out, $filter, $ret);
        else $this->receive_end_lnk($out);
    }
      //in native mode, this->contents contains the already downloaded contents
    return $this->contents;
  }




  protected function end_headers(){
    if(self::$transport_type == self::TRANSPORT_NATIVE)
        return;

    if($this->response['headers']['Connection']=='close') $this->close();
    elseif($file_size = $this->response['headers']['Content-Length']->value) { //empty buffer
        stream_set_blocking($this->lnk, 0); $body="";
        while(strlen($body)<$file_size-1 ) $body.=fgets($this->lnk,1024);
    }
  }

  protected function connect(){
    if(self::$transport_type == self::TRANSPORT_NATIVE)
        return;

    if($this->proxy) 
      $this->lnk = fsockopen($this->proxy_infos['host'], $this->proxy_infos['port']);
    else {
      $this->lnk = @fsockopen($this->enctype.$this->host,$this->port);
      self::trace("Open link {$this->enctype}{$this->host}:{$this->port}");
    }

    if(!$this->lnk) 
        throw new Exception("Unable to connect '$this->host':$this->port");
  }

  protected function close(){ 
    if(self::$transport_type == self::TRANSPORT_NATIVE)
        return;

    if($this->lnk) fclose($this->lnk);
    $this->lnk = null;
  }

}


