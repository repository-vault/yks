<?

function preg_filter($mask, $str){
    preg_match($mask,$str,$out);
    return $out[1];
}