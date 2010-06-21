<?

class css_parser {

  const pad = " \t\n\r";
  const STRING = "(?:\"([^\"]*)\"|'([^']*)')";
  const URI    = "url\(\s*(?:\"([^\"]*)\"|'([^']*)\'|([^)]*))\s*\)";
  const COMMENTS = "/\*.*?\*/";
  const KEYWORD = "([!]?[a-z-]+)";

  public static function init(){

    classes::register_class_paths(array(
        "at_rule"                => "dsp/css/at_rule.php",
        "css_block"              => "dsp/css/block.php",
        "css_ruleset"            => "dsp/css/ruleset.php",
        "css_declarations_block" => "dsp/css/declarations.php",
        "css_declaration"        => "dsp/css/declaration.php",
    ));

  }

  public static function parse_file($file_path){
    try {
      $i = 0;
      $str = file_get_contents($file_path);
      $str = self::strip_comments($str);
      return self::parse_block($str, $i, $file_path);
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


  private static function parse_block($str, &$i, $file_path = false){

    $i += strspn($str, self::pad, $i); $start = $i;
    $embraced = $str{$i} == "{";
    $block = new css_block($embraced);
    if($file_path)
        $block->set_path($file_path);

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

  public static function split_values($str){
    $i=0; $values = array();
    while(!is_null($tmp = self::parse_value($str, $i)))
        $values []= $tmp;
    return $values;
  }


  public static function split_string($str){
    $all = array(
        self::STRING,                 //string
        "(\#[0-9A-F]+)",              //hexacolor
        "(-?[0-9.]+)(%|[a-z]{2,3})",  //unit value
        "(-?[0-9.]+)",                //simple number
        self::URI,                    //URI
        self::KEYWORD,                //keyword
    ); $mask = "#^(?:".join('|', $all).")#i";

    if(!preg_match($mask, $str, $out))
          return null; //throw new Exception("Invalid property value=".substr($str, $i));

    return array('full' => $out[0], 'uri' => pick($out[9]) );
  }


  private static function parse_value($str, &$i){
    $i += strspn($str, self::pad, $i);

    $infos = self::split_string(substr($str, $i));
    if(is_null($infos)) return null;

    $value = $infos['full']; //until more is needed
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

    $i += strlen($out[0])-1;

    $ruleset = new css_ruleset($selector);

        //declarations block
    if($str{$i++} != "{")
        throw new Exception("Invalid declarations block".substr($str, $i));

    do {
        $declaration = self::parse_declaration($str, $i);
        if(!$declaration)
            break;
        $ruleset->stack_declaration($declaration);
    } while($str{$i}!="}" && $str{$i}!="");

    if($str{$i}=="}")
        $i++;

    $i += strspn($str, self::pad, $i);
    return $ruleset;
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

/******** XML ***********/


  public static function from_xml($str){
    $tree = simplexml_load_string($str);
    return self::parse_block_XML($tree);
  }

  private static function parse_declaration_XML($xml){
    $tmp = new css_declaration((string)$xml['name']);
    if($xml['alternative']=='alternative') $tmp->set_alternative();
    foreach(self::split_values((string)$xml) as $value)
        $tmp->stack_value($value);
    return $tmp;
  }

  private static function parse_ruleset_XML($xml){
    $tmp = new css_ruleset((string)$xml['selector']);
    foreach($xml->rule as $rule)
        $tmp->stack_declaration(self::parse_declaration_XML($rule));
    return $tmp;
  }


  private static function parse_at_XML($xml){
    $tmp = new at_rule((string)$xml['keyword']);
    foreach(self::split_values((string)$xml->expression) as $value)
        $tmp->stack_expression($value);
    if($xml->style)
        $tmp->set_block(self::parse_block_XML($xml->style));
    return $tmp;
  }

  private static function parse_block_XML($xml){
    $embraced = $xml['exposed']=='exposed';
    $path     = (string)$xml['file_path'];

    $tmp = new css_block($embraced);
    if($path)
        $tmp->set_path($path);

    foreach($xml->children() as $child) {
        if($child->getName() == "style")
            $tmp->stack_statement(self::parse_block_XML($child));
        elseif($child->getName() == "ruleset")
            $tmp->stack_statement(self::parse_ruleset_XML($child));
        elseif($child->getName() == "atblock")
            $tmp->stack_statement(self::parse_at_XML($child));
    }
    return $tmp;
  }


}