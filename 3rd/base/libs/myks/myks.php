<?php

/*  "Myks controler" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2009
*/


class myks {

  public static $LIBS; //libraries path (here)

  public static function init(){
    self::$LIBS = dirname(__FILE__); //MOD_YKS_ROOT."/libs/myks"; //?
  }

  public static function get_types_xml() {
    $myks_parser = myks::get_parser();
    return $myks_parser->out("mykse")->saveXML();
  }

  public static function get_tables_xml(){
    $myks_parser = myks::get_parser();
    $tables_xml  = $myks_parser->out("table");
    return self::tables_reflection($tables_xml)->saveXML();
  }


/* RAW tables_xml data are not user-friendly
   We should scan & transliterate it in a smarter container
*/
  public static function tables_reflection($tables_xml_raw) {
    $xsl_trans   = RSRCS_PATH."/xsl/metas/myks_tables.xsl";
    return xsl::resolve($tables_xml_raw, $xsl_trans);
  }


  public static function resolve_base($type){
    return self::resolve_to($type, array('enum','int','string','text','time','bool'));
  }

  public static function resolve_to($type, $final_types){
    static $types_xml = false; if(!$types_xml) $types_xml = yks::$get->types_xml;

    $mykse = $types_xml->$type;
    if(in_array((string)$mykse['type'], $final_types)) return $mykse;
    elseif(!$mykse) return array();
    else return self::resolve_to($mykse['type'], $final_types);
  }

  public static function get_parser(){
    return new myks_parser(); //no config
  }

}