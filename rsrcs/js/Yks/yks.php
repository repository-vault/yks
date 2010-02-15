<?
$base_dir = dirname(__FILE__);

Js::register("yks.root", $base_dir);
Js::register("yks.libs", "$base_dir/libs");
Js::register("yks", "$base_dir/mts");


$js_build_list = array_merge( $js_build_list, array(

    'path://yks.libs/xhr.js'         => true,
    'path://yks.libs/xslt.js'        => true,
    'path://yks.libs/urls.js'        => true,

    'path://yks.root/constants.js'   => true,

    'path://yks/Doms.js'             => true,

    'path://yks/Jsx/Rbx.js'          => true,
    'path://yks/Jsx/Jsx.js'          => true,
    'path://yks/Jsx/Forms.js'        => true,

    'path://yks/Jsx/Uploader.js'     => false,

    'path://yks/Jsx/Links.js'        => true,

    'path://yks/Jsx/Screen.js'       => true,
    'path://yks/Jsx/Box.js'          => true,
    
    'path://yks/Headers/Yks.js'      => true,
    'path://yks/Headers/Mootools.js' => true,
    'path://yks/Headers/Interfaces.js' => true,


    'path://yks/Interface/ShowCase.js' => false,
    'path://yks/Interface/Title.js' => true,

    'path://yks/Math/Matrix.js'    =>false,

));
