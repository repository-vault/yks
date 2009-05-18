<?

$js_prefixs['[CC]'] = realpath(dirname(__FILE__)."/../../Clientcide");

$js_build_list=array_merge( $js_build_list, array(
    '[CC]/Class/Binds'            => true,
));