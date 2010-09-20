<?php


class classes {
  static $classes_paths   = array();
  static $classes_aliases = array();

  private static $_call_init      = false;
  private static $inited_classes  = array();

  static function extend_include_path($paths) {
    $new_paths      = is_array($paths)?$paths:func_get_args();
    $current_paths  = explode(PATH_SEPARATOR, get_include_path());
    $paths          = array_merge($current_paths, $new_paths);
    $paths          = array_filter(array_unique($paths));
    set_include_path( join(PATH_SEPARATOR, $paths) );
    return get_include_path();
  }

  static function register_class_path($class_name, $file_path){
    self::$classes_paths[$class_name] = $file_path;
  }

  static function init_need($class_name){
    if(isset(self::$inited_classes[$class_name]))
        return false;
    self::$inited_classes[$class_name] = true;
    return true;
  }

  static function register_alias($alias_name, $from_class){
    self::$classes_aliases[$alias_name] = $from_class;
  }

  static function register_aliases($aliases){
    self::$classes_aliases = array_merge(self::$classes_aliases, $classes_aliases);
  }

  static function alias($class_newname, $class_oldname){
    eval("class $class_newname extends $class_oldname {}");
  }

  static function call_init($status){
    self::$_call_init = (bool) $status;
  }

  static function register_class_paths($paths, $load = false){
    self::$classes_paths = array_merge(self::$classes_paths, $paths);
    if($load) array_walk(array_keys($paths), array(__CLASS__, 'autoload'));
  }

  static function autoload($class_name){
    if(!$class_name) return false;

    $class_name = strtolower($class_name);

    if(isset(self::$classes_paths[$class_name])) {
        $file = self::$classes_paths[$class_name]; //direct path
        include $file;
    } elseif(isset(self::$classes_aliases[$class_name])){
        self::alias($class_name, self::$classes_aliases[$class_name]);
    } elseif(strpos($class_name, "_") ) {
        $file = strtr($class_name, array('_'=>'/') ).".php"; //include_path
        include $file;
    } else return ; //leave it to the spl_autoload(); , yeap ?

    self::init($class_name);
  }

  static function activate($exts = false){
    if($exts) spl_autoload_extensions($exts);

    spl_autoload_register(array(__CLASS__, "autoload"));
    spl_autoload_register(array(__CLASS__, "spl_autoload")); 

  }

  private static function init($class_name){
    if(!$class_name) return false;

    if(!class_exists($class_name))
        throw new Exception("Unable to load $class_name");

    if(!self::$_call_init)
        return;

    if(isset(self::$inited_classes[$class_name])) 
        return;

    if(method_exists($class_name, "init"))
        call_user_func(array($class_name, 'init'));
  }

  static function spl_autoload($class_name){
    spl_autoload($class_name);

    if(!class_exists($class_name))
        return false; //class_exists

    self::init($class_name);

  }
}



