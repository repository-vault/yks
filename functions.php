<?


if(!function_exists('preg_reduce')) {
  function preg_reduce($mask, $str){
    preg_match($mask,$str,$out);
    return $out[1];
  }
}

function simplexml_load_url($url){
    $str = file_get_contents($url);
    return simplexml_load_html($str);
}

function simplexml_load_html($str, $class="Element"){
    libxml_use_internal_errors(true);
    $doc = new DomDocument("1.0", "UTF-8");
    $doc->loadHTML($str);
    libxml_clear_errors();
    libxml_use_internal_errors();
    return simplexml_import_dom($doc, $class);
}
