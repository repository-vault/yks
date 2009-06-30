<?

/**
    Exyks No myks Parser, by 131

    myks_parser  : build a full DOM tree based on every  files.
    output mode specify the root node what you want work with (table,view,mykse,procedure ..)
*/


class myks_parser {
  private $xslt;
  private $myks_gen;
  public $myks_paths;
  const myks_fpi = "-//YKS//MYKS";
  private $myks_ns = array();


  static function default_ns(){
    return array(
        'yks'   => RSRCS_PATH."/myks/yks",
        'isos'  => RSRCS_PATH."/myks/isos",
        'pgsql' => RSRCS_PATH."/myks/pgsql"
    );
  }
  private function resolve_path($path){
    $mask = '#^myks://('.join('|',array_keys($this->myks_ns)).')#e';
    $repl = '$this->myks_ns["$1"]';
    $path = preg_replace($mask, $repl, $path);;
    return paths_merge(ROOT_PATH, $path);
  }
  function __construct($myks_config){
    $tmp_ns = array();
    //foreach($myks_config as ns
    $this->myks_ns = array_merge(self::default_ns(), $tmp_ns);
    $tmp_paths = array();
    if($myks_config->myks_paths->path) foreach($myks_config->myks_paths->path as $path)
        $tmp_paths[]=$this->resolve_path($path['path']);
    
    $this->myks_paths = $tmp_paths;
    $this->myks_gen   = new DomDocument("1.0");

    $main_xml = $this->myks_gen->appendChild($this->myks_gen->createElement("myks_gen"));

    $files = array();
    foreach($this->myks_paths as $path)
        $files = array_merge($files, files::find($path,'.*?\.xml$'));

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


