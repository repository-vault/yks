<?
/*  "Myks controler" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2009
*/


class myks {
  public static function get_types_xml(){
    include_once CLASS_PATH."/stds/files.php";
    include_once CLASS_PATH."/myks/parser.php";
    $myks_gen = new myks_parser(config::retrieve('myks'));
    return $myks_gen->out("mykse")->saveXML();
  }

  public static function get_tables_xml(){
    include_once CLASS_PATH."/stds/files.php";
    include_once CLASS_PATH."/myks/parser.php";
    $myks_gen   = new myks_parser(config::retrieve('myks'));
    $tables_xml = $myks_gen->out("table");
    $xsl = new DOMDocument();$xsl->load(RSRCS_PATH."/xsl/metas/myks_tables.xsl",LIBXML_YKS);
    $xslt = new XSLTProcessor(); $xslt->importStyleSheet($xsl);
    return $xslt->transformToXML($tables_xml);
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