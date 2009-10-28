<?php

/*  "Myks controler" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2009
*/


class myks {
  public static function get_types_xml(){
    $myks_parser = new myks_parser(config::retrieve('myks'));
    return $myks_parser->out("mykse")->saveXML();
  }

  public static function get_tables_xml(){
    $myks_parser = new myks_parser(config::retrieve('myks'));
    $tables_xml  = $myks_parser->out("table");
    $xsl_trans   = RSRCS_PATH."/xsl/metas/myks_tables.xsl";
    return xsl::resolve($tables_xml, $xsl_trans)->saveXML();
  }


  public static function resolve_base($type){
    return self::resolve_to($type, array('enum','int','string','text','time'));
  }

  public static function resolve_to($type, $final_types){
    static $types_xml = false; if(!$types_xml) $types_xml = yks::$get->types_xml;

    $mykse = $types_xml->$type;
    if(in_array((string)$mykse['type'], $final_types)) return $mykse;
    elseif(!$mykse) return array();
    else return self::resolve_to($mykse['type'], $final_types);
  }


}