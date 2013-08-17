<?php

class mime_types {
  private static $mime_path;
  private static $mime_types = null;
  static function init(){
    self::$mime_path = $_SERVER['mime_types_path'];
    if(!self::$mime_path) self::$mime_path = yks::$get->config->paths->mime['path'];
    if(!self::$mime_path) self::$mime_path = is_file("/etc/mime.types") ? "/etc/mime.types" : "/etc/httpd/conf/mime.types";
    if(!is_file(self::$mime_path))
        throw new Exception("mime_type, invalid configuration, chk env mime_types_path");
  }

  private static function build_cache(){
    self::$mime_types = array();
    foreach(file(self::$mime_path) as $line) {
        $c = strpos($line, "#");
        if($c!==false) $line = substr($line, 0, $c);
        $line = trim($line);
        if(!$line)continue;
        $line = preg_split("#\s+#", $line);
        if(count($line)==1)continue;
        $content_type = array_shift($line);
        foreach($line as $ext)
            self::$mime_types[$ext] = $content_type;
    }
  }

  static function get_ext($content_type){
    if(is_null(self::$mime_types))
      self::build_cache();
    return array_search($content_type, self::$mime_types);
  }

  static function get_content_type($ext){
    if(!is_null(self::$mime_types))
        return self::$mime_types[$ext];

    self::build_cache();
    return self::$mime_types[$ext];
  }
}
