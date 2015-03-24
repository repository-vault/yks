<?php

class exyks_ws {

  protected static $classes = array();

  static public function init(){
    if(!$_SERVER['REQUEST_TIME_FLOAT'])
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

    exyks::init();

    $wsdls = yks::$get->config->wsdls;

    $default_use_sess = (string) $wsdls['use_sess'];

    foreach($wsdls->iterate("class") as $class) {
        $class_name = $class['name']; $aliases = array();
        $use_sess = isset($class['use_sess']) && bool($class['use_sess']) ? (string) $class['use_sess'] : $default_use_sess;
        $wsdl_ns  = pick($class['ns'], SITE_CODE);

        $restricted_ip = ((string)$class['restricted'] == "ip");
        $wsdl_ips = null;
        if($restricted_ip) {
            $wsdl_ips= array();
            foreach($class->allow as $allow) {
              $wsdl_ips[]= (string)$allow;
            }
        }

        $host = null;
        $args_replace = null;
        $data = compact('class_name', 'aliases', 'use_sess', 'wsdl_ns', 'wsdl_ips', 'host', 'args_replace');
        foreach($class->iterate("alias") as $alias) {
          $data['aliases'][] = $alias['name'];
        }

        self::$classes[$class_name] = $data;
    }
  }

  // return tupple list($class_name, $wsdl_file)
  public static function resolve($class) {
    if(isset(self::$classes[$class]))
        $class_name = $class;
    else foreach(self::$classes as $_class_name=>$class_infos) {
      if(in_array($class, $class_infos['aliases'])) {
        $class_name = $_class_name;
        break;
      }
    }

    $wsdl_infos = self::$classes[$class_name];
    if(!isset($wsdl_infos)) {
        if($_SERVER['HTTP_SOAPACTION'])
            throw new SoapFault("server", "No valid class '$class_name','{$_SERVER['HTTP_SOAPACTION']} selected");
        header(TYPE_TEXT);
        die("No valid class selected");
    }

    if($wsdl_infos['host']){
      //Distant wsdl
      $wsdl_file = sprintf('%s/services/?class=%s&wsdl', $wsdl_infos['host'], $class_name);
    }else{
      //Local wsdl
      $wsdls_path = ROOT_PATH."/wsdls/".FLAG_DOMAIN;
      $wsdl_file = "$wsdls_path/$class_name.wsdl";
    }

    return array($class_name, $wsdl_file, $wsdl_infos['use_sess'], $wsdl_infos['wsdl_ns'], $wsdl_infos['wsdl_ips'], $wsdl_infos['host']);
  }

  public static function extract_headers($input = null){
    if(is_null($input))
      $input = file_get_contents("php://input", "r");
    $soap_request = simplexml_load_string($input, NULL, LIBXML_ERR_NONE);
    if($soap_request === false)
      return array();

    $soap_request->registerXPathNamespace("envelope", "http://schemas.xmlsoap.org/soap/envelope/");
    $out = array();
    $headers = $soap_request->xpath("/envelope:Envelope/envelope:Header/*");
    if($headers) foreach($headers as $header)
      $out[(string)$header->getName()] = (string)$header;
    return $out;
  }

  public static function serve() {

    set_time_limit(90);

    rbx::$output_mode = 0;

    list($class_name, $wsdl_file, $use_sess, $wsdl_ns, $wsdl_ips, $wsdl_host) = self::resolve($_GET['class']);

    $access = is_null($wsdl_ips) ? true : http::ip_allow($wsdl_ips, exyks::$CLIENT_ADDR);

    if($_SERVER['REQUEST_METHOD']=='OPTIONS')
      die;

     if($_SERVER['REQUEST_METHOD']=='GET' && isset($_GET['post_args'])) {
       $POST = json_decode($_GET['post_args'], true);
       if(!is_null($POST)) {
         $_POST = $POST; 
         $_SERVER['REQUEST_METHOD']= 'POST';
       }
     }


    if($_SERVER['REQUEST_METHOD']=='GET') {
        if(!$access) {
            header("HTTP/1.0 403 access denied");
            die("Access denied");
        }
        header(TYPE_XML);
        readfile($wsdl_file);
        die;
    }

    if($_GET['mode'] == 'rest') {

      $method  = $_GET['method'];
      $class = new $class_name();
      $query = $_POST ? $_POST : json_decode(stream_get_contents(fopen("php://input", "r")), true);

      $params = php::get_method_params($class, $method);

      $data = array_column($params, 'default');
      $data = array_sort(array_merge($data, $query), array_keys($data));

      try {
         $res = call_user_func_array(array($class, $method ), $data);
      } catch(SoapFault $f){
          header("HTTP/1.0 400 error");
          $message = json_encode(array('message' => $f->getMessage(), 'code' => $f->faultcode ));
          die($message);
      } catch(Exception $e){
         header("HTTP/1.0 500 error");
         error_log($e->getMessage());
         die;
      }
      die($res);
    }

    //autodetect if current argument is session_id, init session if so
    $SOAP_SESSION_ID = null;

    if($use_sess === "auto") {
        $url_infos = parse_url(trim($_SERVER['HTTP_SOAPACTION'],'"'));
        parse_str($url_infos['query'], $soap_action);

        //$class_name = pick($soap_action['class'], $_GET['class'], $_POST['class']);
        $method     = pick($soap_action['method']);
        $query = stream_get_contents(fopen("php://input", "r"));

        //file_put_contents(TMP_PATH."/query", $query);
        $xml = simplexml_load_string($query);
        $xml->registerXPathNamespace("me", $wsdl_ns);
        $xml->registerXPathNamespace("env", "http://schemas.xmlsoap.org/soap/envelope/");
        $SOAP_SESSION_ID = (string) first($xml->xpath("//env:Body/me:{$method}/*[1][name()='session_id']"));

        if(!$SOAP_SESSION_ID) //! leave it to sess::renew()
          $use_sess = false;
    }

    define('SOAP_SESSION_ID', $SOAP_SESSION_ID);

    if($use_sess)
        sess::connect(SOAP_SESSION_ID);

    $options = array(
      'actor'      => SITE_CODE,
      'classmap'   => array(),
      'cache_wsdl' => WSDL_CACHE_NONE,
    );

    header(TYPE_XML);
    $server = new SoapServer($wsdl_file, $options);
    $server->setClass($class_name);
    $server->setPersistence(SOAP_PERSISTENCE_REQUEST);

    // We don't want the default handler, unless we are in debug mode.
    use_soap_error_handler(true);

    if(!$access) {
      $server->fault("403", "Access denied");
    } else {
      try {
        $server->handle();
      } catch(Exception $e) {
        // Catch error and produce an anonymous message.
        error_log($e);
        $server->fault('generic', 'An unexpected error occured.');
      }
    }
  }
}
