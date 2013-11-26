<?

abstract class js_node {
  protected $packager;
  protected $dependencies_list = array();
  protected $dependencies_head = array();  
  protected $patches_list      = array();

  protected function __construct($packager){
    $this->packager           = $packager;
  }

  abstract function getKey();


  function parse_xml($xml_contents){

    if(!$xml_contents->dep)
        return $this;

    foreach($xml_contents->dep as $dependency)
        $this->parse_dep($dependency);

    return $this;
  }

  private function parse_dep($dependency){
    $dependency_key  = (string) $dependency['key'];
    $head_only       = bool((string) $dependency['head']);
    $is_patch        = bool((string) $dependency['patch']);

    $dependency_type = starts_with($dependency_key, "path://") ? "module" : "package";

    if($dependency_type == "module") 
        $dependency = $this->packager->module_retrieve($dependency_key, true);

    if($dependency_type == "package") 
        $dependency = $this->packager->package_retrieve($dependency_key, true);

    if($is_patch)
        $dependency->patches_list[] = $this;
    else $this->add_dependency($dependency, $head_only, "top");
  }

  protected function stack_dependency($dependency, $head_only = false){
    return $this->add_dependency($dependency, $head_only);
  }

  private function add_dependency($dependency, $head_only = false, $way = "bottom"){
    $method = ( $way == "bottom" ? "array_push" : "array_unshift" );

    $method($this->dependencies_head, $dependency);

    if($head_only) ;
    else $method($this->dependencies_list, $dependency);
  }



  function __toString(){
    $str = "[".get_class($this)."]#".$this->getKey();
    $str.="(count(\$dependencies_list)==".count($this->dependencies_list).")";
    return $str;
  }



    //passed est un bon candidat mais pas forcement, il n'est qu'un controller de recursive loop
  private function get_dependencies_list_splat($scan_area = "dependencies_list", &$passed = array()){

    if(in_array($this, $passed, true))
            return array();
    $passed[] = $this;

    $dependencies_list = array();
    foreach($this->$scan_area as $dependency) {
        $dependencies_list = array_merge($dependencies_list,
                $dependency->get_dependencies_list_splat($scan_area, $passed));
        $dependencies_list[$dependency->getKey()] = $dependency;

    }

    return $dependencies_list;
  }



  function get_exposed_files(){
    return array();
  }
  function get_exposed_headers(){
    return array();
  }

  function get_files_list(&$context){ //final
    //static $loop = 0;
    //if(count($passed))
    //   die("PASSED".count($passed));

    //echo "Scanning deps in $this".CRLF;

    $dependencies_list = $this->get_dependencies_list_splat();
    if($context) //exclusion des fichiers dejas présents de part le contexte
        $dependencies_list = array_diff($dependencies_list, $context);

    $context = array_merge($context, $dependencies_list);


    $files_list = array();
    foreach($dependencies_list as $dependency)
        $files_list = array_merge($files_list, 
            $dependency->get_exposed_files() );

    $files_list = array_merge($files_list, $this->get_exposed_files());
    return $files_list;
  }

  function get_headers() {
    $headers = array();
    $dependencies_list  = $this->get_dependencies_list_splat("dependencies_head");
    $dependencies_fixed = $this->get_dependencies_list_splat();
    foreach($dependencies_list as $dependency)
        $headers = array_merge($headers,
            $dependency->get_exposed_headers($dependencies_fixed) );
    return $headers;
  }



}