<?php

class js_package extends js_node {

  private $package_name;

  public function __construct($packager, $package_name){
    parent::__construct($packager);
    $this->package_name = $package_name;
    $this->packager->package_adopt($this);
  }


  function parse_xml($xml_contents){

        //deps parsing
    parent::parse_xml($xml_contents);

        //sub package parsing
    if($xml_contents->package)
    foreach($xml_contents->package as $package_xml){
        $package_key = (string) $package_xml['name'];
        $package_child = $this->packager->package_retrieve($package_key, true);
        $package_child->parse_xml($package_xml);
        $this->stack_dependency($package_child);
    }

        //modules definitions
    if($xml_contents->module)
    foreach($xml_contents->module as $module_xml){
        $module_key  = (string) $module_xml['key'];
        $head_only   = bool((string)$module_xml['head']);
        $module = $this->packager->module_retrieve($module_key, true);
        $module->parse_xml($module_xml);
        $this->stack_dependency($module, $head_only);
    }

    return $this;
  }



  function getKey(){
    return $this->package_name;
  }
}
