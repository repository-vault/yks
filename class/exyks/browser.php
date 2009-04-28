<?

     //engine detection

    $agent = " ".strtolower($_SERVER['HTTP_USER_AGENT']);
    $platform  = preg_match('/mac|win|linux/', $agent, $out)?$out[0]:'other';

    $engine = "";
    if( strpos($agent, "webkit") ){
        $engine = "webkit";
        if(strpos($agent, "ipod") ||strpos($agent, "iphone")) $platform = "ipod";
    } elseif(strpos($agent, "presto") ) {
        $engine = "presto";
    } elseif( strpos($agent, "msie") ) {
        $engine = "trident";
    } elseif( strpos($agent, "validator") || strpos($agent, "robot")   ) {
        $engine = "robot";
    } else {
        $engine = "gecko"; //RULZ'EM ALL !
    }

    define('IE', $engine=="trident");
    define('IPOD',$platform=="ipod");
    define('IE6',strpos($agent,"msie 6.0")!==false);
    define('OPERA', $engine=="presto");
    define('SAFARI', $engine=="webkit");
    define('GECKO', $engine=="gecko");
    define('ROBOT', $engine=="robot");


 