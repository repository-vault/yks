<?php

class UnresolvedModule extends Exception {}
class UnresolvedOptionalModule extends UnresolvedModule {}

class exyks_module {

  private $manifest_xml;
  private $manifest_file;
  private $key;
  private $ns;
  private $module_root;
  public  $config;

  private $module_rq_start;  //request start for the module
  private $module_base;

  function __construct($module_xml){
    $this->config = null;

    if(is_a($module_xml, 'config') && $module_xml->search("config") )
      $this->config = $module_xml->config;

    $this->key = $module_xml['key'];
    $this->ns  = "module/$this->key"; //or ...

    $tmp = $module_xml['manifest'];

    $manifest_dest  = exyks_paths::resolve($tmp);
    if(is_file($manifest_dest))
        $manifest_file = $manifest_dest;
    elseif(is_file($manifest_file = "$manifest_dest/manifest.xml"));
    elseif(is_file($manifest_file = "$manifest_dest/{$this->key}.xml"));
    else{
      $class = (bool($module_xml['optional'])) ? UnresolvedOptionalModule : UnresolvedModule;
      throw new $class("Unresolved module #{$this->key} in $manifest_dest");
    }

    $this->manifest_file = $manifest_file;

    $this->manifest_xml  = ksimplexml::load_file($this->manifest_file);

    $this->module_base     = $this->manifest_xml['base'];

    $this->module_rq_start = null;
    if(isset($module_xml['start'])) {
      $this->module_rq_start = $module_xml['start'];
    }

    if(isset($this->manifest_xml['start'])) {
      $this->module_rq_start = pick($this->module_rq_start, $this->manifest_xml['start']);
    }

    $this->module_root   = dirname($this->manifest_file); //or ...

        //two way
    exyks_paths::register("here", $this->module_root, $this->ns);
    exyks_paths::register($this->key, $this->module_root);

    foreach($this->manifest_xml->paths->iterate("ns") as $domain) {
        $path   = exyks_paths::resolve($domain['path'], $this->ns);
        $public = $domain['public']=='public';
        exyks_paths::register($domain['name'], $path , exyks_paths::default_ns, $public );
    }

    $this->process_classes();

  }

  private function get_virtual_paths(){
    $paths = array();
    if($this->module_base && $this->module_rq_start)
        $paths[$this->module_rq_start] = array($this->module_root, $this->module_base);

    foreach($this->manifest_xml->paths->iterate("path") as $path) {
        $path_key = $path['virtual'];

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

  private function get_key(){
    return $this->key;
  }

  public function get_manifest_xml(){
    return $this->manifest_xml;
  }

  public function get_root(){
    return $this->module_root;
  }

  private function paths($from){
    $paths = array();
    foreach($from as $path)
        $paths[] = exyks_paths::resolve($path['path'], $this->ns);
    return $paths;
  }

  private function get_myks_paths(){
    return $this->paths($this->manifest_xml->myks->iterate("path"));
  }

  private function get_locale_paths(){
    return $this->paths($this->manifest_xml->locales->iterate("path"));
  }

  private function process_classes(){
    $classes  = $this->manifest_xml->classes;
    foreach($classes->iterate("class") as $class) {
        $class_name = $class['name'];
        $class_path = exyks_paths::resolve($class['path'], $this->ns);
        classes::register_class_path($class_name, $class_path);
    }

    $paths = $this->paths($classes->iterate("include_path"));
    classes::extend_include_path($paths);
  }

}
