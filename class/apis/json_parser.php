<?
/** json parser (for Js input (not json strict compliant)
*  robust & simple enough for real world usages
*/
class json_parser {
  const pad = " \t\n\r";

  public static function json_to_xml($obj){
    $str = "";
    if(is_null($obj))
      return "<null/>";
    elseif(is_array($obj)) {
        //a list is a hash with 'simple' incremental keys
      $is_list = array_keys($obj) == array_keys(array_values($obj));
      if(!$is_list) {
        $str.= "<hash>";
        foreach($obj as $k=>$v)
          $str.="<item key=\"$k\">".self::json_to_xml($v)."</item>".CRLF;
        $str .= "</hash>";
      } else {
        $str.= "<list>";
        foreach($obj as $v)
          $str.="<item>".json_to_xml($v)."</item>".CRLF;
        $str .= "</list>";
      }
      return $str;
    } elseif(is_string($obj)) {
      return htmlspecialchars($obj) != $obj ? "<![CDATA[$obj]]>" : $obj;
    } elseif(is_scalar($obj))
      return $obj;
    else
      throw new Exception("Unsupported type $obj");
  }

  public static function parse($str){
    try {
      $i = 0;
      return self::walk($str, $i);
    } catch(Exception $e){
      return null;
    }
  }
  
  private static function walk($str, &$i){
    $i += strspn($str, self::pad, $i);
    if($str{$i} == '{')
      $value = self::parse_hash($str, $i);
    elseif($str{$i} == '[')
      $value = self::parse_list($str, $i);
    else $value = self::parse_simple($str, $i);

    $i += strspn($str, self::pad, $i);
    return $value;
  }
  

  private static function consume($str, $token, &$i){
    $i += strspn($str, self::pad, $i);
    if($str[$i] != $token) return false;
    $i++; //+=strlen($token)
    return true;
  }

  private static function parse_list($str, &$i){
    if(!self::consume($str, "[", $i))
      throw new Exception("Invalid list entry");

    $data = array();
    do {
      if(self::consume($str, "]", $i))
        break;
      $data[] = self::walk($str, $i);
    } while($str{$i++} == ',');

    if($str{$i-1} == ']')
      return $data;
    throw new Exception("Invalid list end");
  }

  private static function parse_hash($str, &$i){
    if(!self::consume($str, "{", $i))
      throw new Exception("Invalid hash entry");

    $data = array();
    do {
      if(self::consume($str, "}", $i))
        break;

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

    //$mask = '#"((?:\\\.|[^"\\\])*)"#i'; // http://stackoverflow.com/questions/2148587

    $mask = "#^\s*(?:(-?[0-9.]+)|([a-z_]+[0-9a-z_])|'([^\']*)'|\"((?:\\\.|[^\"\\\])*)\")\s*#i";
    if(!preg_match($mask, substr($str,$i), $out))
      throw new Exception("Invalid simple value at $i");

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

    return (string)stripslashes ($qv);
  }

}
