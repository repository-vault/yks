<?php

class exyks_ws {

  private static $classes = array();

  static public function init(){
    exyks::init();

    foreach(yks::$get->config->wsdls->iterate("class") as $class) {
        $class_name = $class['name']; $aliases = array();
        $data = compact('class_name', 'aliases');
        foreach($class->iterate("alias") as $alias)
            $data['aliases'][] = $alias['name'];
        self::$classes[$class_name] = $data;
    }
  }



  // return tupple list($class_name, $wsdl_file)
  public static function resolve($class){

    if(isset(self::$classes[$class]))
        $class_name = $class;
    else foreach(self::$classes as $_class_name=>$class_infos)
      if(in_array($class, $class_infos['aliases'])) {
        $class_name = $_class_name;
        break;
    }
    
    if(!isset(self::$classes[$class_name])) {
        if($_SERVER['HTTP_SOAPACTION'])
            throw new SoapFault("server", "No valid class selected");
        header(TYPE_TEXT);
        die("No valid class selected");
    }

    $wsdls_path = ROOT_PATH."/wsdls/".FLAG_DOMAIN;
    $wsdl_file = "$wsdls_path/$class_name.wsdl";
    return array($class_name, $wsdl_file);
  }

  public static function serve() {
    header(TYPE_XML);
    set_time_limit(90);

    rbx::$output_mode = 0;

    list($class_name, $wsdl_file) = self::resolve($_GET['class']);

    if($_SERVER['REQUEST_METHOD']=='GET') {
        readfile($wsdl_file);
        die;
    }

    if(DEBUG) ini_set('soap.wsdl_cache_enabled ', 1);
    $options = array('actor' => SITE_CODE, 'classmap' =>array());
    $server = new SoapServer($wsdl_file, $options);
    $server->setClass($class_name);
    $server->setPersistence(SOAP_PERSISTENCE_REQUEST);
      use_soap_error_handler(true);
    $server->handle();
  }

}
