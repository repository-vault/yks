<?

$js_prefixs['[PATCH]'] = dirname(__FILE__).'/Patchs';

$js_build_list=array_merge( $js_build_list, array(

    '[PATCH]/Core/Window'            => true,
    '[PATCH]/Native/String'          => true,
    '[PATCH]/Native/Number'          => true,
    '[PATCH]/Native/Hash'            => true,
    '[PATCH]/Class/Class.extended'   => true,
    '[PATCH]/Element/Element'        => true,
    '[PATCH]/Utilities/Assets'       => true,
    '[PATCH]/Interface/Style'        => true,

));