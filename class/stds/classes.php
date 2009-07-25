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

  static function register_class_paths($paths, $load = false){
    self::$classes_paths = array_merge(self::$classes_paths, $paths);
    if($load) array_walk(array_keys($paths), array(__CLASS__, 'autoload'));
  }

  static function autoload($class_name){
    $class_name = strtolower($class_name);
    if(isset(self::$classes_paths[$class_name]))
         $file = self::$classes_paths[$class_name];
    else if(strpos($class_name, "_") )
        $file = strtr($class_name, array('_'=>'/') ).".php";
    else return ; //leave it to the spl_autoload(); , yeap ?
    include $file;
    self::init($class_name);
  }

  static function activate($exts = false){
    if($exts) spl_autoload_extensions($exts);

    spl_autoload_register(array(__CLASS__, "autoload"));
    spl_autoload_register(array(__CLASS__, "spl_autoload")); 

  }

  private static function init($class_name){
    if(!class_exists($class_name))
        throw new Exception("Unable to load $class_name");
    if(method_exists($class_name, "init"))
        call_user_func(array($class_name, 'init'));
  }

  static function spl_autoload($class_name){
    spl_autoload($class_name);
    self::init($class_name);

  }
}



