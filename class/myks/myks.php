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



}