<?


function preg_reduce($mask, $str){
    preg_match($mask,$str,$out);
    return $out[1];
}

function simplexml_load_html($str, $class=false){
    libxml_use_internal_errors(true);
    $doc = new DomDocument("1.0");
    $doc->loadHTML($str);
    libxml_clear_errors();
    libxml_use_internal_errors();
    return simplexml_import_dom($doc, $class);
}
