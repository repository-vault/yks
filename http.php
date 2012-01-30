<?php

class http {
  const LWSP='[\s]';
  static private $headers_multiple = array('Set-Cookie');
  static private $headers_onlyraw  = array('Location');

  static private $WIN = false;
  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    classes::register_class_paths(array(
        'header'       => CLASS_PATH."/exts/http/header.php",
        'request'      => CLASS_PATH."/exts/http/request.php",
        'sock'         => CLASS_PATH."/exts/http/sock.php",
        'url'          => CLASS_PATH."/exts/http/url.php",
        'http_proxy'   => CLASS_PATH."/exts/http/proxy.php",
        'urls'         => CLASS_PATH."/exts/http/urls.php",
        'tlds'         => CLASS_PATH."/exts/http/tlds.php",
        'console_host' => CLASS_PATH."/exts/cli/console_host.php",
    ));
    self::$WIN = stripos($_SERVER['OS'], 'windows')!==false || isset($_SERVER['WINDIR']);
  }

  static function parse_headers($headers_str){
    $headers_str = preg_replace('#'.CRLF.self::LWSP.'+#',' ',$headers_str);
    $headers = array();

    $liste = explode(CRLF, $headers_str);
    foreach($liste as $header_str) {
        $header = header::parse_string($header_str); $name = $header->name;
        if(!$header) continue;
        if(in_array($name, self::$headers_onlyraw)) $header->value = $header->value_raw;
        if(in_array($name, self::$headers_multiple)) $headers[$name][] = $header;
        else {
            $tmp = $headers[$name];
            $headers[$name] = $tmp?array_merge(is_array($tmp)?$tmp:array($tmp), array($header)):$header;
        }
    }
    return $headers;
  }


  //option might be an integer ; this is just the timeout
  public static function ping_url($url, $options = array()){
    if(is_numeric($options))
      $options = array('timeout'=>$options);

    if($options['proxy']) {
        $options['request_fulluri'] = true;
        $proxy = parse_url($options['proxy']);
        $proxy['port'] = pick($proxy['port'], 8080);
        if($proxy['scheme'] == 'http') {
          $options['proxy'] = "tcp://{$proxy['host']}:{$proxy['port']}";
          if($proxy['user'])
            $credentials = "Basic ".base64_encode("{$proxy['user']}:{$proxy['pass']}");
            $options['header'] .= "Proxy-Authorization: $credentials".CRLF;
        }
    }
    if(!$options['timeout'])
      $options['timeout'] = 3;
    $options['timeout']/=2; //php 5.1 on socket open/close (checked with sleep());
    $opts = array('http' => $options);

    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, null, $ctx);
    return $res;
  }

  private static $console_host;
  public static function connection_aborted(){

    $client_port = $_SERVER['REMOTE_PORT'];
    $client_addr = $_SERVER['REMOTE_ADDR'];

    $server_port = $_SERVER['SERVER_PORT'];
    $server_addr = $_SERVER['SERVER_ADDR'];

    if(self::$WIN) {
      if(!self::$console_host) 
          self::$console_host = new console_host('C:\\Windows\\system32\\netstat.exe -n -p tcp');
      $output = self::$console_host->exec();
    } else {
      exec("netstat -p tcp -n", $output); $output = join(CRLF, $output);  
    }
    
    $mask = "#^\s*TCP.*{$server_addr}:{$server_port}\s*{$client_addr}:{$client_port}\s*([A-Z]+)\s*$#mi";
    $status = preg_reduce($mask, $output);
    return ($status != "ESTABLISHED");
  }

  public static function head($src_url, $timeout = 3, $ip = false, $end = false){
         //timeout is unused yet

    $url = new url($src_url);

    $host_ip  = $ip ? $ip : $url->host;

    $port    = $url->is_ssl?443:80;
    $enctype = $url->is_ssl?'ssl://':'';

    $lnk = new sock($host_ip, $port, $enctype);
    $lnk->request($url->http_query, "HEAD");
    $response = $lnk->response; unset($lnk);
    $response['headers'] = self::parse_headers($response['raw']);
    return $response;
  }

  public static function chunked_deflate($str){

    $body=''; $i = 0;
     do {
        $chunk = substr($str, $i, strspn($str,"abcdef0123456789", $i)); $i+=strlen($chunk)+2;
        $chunk_size = hexdec($chunk);
        $body .= substr($str, $i, $chunk_size); $i+= $chunk_size+2;
    } while($chunk!=="0" && $chunk);
    return $body;
  }

  
  public static function ip_check_cidr ($IP, $CIDR) { 
    list ($net, $mask) = split ("/", $CIDR);
    if(!$mask) $mask = 32;
    $ip_net = ip2long ($net);
    $ip_mask = ~((1 << (32 - $mask)) - 1);
    $ip_ip = ip2long ($IP);
    $ip_ip_net = $ip_ip & $ip_mask;
    return ($ip_ip_net == $ip_net);
  }

  public static function ip_allow($ranges, $ip = false){
    if($ip === false) $ip = $_SERVER['REMOTE_ADDR'];
    $hostname = gethostbyname($ip);
    foreach($ranges as $range){
      //dummy check
      if($range == $hostname)
         return true;
      //if IP, check range
      if(preg_match("#^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(?:/[0-9]{1,2})?$#", $range)) {
        if(self::ip_check_cidr($ip, $range))
          return true;
      } else { //ip name, check with wildcard
        $mask = strtr($range, array("*" => ".*", "." =>  "\."));
        if(preg_match("#^$mask$#", $hostname))
          return true;
      }
    }
    return false;
  }

  public static function put_file($file_path, $http_remote){
    $gw = parse_url($http_remote);

    $schemes = array('http' => 80, 'https' => 443);
    if(!isset($schemes[$gw['scheme']]))
        throw new Exception("Unsupported scheme");
    $gw['port'] = $gw['port'] ? $gw['port'] : $schemes[$gw['scheme']];

    $is_ssl = $gw['scheme'] == 'https';
    $host = $gw['host'];
    if($is_ssl) $host = "ssl://$host";

    $dest = fsockopen($host, $gw['port']);
    $file_size = filesize($file_path);
    $file      = fopen($file_path, "r");


    $CRLF = "\r\n";
    $path = $gw['path']."?".$gw['query'];
    $query_head = "PUT $path HTTP/1.0".$CRLF
        ."Host: {$gw['host']}".$CRLF
        ."Content-Length: $file_size".$CRLF
         .$CRLF;

    echo $query_head;
    $res=  fwrite($dest, $query_head);
    
    //stream_copy_to_stream($file, $fp);

    stream_copy_to_stream($file, $dest);
    $res = stream_get_contents($dest);
    return $res;
  }



}
