<?

class js_packager {

  private $manifests;
  private $nodes_list = array(
        'module'  => array(),
        'package' => array(),
  );

  function __construct() {

  }

  function manifest_register($file_path){

    $xml = simplexml_load_file($file_path);
    foreach($xml->package as $package_xml) {
        $package_key = (string)$package_xml['name'];
        $package = $this->package_retrieve($package_key, true);
        $package->parse_xml($package_xml);
    }

  }

  private function node_adopt($node_type, $node) {
    $this->nodes_list[$node_type][$node->getKey()] = $node;
  }

  private function node_exists($node_type, $node_key) {
    return isset($this->nodes_list[$node_type][$node_key]);
  }

  private function node_retrieve($node_key, $node_type = false, $instanciate = false){

    if($node_type==false) {
        if($this->node_exists("module", $node_key))
            return $this->nodes_list["module"][$node_key];
        if($this->node_exists("package", $node_key))
            return $this->nodes_list["package"][$node_key];
        throw new Exception("Unresolve node '$node_key'");
    }

    if($this->node_exists($node_type, $node_key))
        return $this->nodes_list[$node_type][$node_key];
    
    if(!$instanciate)
        throw new Exception("Unresolve node '$node_key'");

    if($node_type == "module")
        return new js_module($this, $node_key);
    if($node_type == "package")
        return new js_package($this, $node_key);

    die("Instanciate $node_key");
  }




  function package_adopt(js_package $package){ return $this->node_adopt("package", $package);}
  function module_adopt(js_module $module){ return $this->node_adopt("module", $module); }
  function package_exists($package_key){ return $this->node_exists("package", $package_key); }
  function module_exists($module_key){ return $this->node_exists("module", $module_key); }

  function module_retrieve($module_key, $instanciate = false){
    return $this->node_retrieve($module_key, "module", $instanciate);
  }
  function package_retrieve($package_key, $instanciate = false){
    return $this->node_retrieve($package_key, "package", $instanciate);
  }


  function output_headers($node_key){
    $node = $this->node_retrieve($node_key);
    return $node->get_headers();
  }


  function exclude_base($node_key){
    $node = $this->node_retrieve($node_key);
  }


  private $current_context = array();
  function output_node($node_key){
    $node = $this->node_retrieve($node_key);

    return $node->get_files_list($this->current_context);
  }

}

