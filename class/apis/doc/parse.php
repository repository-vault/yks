<?

class doc_parser {



  static function parse($str){
    if(!$str) return false;

    $res = array();

      //remove /** *,*/
    $str = preg_replace("#^/?\*+\s*(/$)?#m", '', $str);
      //unix style LF
    $str = preg_replace("#\r?\n#", "\n", $str);

    $lines = explode(LF, $str);

    $args = array();
    foreach($lines as $line){
      if($arg = self::arg($line)) {
        $args[$arg[0]]['computed'] = $arg[1];
        $args[$arg[0]]['values'][] = $arg[1];
      }

    }
    return array('args'=>$args);

  }

  static function arg($str){
    if(!preg_match("#^@([a-z_][0-9a-z_-]+)(.*?)$#", $str, $out))
      return false;
    $args = preg_split("#\s+#", trim($out[2]));

    return array($out[1], $args);
  }



}