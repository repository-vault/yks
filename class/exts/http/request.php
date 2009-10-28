<?php

class request {
  protected $enctype;

  public $url;
  public $headers;
  public $method;
  public $data;

  static protected $encoding = array(
    "multipart/form-data" => "multipart_encode",
    "application/x-www-form-urlencoded" => "url_encode",
  );


  function __construct($url, $method, $data_raw = false, $enctype = false){
    $this->url = $url;
    $this->method = $method;
    $this->data = '';
    $this->headers = array();

    if($method == 'GET')
        return;


    if(!$enctype)
        $enctype = "application/x-www-form-urlencoded";

    $encoding = self::$encoding[$enctype];
    if(!$encoding)
        throw new Exception("Unknow data enctype");

    if($data_raw) $this->data = $this->$encoding($data_raw);
    $this->encoding = $encoding;
  }


  function addHeaders($data){
    $this->headers = array_merge($this->headers, $data);
  }

  function url_encode($data){
    $str = http_build_query($data, null, '&');
    $this->headers  = array(
        'Content-Type'  => 'application/x-www-form-urlencoded',
        'Content-Length' => strlen($str),
    );
    return $str;

  }


  function multipart_encode($data){
    $boundary = str_repeat('-',10)
                .substr(base64_encode(md5(rand())),0,10);
    $str = "";

    foreach($data as $name=>$value){
        $str .= "--$boundary".CRLF;
        $str .= "Content-Disposition: form-data; name=\"$name\"".CRLF;
        $str .= CRLF;
        $str .= $value.CRLF;
    }

    $str .= "--$boundary--".CRLF;

    $this->headers  = array(
        'Content-Type'  => "multipart/form-data; boundary=$boundary",
        'Content-Length' => strlen($str),
    );

    return $str;
  }




}