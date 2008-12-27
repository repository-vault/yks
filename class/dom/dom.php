<?
/*	"Exyks DOM" by Leurent F. (131) for IMACUS
	distributed under the terms of GNU General Public License - © 2007 
*/

class dom {
  static function merge($src,$insert,$parent,$replace=false){
	$xml = new DOMDocument('1.0','utf-8');
	$xml->loadXML($src->asXML()); $main=$xml->documentElement;

	$dom2=dom_import_simplexml($insert)->childNodes;
	$from=$main->nodeName==$parent?$main:$main->getElementsByTagName($parent)->item(0);
	foreach($insert->attributes() as $k=>$v)$from->setAttribute("$k","$v");
	for($a=0;$a<$dom2->length;$a++){
		$main2=$xml->importNode($dom2->item($a),true);
		if($replace && ($tmp=$from->getElementsByTagName($main2->nodeName)->item(0)))
			$from->replaceChild($main2,$tmp);
		else $from->appendChild($main2);
	}return simplexml_import_dom($xml);
  }

  static function remove($src,$parent,$find=false){
	$main=dom_import_simplexml($src);

	if(!is_array($find))$find=array("element"=>"$find");
	$element=$find['element'];unset($find['element']);
	list($filter_key,$filter_val)=each($find);

	$from=$main->nodeName==$parent?$main:$main->getElementsByTagName($parent)->item(0);
	$list=$from->getElementsByTagName($element);$rem=false;

	if($list->length==1)$from->removeChild($list->item(0));
	else for($a=0;$a<$list->length;$a++){
		$tmp=$list->item($a);
		if($filter_key=="content")die($tmp->nodeValue);
		if($tmp->getAttribute($filter_key)==$filter_val){
			$from->removeChild($tmp);
			$a--;// !! $list is resized when using removechild
		}
	} return simplexml_import_dom($main);
  }
}

?>