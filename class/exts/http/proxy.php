<?

/* 131's
*  http://doc.exyks.org/wiki/Source:ext/http/proxy
*/

class http_proxy extends http_aserver {
  private $client_bind;
  protected $auths  = array();
  protected $exclude_dests  = array();

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

/****************************** ***********************/

  protected function serve_client($client_stream){

    list($method, $url_infos, $request_str) = parent::serve_client($client_stream);

    if(!$method)
      return fclose($client);

        //open a pipe to the destination
    if($method != "CONNECT") {
      $dest_stream = $this->prepare_std($client_stream, $method, $url_infos, $request_str);
      $this->serve_std($dest_stream, $client_stream);
    } else {
      $dest_stream = $this->prepare_connect($client_stream, $url_infos, $request_str);
      $this->serve_connect($dest_stream, $client_stream);
    }
  }

  protected function read_request($client_stream) {

    list($method, $url_infos, $request_str) = parent::read_request($client_stream);

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

      //we are a poor 1.0 proxy
    $request_head = "$method {$url_query} HTTP/1.0".CRLF;
    $request_str  = $request_head.$request_str;
    $dropped_headers = array('Keep-Alive', 'Proxy-Connection', 'TE', 'Connection');
    $request_str  = preg_replace("#^(?:".join('|', $dropped_headers)."):.*\r?\n#m", "", $request_str);

    fwrite($dest_stream, $request_str);

    $bodyless_methods = array('GET', 'HEAD');
    //if GET, just go the response
    if(in_array($method, $bodyless_methods))
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


  /******** Utils : internals ***************************/

  static function same_domain($domain0, $domain1) {
    return join('.', array_slice(explode('.', $domain1),-count(explode('.', $domain0)))) == $domain0;
  }
  
  protected function open_dest($url_infos, $use_binding = true) {
    $dest_host = $url_infos['host'];
    $dest_port = $url_infos['port'] ? $url_infos['port'] : 80;
    if($this->trace) echo "openning $dest_host:$dest_port".CRLF;

    foreach($this->exclude_dests as $domain)
      $use_binding &= !self::same_domain($domain, $dest_host);

    if($this->client_bind && $use_binding) {
      $remote_socket = "tcp://$dest_host:$dest_port";
      $dest = stream_socket_client ($remote_socket , $errno,
          $errstr , 30, STREAM_CLIENT_CONNECT , $this->stream_context);
    } else $dest = fsockopen($dest_host, $dest_port);

    return $dest;
  }


}

