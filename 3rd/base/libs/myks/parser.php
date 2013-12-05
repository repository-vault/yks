<?php

/**
    Exyks No myks Parser, by 131

    myks_parser  : build a full DOM tree based on every  files.
    output mode specify the root node what you want work with (table,view,mykse,procedure ..)
*/


class myks_parser {
  private $xslt;
  private $myks_gen;
  const myks_fpi = "-//YKS//MYKS";

  static private $myks_paths;
  public static function init(){

    $paths = array();
    //list paths
    foreach(yks::$get->config->myks->myks_paths->iterate("path") as $path)
        $paths[] = exyks_paths::resolve($path['path']);

    foreach(exyks::get_modules_list() as $modules)
        $paths = array_merge($paths, $modules->myks_paths);

    self::$myks_paths = $paths;
  }


  function trace(){
    cli::box("Scanning : mykses paths", '● '.join(LF.'● ', self::$myks_paths));
  }

  function __construct(){

    $this->myks_gen   = new DomDocument("1.0");

    $main_xml = $this->myks_gen->appendChild($this->myks_gen->createElement("myks_gen"));

    $files = array();
    foreach(self::$myks_paths as $path)
        $files = array_merge($files, files::find($path,'#.*?\.xml$#'));

    $xsl_file = RSRCS_PATH."/xsl/metas/myks_gen.xsl";
    if(!is_file($xsl_file)) die("Unable to locate ressource myks_gen.xsl, please check rsrcs");

    xml::register_fpi(self::myks_fpi, RSRCS_PATH."/dtds/myks.dtd", "myks");


    foreach($files as $xml_file){
        try {
            $doc = xml::load_file($xml_file, LIBXML_MYKS, self::myks_fpi);
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


