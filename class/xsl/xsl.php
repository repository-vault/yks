<?
/*	"Xsl gest" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2007 
*/

$libxml_errors=array(
    73=>"Expected &gt;",
);

class xsl {
  static function resolve_ent($content){
    $xml = new DOMDocument();
    $xml->preserveWhiteSpace =false;
    $xml->loadXML($content,LIBXML_YKS);
    $xml->formatOutput=true;
    return $xml->saveXML();
  }
  static function resolve($xml,$xsl_file){
    global $libxml_errors;
    $xsl = new DOMDocument(); $xslt = new XSLTProcessor();
    $errors = libxml_get_errors();
    if($errors)trigger_error(self::disp_errors($errors), E_USER_WARNING);
    libxml_clear_errors();
    $xsl->load($xsl_file,LIBXML_YKS);

    $xslt->importStyleSheet($xsl);
    return $xslt->transformToDoc($xml);
  }
  static function disp_errors($errors){
    $errs=array();

    $ent_mask="#Entity '(.*?)' not defined#";
    foreach($errors as $err){
        $code=$err->code;
        $mess=(string)$err->message;
        $errs[]=$mess;
    }return join(', ',$errs);
  }
  static function load_multiples($xml_file,$xsl_file=false){
    $old_path=getcwd();chdir(dirname($xml_file));
    $xml = new DOMDocument();
    $xsl = new DOMDocument();
    $xslt = new XSLTProcessor();
    $xml->load($xml_file,LIBXML_YKS);
    if(!$xsl_file){
        $tmp=$xml->firstChild;
        while($tmp && $tmp->nodeName!="xml-stylesheet")$tmp=$tmp->nextSibling;
        if($tmp->nodeName!="xml-stylesheet")die("Unable to find related stylesheet");
        $data=$tmp->data;$start=strpos($data,"href")+6;
        $xsl_file=substr($data,$start,strpos($data,$data{$start-1},$start)-$start);
    }
    $xsl->load($xsl_file,LIBXML_YKS);
    $xslt->importStyleSheet($xsl);
    chdir($old_path);
    $res=$xslt->transformToDoc($xml);	//beah

    return 	simplexml_import_dom($res);
  }

  static function load_simplexml($xml_file){
    $xml = new DOMDocument();
    $xml->resolveExternals=true;
    $xml->validateOnParse=true;
    $xml->load($xml_file);
    return 	simplexml_import_dom($xml);
  }
}

