<?php

class date {

  static function validate($date,$format=DATE_MASK, $zero_time = false){
        $format=preg_replace("#[a-z]#i","%$0",strtr($format,array('i'=>'M','s'=>'S')));
	if(!($tm=strptime($date,$format)))return false;

        if($zero_time)
        return gmmktime($tm['tm_hour'],$tm['tm_min'],$tm['tm_sec'],
                $tm['tm_mon']+1,$tm['tm_mday']+1, pick($tm['tm_year'], 1970));

	return mktime($tm['tm_hour'],$tm['tm_min'],$tm['tm_sec'],
                $tm['tm_mon']+1,$tm['tm_mday'],1900+$tm['tm_year']);
  }


  static function add($time,$dd=0,$mm=0,$YY=0){
    list($d,$m,$Y,$H,$i,$s)=explode(',',date('d,m,Y,H,i,s',$time));
    return mktime($H,$i,$s,$mm+$m,$dd+$d,$YY+$Y);
  }


    /** get universal day **/
  static function get_uday($d=false,$m=false,$y=false,$timestamp=false){
	return floor(($timestamp?$timestamp:mktime(0,0,0,
		$m!==false?$m:idate('m'),
		$d!==false?$d:idate('d'),
		$y!==false?$y:idate('Y')))/86400);
  }

}



