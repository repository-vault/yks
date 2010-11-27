<?
/* 131's
*  http://doc.exyks.org/wiki/Source:ext/http/proxy
*/

class http_proxy {
  private $ip;
  private $client_bind;
  private $port;  
  private $server;

  protected $trace        = true;
    //use binding on connect mode
  protected $bind_on_ssl  = false;

  protected $auths  = array();
  protected $exclude_dests  = array();

  function __construct($ip = '0.0.0.0', $port = '8080'){
    $bind = "tcp://$ip:$port";
  
    $this->server = stream_socket_server($bind, $errno, $errstr);
    if (!$this->server) 
      throw new Exception ("$errstr ($errno)");
  }

  function close(){
    fclose($this->server);
  }

  function allow_account($login, $password){
    $this->auths['accounts'][$login] = $password;
  }

  function allow_from($host){
    $this->auths['hosts'][] = $host;
  }


  function bind_exclude($dest){
    $this->exclude_dests[] = $dest;
  }

  function bind_to($client_bind){
    $this->client_bind = $client_bind;
    $opts = array(
        'socket' => array(
            'bindto' => "{$this->client_bind}:0",
        ),
    ); $this->stream_context = stream_context_create($opts);
  }

  function run(){
    $this->wait_for_clients();
  }

  private function wait_for_clients(){
    if($this->trace) echo "Waiting for clients \r\n";
    $serve_client = extension_loaded('pcntl') ? "fork_client" : "serve_client";
    //$serve_client = "serve_client";

    do {
    if ($client = stream_socket_accept($this->server))
      $this->$serve_client($client);
    } while(true);
    $this->close();
  }


  private function fork_client($client){
    $pid = pcntl_fork();
    if ($pid == -1) {
         die('could not fork');
         return;
    } else if ($pid) {
        pcntl_wait($c_status,WNOHANG);
        fclose($client);
        return; //waitagain

    } else {
        $this->serve_client($client);
        die;
    }
  }

/****************************** ***********************/

  protected function serve_client($client_stream){
    list($method, $url_infos, $request_str) = $this->read_request($client_stream);

    if(!$method)
      return fclose($client);

        //open a pipe to the destination
    if($method == "GET" || $method == "POST") {
      $dest_stream = $this->prepare_std($client_stream, $method, $url_infos, $request_str);
      $this->serve_std($dest_stream, $client_stream);
    } elseif($method == "CONNECT") {
      $dest_stream = $this->prepare_connect($client_stream, $url_infos, $request_str);
      $this->serve_connect($dest_stream, $client_stream);
    }
  }


  protected function read_request($client_stream) {
    $request_str = $this->read_headers($client_stream);
    if($this->trace) echo $request_str;
    $mask = '#^(GET|POST|CONNECT)\s*(.*?)\s*HTTP/[0-9]\.[0-9]\r\n#';
    if(!preg_match($mask, $request_str, $out))
        return null;

      //drop the request first line
    $request_str = substr($request_str, strlen($out[0]));

    list(, $method, $url, $version) = $out;
    $url_infos = parse_url($url);

    if($this->auths)
      $this->proxy_authenticate($client_stream, $request_str);

    return array($method, $url_infos, $request_str);
  }

  protected function proxy_authenticate($client_stream, &$request_str){
      //check host
    $remote_addr = strtok(stream_socket_get_name($client_stream, true),":");
    $remote_host = gethostbyaddr($remote_addr);

    if(  in_array($remote_addr, $this->auths['hosts'])
      || in_array($remote_host, $this->auths['hosts']) )
      return;

      //check account
    $proxy_auth = false;
    $mask = "#^Proxy-Authorization:\s*Basic\s(.*)\r\n#m";
    if(preg_match($mask, $request_str, $out)) {
      $full = base64_decode($out[1]);
      list($proxy_login, $proxy_pswd) = explode(":", $full, 2);
      $proxy_auth = ($this->auths['accounts'][$proxy_login] == $proxy_pswd);

        //dont fw proxy predentials
      $request_str = preg_replace($mask, "", $request_str);
    }
    
    if(!$proxy_auth) {
      //    Authorization: Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==
      $failure = "HTTP/1.0 407 Proxy Authentication Required".CRLF;
      $failure .= "Proxy-Authenticate:Basic realm=\"Login\"".CRLF.CRLF;
      fwrite($client_stream, $failure);
      die;
    }
  }

