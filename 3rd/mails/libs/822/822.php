<?php

define('EXT_MBSTRING',extension_loaded('mbstring'));

class rfc822 {
    static function date_valid($str){
        return strtotime(substr($str,0,31)); //lvl1

        $months=array(1=>'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $days=array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');

        $weekday_str='('.join('|',$days).')';
        $month_str='('.join('|',$months).')';
        $d="(\d+)";$s="[\s,]";$zone="(?:([+-])(\d{2})(\d{2}))";

        $date_format="#$weekday_str$s+$d$s+$month_str$s+$d$s+$d:$d:$d$s+$zone?#";
        if(!preg_match($date_format,$str,$out))return false;
        
        return strtotime($out[0]); //lvl2

        $test=gmmktime( $out[5], $out[6], $out[7], array_search($out[3],$months),$out[2],$out[4]);
        $test+=($out[8]=='-'?1:-1)*($out[9]*3600+$out[10]*60);
        return $test; //lvl3
    }

    static function header_extras($str){
        $params=array();
        preg_match_all('#;\s*([a-z0-9-]+)=((["\'])[^\\3]*?\\3|[^\s]+)#i',$str,$out,PREG_SET_ORDER);
        foreach($out as $data) $params[$data[1]]=trim($data[2],$data[3]);
        return $params;
    }


    static function decode($str, $encoding = '7bit'){
        if($encoding == "quoted-printable") return self::quoted_printable_decode($str);
        elseif($encoding == "base64") return base64_decode($str);
        else return $str; //8bit LOL
    }

    static function  quoted_printable_decode($str) {
        $str = preg_replace("#=\r?\n#",'',$str);
        $str = preg_replace("#=([a-f0-9]{2})#ie", "chr(hexdec('\\1'))",$str);
        return $str;
    }
        /** PEAR's */
    static function header_decode($input) {

        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);

        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {

            $encoded  = $matches[1];
            $charset  = strtolower($matches[2]);
            $encoding = strtolower($matches[3]);
            $text     = $matches[4];

            switch ($encoding) {
                case 'b':
                    $text = base64_decode($text);
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach($matches[1] as $value)
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    break;
            }

            if(EXT_MBSTRING && $charset!='utf-8')
                $text= mb_convert_encoding($text,"UTF-8",$charset);

            $input = str_replace($encoded, $text, $input);

        }

        return trim($input);
    }
}

