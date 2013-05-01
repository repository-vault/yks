<?

class php {

  function file_get_php_classes($file_path) {
    if(!is_file($file_path))
        return array();
    $php_code = file_get_contents($file_path);
    return self::get_php_classes($php_code);
  }

  private static function get_php_classes($php_code) {
    $classes = array();
    $tokens = token_get_all($php_code);
    $count = count($tokens);
    for ($i = 2; $i < $count; $i++) {
      if (   $tokens[$i - 2][0] == T_CLASS
          && $tokens[$i - 1][0] == T_WHITESPACE
          && $tokens[$i][0] == T_STRING) {
        $class_name = $tokens[$i][1];
        $classes[] = $class_name;
      }
    }

    return $classes;
  }

}