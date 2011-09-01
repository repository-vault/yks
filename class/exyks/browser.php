<?php

     //engine detection

    $agent = " ".strtolower($_SERVER['HTTP_USER_AGENT']);
    $platform  = preg_match('/mac|win|android|blackberry/', $agent, $out)?$out[0]:'other';



    $engine = ""; //webkit|presto|agent|gecko|robot|mobile

    if( strpos($agent, "webkit") ){
        $engine = "webkit";
        if(strpos($agent, "ipod") || strpos($agent, "iphone"))
            $platform = "ipod";
    } elseif(strpos($agent, "presto") ) {
        $engine = "presto";
    } elseif( strpos($agent, "msie") ) {
        $engine = "trident";
    } elseif( strpos($agent, "blackberry") ) {
        $engine   = "mobile"; //anonymous mobile (low specs) engine
    } elseif( strpos($agent, "validator") || strpos($agent, "robot")   ) {
        $engine = "robot";
    } else {
        $engine = "gecko"; //RULZ'EM ALL !
    }

    define('IE', $engine=="trident");
    define('IE6',strpos($agent,"msie 6.0")!==false);
    define('OPERA', $engine=="presto");
    define('SAFARI', $engine=="webkit");
    define('GECKO', $engine=="gecko");
    define('ROBOT', $engine=="robot");

    define('PLATFORM_MOBILE', in_array($platform, array("ipod", "blackberry", "android")));

 