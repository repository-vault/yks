<?php

class exyks_module {

  private $manifest_xml;
  private $manifest_file;
  private $key;
  private $ns;
  private $module_root;
  private $module_rq_start;  //request start for the module
  private $module_sg_start;  //module suggested start

  function __construct($module_xml){

    $this->key = $module_xml['key'];
    $this->ns  = "module/$this->key"; //or ...

    $tmp = $module_xml['manifest'];

    $manifest_dest  = exyks_paths::resolve($tmp);
    if(is_file($manifest_dest))
        $manifest_file = $manifest_dest;
    elseif(is_file($manifest_file = "$manifest_dest/manifest.xml"));
    elseif(is_file($manifest_file = "$manifest_dest/{$this->key}.xml"));
    else throw new Exception("Unresolved module");


    $this->manifest_file = $manifest_file;

    $this->manifest_xml  = ksimplexml::load_file($this->manifest_file);

    $this->module_sg_start = $this->manifest_xml['start'];
    $this->module_rq_start = pick($module_xml['start'], $this->module_sg_start);

    $this->module_root   = dirname($this->manifest_file); //or ...

        //two way
    exyks_paths::register("here", $this->module_root, $this->ns);
    exyks_paths::register($this->key, $this->module_root);


    $this->register_classes();
    $this->extend_include_path();
 
  }

  private function get_virtual_paths(){
    $paths = array();
    if($this->module_sg_start)
        $paths[$this->module_rq_start] = array($this->module_root, $this->module_sg_start);

    foreach($this->manifest_xml->paths->iterate("path") as $path) {
        $path_key = $path['symbolic'];

        $dest     = $path['base']?$path['base']."/$path_key":$path['dest'];
        if(!$dest) $dest = $path_key;
        $paths[$path_key] = array($this->module_root, $dest);
    }
    return $paths;
  }

  function __get($key){
    if(method_exists($this, $getter = "get_$key"))
      return $this->$getter();
  }


  public function __toString(){
    return "<module #{$this->key}/> ";
  }

  private function get_manifest_xml(){
    return $this->manifest_xml;
  }

  private function get_myks_paths(){
    $paths  = array();
    foreach($this->manifest_xml->myks->path as $path)
        $paths[] = exyks_paths::resolve($path['path'], $this->ns);

    return $paths;
  }

  private function extend_include_path(){
    foreach($this->manifest_xml->include_path->iterate("path") as $path){
        $path = exyks_paths::resolve($path['path'], $this->ns);
        classes::extend_include_path($path);
    }
  }

  private function register_classes(){
    foreach($this->manifest_xml->classes->iterate("class") as $class) {
        $class_name = $class['name'];
        $class_path = exyks_paths::resolve($class['path'], $this->ns);
        classes::register_class_path($class_name, $class_path);
    }
  }

}