<?
function truncate($str,$len=10){return preg_replace('#&[^;]*?$#m','…',mb_strimwidth($str,0,$len,'…'));}
function unicode_decode($str){return preg_replace('#\\\u([0-9a-f]{4})#e',"unicode_value('\\1')",$str);}
function unicode_value($code) {
    if(($v=hexdec($code))<0x0080) return chr($v);
    elseif($v<0x0800) return chr((($v&0x07c0)>>6)|0xc0).chr(($v&0x3f)|0x80);
    else return chr((($v&0xf000)>>12)|0xe0).chr((($v&0x0fc0)>>6)|0x80).chr(($v&0x3f)|0x80);
}


function pict_clean($str){ return strtr($str, '/', ' '); }



function entity_load($entity_type, $entity_def, $lang, $vals=false){
        $entities=array();
        $entity_key=(string)($entity_def['key']?$entity_def['key']:$entity_type);
        $entity_pfx= $entity_def['prefix'];
        if($vals) {
            if($entity_pfx) $vals=array_mask($vals,"$entity_pfx.$entity_type.%s");
            $where=array($entity_key=>$vals);
            $limit="LIMIT ".count($vals);
        }else $where=array("true");

        if($entity_def['mode']=='col') $val=$entity_def['col']?$entity_def['col']:$lang;
        else {
            $where['lang_key']=$lang;
            $val = $entity_def['col'];
        }

        sql::select($entity_def['table'],$where, "`$entity_key` as key, `$val` as val",$limit);
        while(extract(sql::fetch()))
            $entities['&'.(
                    $entity_pfx?substr($key,strlen($entity_pfx)+1):$entity_type.'.'.$key
                  ).';']=$val;
        return $entities;
}

function entity_dynamics($str,$lang){
    global $config;
    $entity_mask=join('|',array_keys((array)$config->dyn_entities));
    $entity_dynamic_mask="#&($entity_mask)\.([.a-zA-Z0-9_-]+);#";
    preg_match_all($entity_dynamic_mask,$str,$out);

    $entity_loaded=array();
    
    foreach($out[1] as $k=>$entity_type)
        $entity_loaded[$entity_type][]=$out[2][$k];

    $entities=array();
    foreach($entity_loaded as $entity_type=>$vals)
        $entities= array_merge(
            $entities,
            entity_load($entity_type,$config->dyn_entities->$entity_type,$lang,$vals)
        );
    return strtr($str,$entities);
}




    //may be usefull for search forms
function strip_accents($str){
    return strtr($str,array('À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'Ç'=>'C', 'ç'=>'c', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ÿ'=>'y', 'Ñ'=>'N', 'ñ'=>'n'));
}

function rte_clean($str){
    $str=htmlspecialchars_decode(trim($str));
    $str=html_entity_decode($str,ENT_NOQUOTES,"UTF-8");
    if(stripos($str,"<body")){
        preg_match("#<body.*?>(.*?)</body>#is",$str,$out);
        $str=(string)$out[1];
    }
    $doc = new DOMDocument('1.0','UTF-8');
    @$doc->loadHTML("<html><body>$str</body></html>"); $str=$doc->saveXML();
    $str=utf8_decode(html_entity_decode($str,ENT_NOQUOTES,"UTF-8"));
    $str=mb_ereg_replace("&","&amp;",mb_ereg_replace("&amp;","&",$str));
    if(mb_strpos($str,"<body/>"))return "";
    if(mb_detect_encoding($str,'utf-8,iso-8859-1')!="UTF-8")$str=utf8_encode($str);
    $len=mb_strlen($str);$start=mb_strpos($str,"<body>")+6;$end=mb_strpos($str,"</body>");
    $str=mb_substr($str,$start,$end-$start);

    $replaces=array(
        '#<([a-z/]+[^<>]*?)>#s'=>'&ks_start;$1&ks_end;',
        '#<#'=>'&lt;',
        '#>#'=>'&gt;',
        '#&ks_start;#'=>'<',
        '#&ks_end;#'=>'>',
        "#[\r\n]#"=>'',
        '#^(<br/>)+|(<br/>)+$#'=>'',
    );$str=preg_areplace($replaces,$str);

    return $str;
}

