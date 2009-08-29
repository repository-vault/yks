<?

$js_prefixs['[YKS/ROOT]'] = dirname(__FILE__);
$js_prefixs['[YKS/LIBS]'] = $js_prefixs['[YKS/ROOT]'].'/libs';
$js_prefixs['[YKS]']      = $js_prefixs['[YKS/ROOT]'].'/mts';

$js_build_list = array_merge( $js_build_list, array(

    '[YKS/LIBS]/xhr'         => true,
    '[YKS/LIBS]/xslt'        => true,
    '[YKS/LIBS]/urls'        => true,

    '[YKS/ROOT]/constants'   => true,

    '[YKS]/Doms'             => true,

    '[YKS]/Jsx/Rbx'          => true,
    '[YKS]/Jsx/Jsx'          => true,
    '[YKS]/Jsx/Forms'        => true,

    '[YKS]/Jsx/Uploader'     => false,

    '[YKS]/Jsx/Links'        => true,

    '[YKS]/Jsx/Screen'       => true,
    '[YKS]/Jsx/Box'          => true,
    
    '[YKS]/Headers/Yks'      => true,
    '[YKS]/Headers/Mootools' => true,
    '[YKS]/Headers/Interfaces' => true,


    '[YKS]/Interface/ShowCase' => false,
    '[YKS]/Interface/Title' => true,

    '[YKS]/Math/Matrix'    =>false,



));
