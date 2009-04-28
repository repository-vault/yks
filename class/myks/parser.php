<?

/**
    Exyks No myks Parser, by 131

    myks_parser  : build a full DOM tree based on every  files.
    output mode specify the root node what you want work with (table,view,mykse,procedure ..)
*/


class myks_parser {
  private $xslt;
  private $myks_gen;
  public $myks_path;
  const myks_fpi = "-//YKS//MYKS";

  function __construct($myks_config){
    $this->myks_path = paths_merge(ROOT_PATH, $myks_config['path'], "config/myks");
    $this->myks_gen = new DomDocument("1.0");

    $main_xml=$this->myks_gen->appendChild($this->myks_gen->createElement("myks_gen"));
    //	$old_path=getcwd();chdir($this->myks_path);
    $files = $this->myks_path?files::find($this->myks_path,'.*?\.xml$',files::FIND_FOLLOWLINK):array();
    $xsl_file = RSRCS_PATH."/xsl/metas/myks_gen.xsl";
    if(!is_file($xsl_file)) die("Unable to locate ressource myks_gen.xsl, please check rsrcs");

    xml::register_fpi(self::myks_fpi, RSRCS_PATH."/dtds/myks.dtd", "myks");


    foreach($files as $xml_file){
        try {
            $doc = xml::load_file($xml_file, self::myks_fpi);
        } catch(Exception $e){ rbx::error("$xml_file n'est pas valide"); continue; }
        $tmp_node = $this->myks_gen->importNode($doc->documentElement, true);
        $main_xml->appendChild($tmp_node);
    }

    $xsl = new DOMDocument();$xsl->load($xsl_file,LIBXML_YKS);
    $this->xslt = new XSLTProcessor(); $this->xslt->importStyleSheet($xsl);
  }
  function out($mode){
    $this->xslt->setParameter('',array('root_xml'=>$mode));
    return $this->xslt->transformToDoc($this->myks_gen);
  }


}

