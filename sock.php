<?php

//Hi, I'm sock, i'm as smart as an oyster

class sock   {

  const TRANSPORT_USER = 1;
  const TRANSPORT_NATIVE = 2; //file_get_contents
  const HTTP_VERSION = 1.1; //Or 1.0


  private $lnk;
  protected $host;
  protected $port;
  protected $enctype;

  public static $trace = false;
  public static $transport_type = sock::TRANSPORT_USER;

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
        $str .= print_r($this->query['raw'],1);
        $str .= print_r($this->response['raw'], 1);
        $str .= CRLF.str_repeat('-', 60).CRLF;
        self::trace($str);
    }


    $this->response['headers'] = http::parse_headers($this->response['raw']);
    $this->process_response();
  }


  private function request_native(){
    global $http_response_header;

    $opts = array('http' => array(
        'method'  => $this->query['method'],
        'header'  => $this->query['headers_str'],
        'content' => $this->query['data'],

        'ignore_errors'    => true,
        'timeout'          => 10,
        'max_redirects'    => 1,
        'protocol_version' => self::HTTP_VERSION,

    )); $ctx = stream_context_create($opts);

    
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


  function process_response() { }


  function forge_query_headers($extra_headers){

    $headers = array(
        'Host'          =>$this->host,
        'Connection'    =>'close',
    ); // -- 'Referer'       =>'',

    if(self::HTTP_VERSION == 1.1
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
    $this->query = array();
    $this->query['url']         = ($this->enctype=="ssl://"?"https":"http")."://{$this->host}{$url}";
    $this->query['raw_url']     = $url;
    $this->query['data']        = $data;
    $this->query['headers']     = $this->forge_query_headers($headers);
    $this->query['headers_str'] = mask_join('', $this->query['headers'], '%2$s: %1$s'.CRLF);
    $this->query['method']      = $method;

    $this->query['raw'] = "$method $url HTTP/1.1".CRLF;
    $this->query['raw'] .= $this->query['headers_str'];
    $this->query['raw'] .= CRLF;

    $this->query['raw'].= $this->query['data'];
    return $this->query['raw'];
  }



  private function receive_chunked($filter = false, &$ret = NULL){
    $bfilter = (bool)$filter;
    $body='';

    stream_set_blocking($this->lnk,1);
     do {
        $tmp=fgets($this->lnk);
        $chunk=substr($tmp,0,strspn($tmp,"abcdef0123456789"));
        $chunk_size=hexdec($chunk);$file_size+=$chunk_size;$tmp='';
        while(strlen($tmp)<$chunk_size && !feof($this->lnk))
            $tmp.=fgets($this->lnk);
        $body.=substr($tmp,0,$chunk_size);
        if($bfilter && preg_match($filter,$body,$ret)){$this->end_headers();return true;}
    } while($chunk!=="0");$this->end_headers();

    return $body;
  }

  private function receive_classic($file_size, $filter=false, &$ret=NULL){
    $bfilter = (bool)$filter;

    $body='';
    $time_limit = 20;
    $start_time = time();

    stream_set_blocking($this->lnk, 0);
    while(strlen($body)<$file_size-1 ){
        if(time() - $start_time > $time_limit)
            throw new Exception("Too much time for receiving content block");

        if(!$tmp=fgets($this->lnk, 1024)) continue; $body.=$tmp;

        $start_time = time(); 
        if($bfilter && preg_match($filter,$body,$ret)) return true;
        if(strlen($body)>=$file_size-1) return $body;
    } $this->end_headers();

    return $body;
  }

  function receive($filer = false, &$ret=NULL){

    $file_size = (int)    $this->response['headers']['Content-Length']->value;
    $chunked   = (string) $this->response['headers']['Transfer-Encoding']->value == "chunked";

    if(self::$transport_type == self::TRANSPORT_USER) {

        if(!$this->lnk) return false;
     
        if($file_size)
            return $this->receive_classic($file_size, $filter, $ret);
        elseif($chunked)
            return $this->receive_chunked($filter, $ret);

        throw new Exception("Invalid transport type");
    }


    if($chunked)
        $this->contents = http::chunked_deflate($this->contents);

    $bfilter = (bool)$filter;
    if($bfilter && preg_match($filter, $this->contents, $ret)) return true;

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

    $this->lnk = @fsockopen($this->enctype.$this->host,$this->port);
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


