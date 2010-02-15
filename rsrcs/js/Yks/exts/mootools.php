<?

Js::register("mt", realpath(dirname(__FILE__)."/../../Mootools"));


$js_build_list = array_merge( $js_build_list, array(

    'path://mt/Core/Core.js'                 => true,
    'path://mt/Core/Browser.js'              => true,
    'path://mt/Native/Array.js'              => true,
    'path://mt/Native/Function.js'           => true,
    'path://mt/Native/Number.js'             => true,
    'path://mt/Native/String.js'             => true,
    'path://mt/Native/Event.js'              => true,
    'path://mt/Native/Hash.js'               => true,
    'path://mt/Class/Class.js'               => true,
    'path://mt/Class/Class.Extras.js'        => true,
    'path://mt/Element/Element.js'           => true,
    'path://mt/Element/Element.Event.js'     => true,
    'path://mt/Element/Element.Style.js'     => true,
    'path://mt/Element/Element.Dimensions.js'=> true,
    'path://mt/Utilities/Selectors.js'       => true,
    'path://mt/Utilities/JSON.js'            => true,
    'path://mt/Utilities/DomReady.js'        => true,
    'path://mt/Utilities/Cookie.js'          => false,
    'path://mt/Fx/Fx.js'                     => true,
    'path://mt/Fx/Fx.CSS.js'                 => true,
    'path://mt/Fx/Fx.Tween.js'               => true,
    'path://mt/Fx/Fx.Morph.js'               => true,
    'path://mt/Fx/Fx.Elements.js'            => true,
    'path://mt/Fx/Fx.Scroll.js'              => false,

    'path://mt/Fx/Fx.Slide.js'               => false,

    'path://mt/Fx/Fx.Transitions.js'         => true,

    'path://mt/Request/Request.js'           => false,
    'path://mt/Request/Request.HTML.js'      => false,
    'path://mt/Request/Request.JSON.js'      => false,
    'path://mt/Drag/Drag.js'                 => false,
    'path://mt/Drag/Drag.Move.js'            => false,
    'path://mt/Plugins/Scroller.js'          => false,
    'path://mt/Interface/Accordion.js'       => false,

    'path://mt/Utilities/Assets.js'          => true,

));