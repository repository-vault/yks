<?php
class js_module extends js_node {

  private $module_key;
  private $module_description;
  private $module_class;
  private $module_matches;

  public function __construct($packager, $module_key){
    parent::__construct($packager);
    $this->module_key  = $module_key;
    $this->packager->module_adopt($this);

  }

  function getKey(){
    return $this->module_key;
  }

  function parse_xml($xml_contents){
        //deps parsing
    parent::parse_xml($xml_contents);

    if($xml_contents->description)
        $this->module_description = (string)$xml_contents->description;

    if($xml_contents['expose'])
        $this->module_class       = trim((string)$xml_contents['expose']);

    if($xml_contents['matches'])
        $this->module_matches     = trim((string)$xml_contents['matches']);

    return $this;
  }

  function get_exposed_headers(){
    if(!$this->module_class)
        return array();
    $data = array(
        'class' => $this->module_class,
    );
    if($this->module_matches)
        $data['match'] = $this->module_matches;
    return array($this->module_key => $data);
  }

  function get_exposed_files(){
    $file_path = $this->module_key;
    $files = array($file_path);
    if($this->patches_list)
    foreach($this->patches_list as $patch)
        $files = array_merge($files, $patch->get_exposed_files());
    return $files;
  }

}