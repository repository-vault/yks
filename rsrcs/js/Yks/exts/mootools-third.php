<?

Js::register("cc",    realpath(dirname(__FILE__)."/../../3rd/Clientcide"));
Js::register("walsh", realpath(dirname(__FILE__)."/../../3rd/Walsh"));

$js_build_list = array_merge( $js_build_list, array(
    'path://cc/Class/Binds.js'           => true,
    'path://walsh/Native/Array.js'        => true,

));