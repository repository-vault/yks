<?
/** json parser (for Js input (not json strict compliant)
*  robust & simple enough for real world usages
*/
class json_parser {
  const pad = " \t\n\r";
  public static function parse($str){
    try {
      return self::walk($str);
    } catch(Exception $e){
      return null;
    }
  }
  
  private static function walk($str, &$i = 0){
    $i += strspn($str, self::pad, $i);

    if($str{$i} == '{')
      $value = self::parse_hash($str, $i);
    elseif($str{$i} == '[')
      $value = self::parse_list($str, $i);
    else $value = self::parse_simple($str, $i);

    $i += strspn($str, self::pad, $i);
    return $value;
  }
  
  private static function parse_list($str, &$i){
    if($str{$i++} != '[')
      throw new Exception("Invalid list entry");
    $data = array();
    do {
      $data[] = self::walk($str, $i);
    } while($str{$i++} == ',');

    if($str{$i-1} == ']')
      return $data;
    throw new Exception("Invalid list end");
  }

  private static function parse_hash($str, &$i){
    if($str{$i++} != '{')
      throw new Exception("Invalid hash entry");
    $data = array();
    do {
      $key   = self::parse_simple($str, $i);
      if($str{$i++} != ':')
        throw new Exception("Invalid hash key");
      $data[$key] = self::walk($str, $i);
    } while($str{$i++} == ',');
    if($str{$i-1} == '}')
      return $data;
    throw new Exception("Invalid hash end");
  }
  
  private static function parse_simple($str, &$i ){

    $mask = "#^\s*(?:([0-9]+)|([a-z_]+[0-9a-z_])|'([^\']*)'|\"([^\"]*)\")\s*#i";
    if(!preg_match($mask, substr($str,$i), $out))
      throw new Exception("Invalid simple value ...");

    $i += strlen($out[0]);
    list($dv, $sv, $qv) = array($out[1], $out[2], pick($out[3], $out[4]));
    
    if($dv !== '')
      return (int) $dv;

    if($sv !== '') {
      if($sv === "true") return true;
      elseif($sv === "false") return false;
      elseif($sv === "null") return null;
      return $sv;
    }

    return (string)$qv;
  }

}
