<?
/* 131's
*  http://doc.exyks.org/wiki/Source:ext/http/proxy
*/

class http_cb_proxy extends http_proxy {
  private $callbacks;

  function attach($file_type, $callback) {
    $this->callbacks[$file_type] = $callback;
  }

  protected function serve_std($dest_stream, $client_stream){
    if(!$this->callbacks) {
        $this->stream_copy_to_stream($dest_stream, $client_stream);
        return ;
    }

    $headers_str  = $this->read_headers($dest_stream);
    $headers      = http::parse_headers($headers_str);
    $content_type = $headers['Content-Type']->value;
    $callback     = $this->callbacks[$content_type];

    if($callback) {
      $out_file    = files::tmppath();
      $file_stream = fopen($out_file, "wb");
      $gziped = $headers['Content-Encoding']->value == "gzip";
      $this->stream_copy_to_stream($dest_stream, $file_stream);
      fclose($file_stream);
      if($gziped) file_put_contents($out_file, file_get_contents("compress.zlib://$out_file"));
      call_user_func($callback, $client_stream, $out_file, $headers);
    } else {
      fwrite($client_stream, $headers_str);
      $this->stream_copy_to_stream($dest_stream, $client_stream);
    }
  }

}

