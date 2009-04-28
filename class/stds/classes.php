<?


class classes {
  static $classes_paths=array();

  static function extend_include_path($paths) {
    $paths =  is_array($paths)?$paths:func_get_args();
    array_unshift($paths, get_include_path());
    set_include_path( join(PATH_SEPARATOR, $paths) );
    return get_include_path();
  }

  static function register_class_path($class_name, $file_path){
    self::$classes_paths[$class_name] = $file_path;
  }
  static function register_class_paths($paths){
    self::$classes_paths = array_merge(self::$classes_paths, $paths);
  }

  static function autoload($class_name){
    $class_name = strtolower($class_name);
    if(isset(self::$classes_paths[$class_name]))
         $file = self::$classes_paths[$class_name];
    else if(strpos($class_name, "_") )
        $file = strtr($class_name, array('_'=>'/') ).".php";
    else return false; //leave it to the spl_autoload(); , yeap ?
    include $file;
    if(!class_exists($class_name))
        die("Unable to load $class_name (in $file)");    
  }

  static function activate($exts = false){
    if($exts) spl_autoload_extensions($exts);
    spl_autoload_register();
    spl_autoload_register(array(__CLASS__,"autoload"));
  }

}



