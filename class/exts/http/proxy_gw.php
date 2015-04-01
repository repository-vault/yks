<?php


class http_proxy {

  static private $FORWARD_HEADERS = array(
    'in' => array(
        'USER_AGENT',
        'ACCEPT',
        'ACCEPT_LANGUAGE',
        'ACCEPT_CHARSET',
        'CONTENT_TYPE',
    ),
    'out' => array(
        'Content-Type',
        'Content-Length',
        'ETag',
        'Last-Modified',
        'Date',
    ),
  );


  private static function read_stdin(){
    $fp     = fopen("php://input", "r");
    $str    = stream_get_contents($fp);
    fclose($fp);
    return $str;
  }

  static function request($SERVER_NAME, $url, $_SERVER, $_COOKIES, $input = null) {
    global $http_response_header;

    $headers   = array();

    $headers['Host'] = $SERVER_NAME;
    $METHOD  = $_SERVER['REQUEST_METHOD'];
    $is_post = ($METHOD == 'POST');


    $query_contents = '';

    foreach(self::$FORWARD_HEADERS['in'] as $header) {
      $_server_header = sprintf("HTTP_%s", $header);  
      if(isset($_SERVER[$_server_header]))
        $headers[self::CamelCase($header)] = $_SERVER[$_server_header];
    }

    if($_COOKIES)
        $headers['Cookie'] = http_build_query($_COOKIES, '', ';');
    
    if($is_post) {
        $in_data = is_null($input) ?  self::read_stdin() : $input;
        $query_contents            = $in_data;
        $headers["Content-Length"] = strlen($in_data);
        $headers["Content-Type"]   = "application/x-www-form-urlencoded";
    }


    $lnk = new sock($SERVER_NAME);
    $lnk->request($url, $METHOD, $query_contents, $headers);

    $response_headers  = $lnk->response['headers'];
    $response_contents = $lnk->receive();

    foreach($response_headers as $headers_list){
        if(!is_array($headers_list)) $headers_list = array($headers_list);
        foreach($headers_list as $header) {
            if(!in_array($header->name, self::$FORWARD_HEADERS['out'])) continue;
            $header_str = $header->full;
            header($header_str);
        }
    }

    return array($response_headers, $response_contents);
  }



  private static function getipbyhostname($hostname){
    $infos = first(dns_get_record ($hostname, DNS_A));
    return $infos['ip'];
  }

    //used in Http header's name resolutions
  private static function CamelCase($str){
    $str = strtolower($str);
    $str = strtr($str, "_", "-");
    $str = preg_replace("#((?:^|-)[a-z])#e", 'strtoupper("$1")', $str);
    return $str;
  }

}
