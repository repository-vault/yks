<?

$base_dir = dirname(__FILE__).'/Patchs';
Js::register("patch", $base_dir);


$js_build_list=array_merge( $js_build_list, array(

    'path://patch/Core/Window.js'            => true,
    'path://patch/Native/String.js'          => true,
    'path://patch/Native/Number.js'          => true,
    'path://patch/Native/Hash.js'            => true,
    'path://patch/Class/Class.extended.js'   => true,
    'path://patch/Element/Element.js'        => true,
    'path://patch/Utilities/Assets.js'       => true,
    'path://patch/Interface/Style.js'        => true,

));