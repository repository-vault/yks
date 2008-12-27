<?
$ent_mask="([a-z0-9_\.-]*?)";
$val_mask="\"([^\"]*?)\"";

define('ENTITY_MASK',"#<!ENTITY\s+$ent_mask\s+$val_mask\s*>#i");
define('INTERNALS_MASK',"#<!ENTITY\s+%\s+$ent_mask\s+$val_mask\s*>#i");

class dtd {

  public $entities=array();	//entities
  public $internals=array();	//internals entities
  public $file;

  function __construct($dtd_file) {
		// see http://bugs.php.net/bug.php?id=40956&edit=1
	$tmp=XML_VERSION."<!DOCTYPE tmp [ <!ENTITY % tmp SYSTEM '$dtd_file'> %tmp; ]><tmp/>";
	$doc=new DOMDocument("1.0"); $doc->loadXML($tmp,LIBXML_YKS);
	$str=$doc->doctype->internalSubset;unset($doc);$tmp='';
	while( preg_match_all(INTERNALS_MASK,$str,$out) && $tmp!=$str)
		$str=strtr($tmp=$str,array_combine(
				array_map(create_function('$v','return "%$v;";'),$out[1]),$out[2])
		);
	
	if($out[0]) $this->internals=array_combine($out[1],$out[2]);

	if(preg_match_all(ENTITY_MASK,$str,$out))
		$this->entities=array_combine($out[1],$out[2]);

  }

  function save(){ return false; //this will not work 
	$entities_str='';$includes_str='';ksort($this->entities);ksort($this->includes);

	foreach(array_filter($this->entities) as $ent_code=>$ent_trad)
		$entities_str.="<!ENTITY $ent_code \"$ent_trad\">\n";
	foreach(array_filter($this->includes) as $ent_code=>$ent_file)
		$includes_str.="<!ENTITY % $ent_code SYSTEM \"$ent_file\">\n%$ent_code;\n";
	file_put_contents($this->file,"$includes_str\n$entities_str");

  }
  static function ent_get($str){
	return preg_match_all(ENTITY_MASK,file_get_contents($str),$out)?
		array_combine(array_mask($out[1],'&%s;'),$out[2]):array();
  }
  static function ent_remove($src,$ent){ $dtd=new dtd($src);unset($dtd->entities[$ent]);$dtd->save();}
  static function ent_add($src,$ent,$str){ $dtd=new dtd($src);$dtd->entities[$ent]=$str;$dtd->save();}
}

?>