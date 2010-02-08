<?php

class modules_manager {

  const module_fpi = "-//YKS//MODULE";

  public static function init(){
    xml::register_fpi(self::module_fpi, RSRCS_PATH."/dtds/module.dtd", "module");
  }

  public static function validate(exyks_module $module){

    $xml = $module->manifest_xml->asXML();


    try {
        xml::load_string($xml, LIBXML_MYKS, self::module_fpi);
    } catch(Exception $e){
        throw rbx::error("Invalid module {$module}");
    }


  }

}