<?

/**
    Exyks No myks Parser, by 131

    myks_parser  : build a full DOM tree based on every $myks_dir files.
    output mode specify the root node what you want work with (table,view,mykse,procedure ..)
*/


class myks_parser {
  private $xslt;
  private $myks_gen;
  const myks_public_name = "-//YKS//MYKS";

  function __construct($myks_dir){

    $this->myks_gen = new DomDocument("1.0");

    $main_xml=$this->myks_gen->appendChild($this->myks_gen->createElement("myks_gen"));
    //	$old_dir=getcwd();chdir($myks_dir);
    $files = $myks_dir?find_file($myks_dir,'.*?\.xml$',FIND_FOLLOWLINK):array();
    $xsl_file = RSRCS_DIR."/xsl/myks_gen.xsl";
    if(!is_file($xsl_file)) die("Unable to locate ressource myks_gen.xsl, please check rsrcs");
    foreach($files as $xml_file){

        //the file MUST validate
        $test=new DomDocument("1.0","UTF-8");
        $str = self::resolve_dtd($xml_file);
        $test->loadXML($str,LIBXML_MYKS);//@$test->load($xml_file);
        if(!$test->validate()){ rbx::error("$xml_file n'est pas valide"); continue; }

        $tmp_node = $this->myks_gen->importNode($test->documentElement,true);
        $main_xml->appendChild($tmp_node);
    }
    $xsl = new DOMDocument();$xsl->load($xsl_file,LIBXML_YKS);
    $this->xslt = new XSLTProcessor(); $this->xslt->importStyleSheet($xsl);
  }
  function out($mode){
    $this->xslt->setParameter('',array('root_xml'=>$mode));
    return $this->xslt->transformToDoc($this->myks_gen);
  }

  static function resolve_dtd($xml_file){
    $search = '#<\!DOCTYPE\s+myks\s+PUBLIC\s+"'.(self::myks_public_name).'"[^>]*>#';
    $replace = '<!DOCTYPE myks SYSTEM "'.(RSRCS_DIR."/dtds/myks.dtd").'">';

    $str = file_get_contents($xml_file);
    $str = preg_replace( $search, $replace, $str);

    return $str;
  }
}

