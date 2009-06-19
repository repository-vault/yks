<?

$js_prefixs['[CC]'] = realpath(dirname(__FILE__)."/../../3rd/Clientcide");
$js_prefixs['[WALSH]'] = realpath(dirname(__FILE__)."/../../3rd/Walsh");
$js_build_list=array_merge( $js_build_list, array(
    '[CC]/Class/Binds'            => true,
    '[WALSH]/Native/Array'            => true,

));