<?

class css_parser {

  const pad = " \t\n\r";
  const STRING = "(?:\"([^\"]*)\"|'([^']*)')";
  const URI    = "url\(\s*(?:\"([^\"]*)\"|'([^']*)\'|([^)]*))\s*\)";
  const COMMENTS = "/\*.*?\*/";
  const KEYWORD = "([!]?[a-z-]+)";

  public static function parse($str){
    try {
      $i = 0;
      $str = self::strip_comments($str);
      return self::parse_block($str, $i);
    } catch(Exception $e){
      rbx::error($e->getMessage());
      return null;
    }
  }

    //comments are semanticaly equals to whitespaces
  private static function strip_comments($str){
    $mask = "#".self::COMMENTS."#s";
    $str = preg_replace($mask, " ", $str);
    return $str;
  }


  private static function parse_block($str, &$i){

    $i += strspn($str, self::pad, $i); $start = $i;
    $embraced = $str{$i} == "{";
    $block = new css_block($embraced);
    if($embraced) $i++;

    do {
        $statement = self::walk($str, $i);
        if(!$statement)
            break;
        $block->stack_statement($statement);
    } while($str{$i+1}!="}"  && $str{$i}!="" );

    if($embraced && $str{$i++}!="}")
        throw new Exception("Invalid block end start at $start ".substr($str, $i-2));

    return $block;
  }


  private static function parse_declarations($str, &$i){

    if($str{$i++} != "{")
        throw new Exception("Invalid declarations block".substr($str, $i));

    $block = new css_declarations_block();
    do {
        $declaration = self::parse_declaration($str, $i);
        if(!$declaration)
            break;
        $block->stack_declaration($declaration);
    } while($str{$i}!="}" && $str{$i}!="");

    if($str{$i}=="}")
        $i++;

    $i += strspn($str, self::pad, $i);
    return $block;

  }

  private static function parse_declaration($str, &$i){

    $i += strspn($str, self::pad, $i);
    $mask = "#^([^:]*?):#";
    if(!preg_match($mask, substr($str, $i), $out))
        throw new Exception("Invalid property declaration ".substr($str,$i));

    list(, $property_name) = $out; $i+= strlen($out[0]);
    $declaration = new css_declaration($property_name);

    do {
        $value = self::parse_value($str, $i);
        if(is_null($value))
            break;
        ///die("THIS IS $value");

        if($str{$i}==',') {
            $i++; $declaration->set_alternative(); //$declaration->something();     
        }

        $declaration->stack_value($value);       
    } while($str{$i}!=';' && $str{$i}!='}' && $str{$i}!="");

    $i += strspn($str, self::pad.';', $i);
    
    return $declaration;
  }

  private static function parse_value($str, &$i){

    $i += strspn($str, self::pad, $i);
    $all = array(
        self::STRING,                //string
        "(\#[0-9A-F]+)",              //hexacolor
        "(-?[0-9.]+)(%|[a-z]{2,3})",  //unit value
        "(-?[0-9.]+)",                //simple number
        self::URI,                   //URI
        self::KEYWORD,                 //keyword
    ); $mask = "#^(?:".join('|', $all).")#i";

    if(!preg_match($mask, substr($str, $i), $out))
        throw new Exception("Invalid $mask property value=".substr($str, $i));
    $value = $out[0]; //until more is needed
    $i += strlen($value);
    $i += strspn($str, self::pad, $i);

    //rbx::ok("parsevalue $value");
    return $value;
  }
  
  private static function walk($str, &$i){
    $i += strspn($str, self::pad, $i);
    $step = $str{$i};

    //rbx::ok("Walk on step $step");
    $value = null;
    if($step == '}')
        return $value;
    elseif($step == '@')
      $value = self::parse_at($str, $i);
    elseif($step == '{')
      $value = self::parse_block($str, $i);
    elseif($step != "")
      $value = self::parse_ruleset($str, $i);

    $i += strspn($str, self::pad, $i);
    return $value;
  }


  private static function parse_ruleset($str, &$i){
    //rbx::ok("parserulset");
    //$selector = self::parse_selector($str, $i);
 
    $mask = "#^(.*?)\s*\{#";
    if(!preg_match($mask, substr($str,$i), $out))
        throw new Exception("Invalid ruleset" . substr($str, $i));

    list(, $selector) = $out;
    $i+=strlen($out[0])-1;
    $block = self::parse_declarations($str, $i);

    return new css_ruleset($selector, $block);
  }


  private static function parse_string($str, &$i){
    //rbx::ok("parsestring");
    $i += strspn($str, self::pad, $i);

    //([!#$%&*-~]|{nonascii}|{escape})*{w} unspecaped
        //{string1}|{string2}
    $mask = "#(?:".self::STRING.'|'.self::KEYWORD.")#";
    if(!preg_match($mask, substr($str,$i), $out))
        throw new Exception("Invalid string $mask");

    $i += strlen($out[0]);
    return pick($out[1], $out[2], $out[3]);
  }


  private static function parse_at($str, &$i){
    //rbx::ok("parseat");

    if($str{$i++} != '@')
      throw new Exception("Invalid at rule entry");

    $rule_keyword = self::parse_string($str, $i);
    $rule = new at_rule($rule_keyword);

    do {
        $value = self::parse_value($str, $i);
        if(!$value)
            break;
        //rbx::ok("THIS IS $value --".$str{$i+1}."--");
        ///die("THIS IS $value");
        $rule->stack_expression($value);
    } while($str{$i}!=';' && $str{$i}!='{' && $str{$i}!="");

        //inline rule
    if($str{$i} == ";") {
        $i += strspn($str, self::pad.';', $i);
        return $rule;
    }

    $block = self::parse_block($str, $i);
    $rule->set_block($block);
    
    return $rule;
    
  }
}