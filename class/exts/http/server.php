<?php

class http_server extends http_aserver {

  protected $paths = array();
  protected $cgi_handler = array();

  function register_path($local_path, $virtual_path) {
    $this->paths[$virtual_path] = $local_path;
  }

  function register_cgi_handler($ext, $bin_path, $mime_type = false){
    $this->cgi_handler[$ext] = compact('bin_path', 'mime_type');
  }

  protected function serve_client($client_stream){

    list($method, $url_infos, $request_str) = parent::serve_client($client_stream);

    $local_path = $this->resolve_path($url_infos['path']);
    if(!is_file($local_path)) {
      fwrite($client_stream, "HTTP/1.0 404 Not found".CRLF.CRLF);
      return;
    }
  
    $ext = files::ext($local_path);
    rbx::ok("Ext is $ext");
    if($this->cgi_handler[$ext]) {
      $this->serve_cgi($client_stream, $local_path, $ext);
      return;
    }
    
    $header  = "HTTP/1.0 200 Ok".CRLF;
    $header .= "Content-type: text/plain".CRLF;
    $header .= "Connection: close".CRLF;
    $header .= "Date: ".date(DATE_RFC1123).CRLF;
    $header .= "Content-Length: ".filesize($local_path).CRLF;

    fwrite($client_stream, $header.CRLF);

    $dest_stream = fopen($local_path, 'r');

    $this->serve_std($dest_stream, $client_stream);
  }

  private function serve_cgi($client_stream, $local_path, $ext){
    $cgi = $this->cgi_handler[$ext];
    $cmd = "{$cgi['bin_path']} $local_path";
    $pop = popen($cmd, 'r');

    $headers  = "HTTP/1.0 200 Ok".CRLF;
    $headers .= "Connection: close".CRLF;
    $headers .= "Date: ".date(DATE_RFC1123).CRLF;
    if($cgi['mime_type']) {
      $headers .= "Content-type: {$cgi['mime_type']}".CRLF;
      $headers .= CRLF;
    }

    fwrite($client_stream, $headers);

    $this->serve_std($pop, $client_stream);
  }


  private function resolve_path($url){
    $resolved = "";
    foreach($this->paths as $virtual_path => $path){
      if(!starts_with($url, $virtual_path)) continue;
      if(strlen($virtual_path) > strlen($resolved)) $resolved = $virtual_path;
    }
    if(!$resolved)
      return false;

    $path = $this->paths[$resolved].strip_start($url,$resolved);
    return $path;
  }



}