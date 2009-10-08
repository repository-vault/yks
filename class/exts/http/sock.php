<?

class sock   {
  private $lnk;
  protected $host;
  protected $port;
  protected $enctype;
  public static $trace = false;
  public $response;

  private $metas = array(
    'user-agent' => "Mozilla/5.0 (Windows;) Gecko/2008092417 Firefox/3.0.3",
  );

  function __construct($host, $port=80, $enctype=""){
    $this->host = $host;
    $this->port = $port;
    $this->enctype = $enctype;
  }
  function __destruct(){
    $this->close();
  }

  function request($url, $method = 'GET', $data = '', $extra_headers = array() ){
    if(!$this->lnk) $this->connect();

    stream_set_timeout($this->lnk, 3);

    $query = $this->forge_query($url, $method, $data, $extra_headers);
    fputs($this->lnk, $query);
    $response = $this->fetch_response();
    if(self::$trace){
        echo CRLF.str_repeat('-', 60).CRLF.$url.CRLF;
        print_r($query);
        print_r($response);
    }

    $this->process_response();
  }


  function process_response() { }


  function forge_query_headers($extra_headers){

    $headers = array(
        'Host'          =>$this->host,
        'Connection'    =>'keep-alive',
        'Keep-Alive'    =>300,
        'User-Agent'    => $this->metas['user-agent'],
    ); // -- 'Referer'       =>'',

    if($extra_headers)
        $headers = array_merge($headers, $extra_headers);

    return $headers;
  }


  private function forge_query($url, $method, $data, $headers){
    $this->query = array();
    $this->query['url']  = ($this->enctype=="ssl://"?"https":"http")."://{$this->host}{$url}";
    $this->query['data'] = $data;

    $this->query['headers'] = $this->forge_query_headers($headers);

    $this->query['raw'] = "$method $url HTTP/1.1".CRLF;
    $this->query['raw'] .= mask_join('', $this->query['headers'], '%2$s: %1$s'.CRLF);
    $this->query['raw'] .= CRLF;

    $this->query['raw'].= $this->query['data'];
    return $this->query['raw'];
  }


  private function fetch_response(){
    $try=0; do {
        $head="";
        $start_time = time();
        while(($tmp = fgets($this->lnk))!="\r\n") {
            $head.=$tmp;
            if(time() - $start_time > 3)
                throw new Exception("Too much time for receiving headers");
        }
        preg_match("#HTTP/... ([0-9]{3}) #", $head, $out);
        $code=(int)$out[1];
    } while((!$code) && ($try++<10));

    $this->response = array();
    $this->response['raw']  = $head;
    $this->response['code'] = $code;
    return $this->response['raw'];
  }


  function receive($filter=false, &$ret=NULL){
    $bfilter = (bool)$filter;
    if(!$this->lnk) return false;

    $file_size = (int)$this->response['headers']['Content-Length']->value;
    $chunked = (string) $this->response['headers']['Transfer-Encoding'] == "chunked";

    $body='';
    $time_limit = 20;
    set_time_limit($time_limit); 
    if($file_size){
        stream_set_blocking($this->lnk, 0);
        while(strlen($body)<$file_size-1 ){
            if(!$tmp=fgets($this->lnk, 1024)) continue; $body.=$tmp;

            set_time_limit($time_limit ); 
            if($bfilter && preg_match($filter,$body,$ret))return true;
            if(strlen($body)>=$file_size-1) return $body;
        } $this->end_headers(); return $body;
    } elseif($chunked){
        stream_set_blocking($this->lnk,1);
         do {
            $tmp=fgets($this->lnk);
            $chunk=substr($tmp,0,strspn($tmp,"abcdef0123456789"));
            $chunk_size=hexdec($chunk);$file_size+=$chunk_size;$tmp='';
            while(strlen($tmp)<$chunk_size && !feof($this->lnk))
                $tmp.=fgets($this->lnk);
            $body.=substr($tmp,0,$chunk_size);
            if($bfilter && preg_match($filter,$body,$ret)){$this->end_headers();return true;}
        }while($chunk!=="0");$this->end_headers();return $body;
    }
  }



  protected function end_headers(){
    if($this->response['headers']['Connection']=='close') $this->close();
    elseif($file_size = $this->response['headers']['Content-Length']->value) { //empty buffer
        stream_set_blocking($this->lnk, 0); $body="";
        while(strlen($body)<$file_size-1 ) $body.=fgets($this->lnk,1024);
    }
  }

  protected function connect(){
    $this->lnk = @fsockopen($this->enctype.$this->host,$this->port);
    if(!$this->lnk) 
        throw new Exception("Unable to connect '$this->host':$this->port");
  }

  protected function close(){ 
    if($this->lnk) fclose($this->lnk);
    $this->lnk=null;
  }

}


