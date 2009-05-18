<?

$js_prefixs['[MT]'] = realpath(dirname(__FILE__)."/../../Mootools");


$js_build_list = array_merge( $js_build_list, array(

    '[MT]/Core/Core'                 => true,
    '[MT]/Core/Browser'              => true,
    '[MT]/Native/Array'              => true,
    '[MT]/Native/Function'           => true,
    '[MT]/Native/Number'             => true,
    '[MT]/Native/String'             => true,
    '[MT]/Native/Event'              => true,
    '[MT]/Native/Hash'               => true,
    '[MT]/Class/Class'               => true,
    '[MT]/Class/Class.Extras'        => true,
    '[MT]/Element/Element'           => true,
    '[MT]/Element/Element.Event'     => true,
    '[MT]/Element/Element.Style'     => true,
    '[MT]/Element/Element.Dimensions'=> true,
    '[MT]/Utilities/Selectors'       => true,
    '[MT]/Utilities/JSON'            => true,
    '[MT]/Utilities/DomReady'        => true,
    '[MT]/Utilities/Cookie'          => false,
    '[MT]/Fx/Fx'                     => true,
    '[MT]/Fx/Fx.CSS'                 => true,
    '[MT]/Fx/Fx.Tween'               => true,
    '[MT]/Fx/Fx.Morph'               => true,
    '[MT]/Fx/Fx.Elements'            => true,
    '[MT]/Fx/Fx.Scroll'              => false,

    '[MT]/Fx/Fx.Slide'               => false,

    '[MT]/Fx/Fx.Transitions'         => true,

    '[MT]/Request/Request'           => false,
    '[MT]/Request/Request.HTML'      => false,
    '[MT]/Request/Request.JSON'      => false,
    '[MT]/Drag/Drag'                 => false,
    '[MT]/Drag/Drag.Move'            => false,
    '[MT]/Plugins/Scroller'          => false,

    '[MT]/Utilities/Assets'          => true,

));