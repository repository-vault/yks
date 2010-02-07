<?

class exyks_module {

  private $manifest_xml;
  private $manifest_file;
  private $key;
  private $ns;

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

    $module_root         = dirname($this->manifest_file); //or ...

    exyks_paths::register("here", $module_root, $this->ns);


    $this->register_classes();
  }

  function __get($key){
    if(method_exists($this, $getter = "get_$key"))
      return $this->$getter();
  }

  private function get_myks_paths(){
    $paths  = array();
    foreach($this->manifest_xml->myks->path as $path)
        $paths[] = exyks_paths::resolve($path['path'], $this->ns);

    return $paths;
  }


  private function register_classes(){
    foreach($this->manifest_xml->classes->class as $class) {
        $class_name = $class['name'];
        $class_path = exyks_paths::resolve($class['path'], $this->ns);
        classes::register_class_path($class_name, $class_path);
    }
  }

}