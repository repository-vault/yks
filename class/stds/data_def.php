<?

global $config,$class_path;

  include_once "$class_path/stds/files.php";

if($flag=="types_xml"){
	include_once "$class_path/myks/parser.php";
	$myks_gen=new myks_parser(MYKS_DIR);
	return $myks_gen->out("mykse")->saveXML();
}


if($flag=="tables_xml"){
	include_once "$class_path/myks/parser.php";
	
	$myks_gen=new myks_parser(MYKS_DIR);
	$tables_xml=$myks_gen->out("table");
	$xsl = new DOMDocument();$xsl->load(RSRCS_DIR."/xsl/metas/myks_tables.xsl",LIBXML_YKS);
	$xslt = new XSLTProcessor(); $xslt->importStyleSheet($xsl);
	return $xslt->transformToXML($tables_xml);
}

if($flag=="entities"){
	include_once "$class_path/dom/dtds.php";
	$lang=$zone;if(!$lang)$lang=USER_LANG;
        $constants = retrieve_constants("#^(?!FLAG_)#", "&%s;");

        $dyn_entities = array();
        if($config->dyn_entities)
          foreach($config->dyn_entities->children() as $entity_def){
            if(strpos($entity_def['options'],"cachable")===false)continue;
            $dyn_entities = array_merge($dyn_entities,
                locale_renderer::render($entity_def->getName(),false, $lang));
        }

        $entities=count($args)>=3 && is_array($args[2])?$args[2]:array();
        $entities=array_merge($constants, $dyn_entities, $entities);

	$path="lang/$lang/";
        
	foreach(find_file($path,'\.ent$',FIND_FOLLOWLINK) as $dtd_file)
		$entities=array_merge($entities,dtd::ent_get($dtd_file));
	return $entities;
}