  protected function prepare_connect($client_stream, $url_infos, $request_str) {
    $dest_stream = $this->open_dest($url_infos, $this->bind_on_ssl);
    $success  = "HTTP/1.0 200 Connection Established".CRLF;
    $success .= "Proxy-agent: Apache".CRLF.CRLF;
      $res = fwrite($client_stream, $success);
    return $dest_stream;
  }


    //return  $dest lnk
  protected function prepare_std($client_stream, $method, $url_infos, $request_str) {

    $dest_stream = $this->open_dest($url_infos);

    $url_query   = $url_infos['path'];
    if(isset($url_infos['query']))
        $url_query.="?".$url_infos['query'];

    $request_head = "$method {$url_query} HTTP/1.0".CRLF;
    $request_str  = $request_head.$request_str;
      fwrite($dest_stream, $request_str);

    //if GET, just go the response
    if($method =="GET")
      return $dest_stream;

    $mask = "#^Content-Length:\s*([0-9]+)#m";
    $request_length = preg_match($mask, $request_str, $out) ? $out[1] : 0;

    //read the whole request and send it to the destination
    $this->stream_copy_to_stream_nb($client_stream, $dest_stream, $request_length);
    return $dest_stream;
  }

  /******** Utils : Hookables ***************************/

  protected function serve_connect($dest_stream, $client_stream){
    $this->tunnel_streams($dest_stream, $client_stream);
  }

  protected function serve_std($dest_stream, $client_stream){
      $this->stream_copy_to_stream($dest_stream, $client_stream);
  }

  /******** Utils : internals ***************************/

  protected function open_dest($url_infos, $use_binding = true) {
    $dest_host = $url_infos['host'];
    $dest_port = $url_infos['port'] ? $url_infos['port'] : 80;
    if($this->trace) echo "openning $dest_host:$dest_port".CRLF;

    $use_binding &= !in_array($dest_host, $this->exclude_dests);
    if($this->client_bind && $use_binding) {
      $remote_socket = "tcp://$dest_host:$dest_port";
      $dest = stream_socket_client ($remote_socket , $errno,
          $errstr , 30, STREAM_CLIENT_CONNECT , $this->stream_context);
    } else $dest = fsockopen($dest_host, $dest_port);

    return $dest;
  }



  /******** Utils : statics ***************************/

 protected function read_headers($lnk){
    $headers_str = "";
    do {
      $line = fgets($lnk);
      $headers_str .= $line;
    } while($line != CRLF && $lnk);
    return $headers_str;
   }


  protected function stream_copy_to_stream($src, $dest){
    while($tmp=fread($src,1024)) fwrite($dest, $tmp);
  }

  protected function stream_copy_to_stream_nb($src, $dst, $content_length){
    $sent_bytes = 0;
    stream_set_blocking($src, 0);
    while($sent_bytes< $content_length) {
      $read = array($src);
      $res = stream_select($read, $null, $null, null);
      if($res === false) break;
      if(feof($src) || feof($dst)) break;
      $sent_bytes += fwrite($dst, fread($src, 8193));
    }
  }

  protected function tunnel_streams($src, $dst){

    do {
      $read = array($src, $dst);
      $res = stream_select($read, $null, $null, NULL);
      if($res === false) break;
      //if($res === 0) continue; //should not append
      if(in_array($src, $read, true)) fwrite($dst, fread($src, 8193));
      if(in_array($dst, $read, true)) fwrite($src, fread($dst, 8193));

      if(feof($src) || feof($dst)) break;

    } while (true);
    fclose($src);
    fclose($dst);

  }


}

