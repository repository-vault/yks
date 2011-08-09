<?
// Fonction outils pour les locales.

//coupe les répétion de plus de 10 chars    
function strip_replace($str){ 
    $str = preg_replace("#(.)\\1{5,}#",'$1$1',$str);
    return preg_replace("#([^\s]{10})([^\s]{5})#",'$1 $2',$str);
    
}

// Clean les caractère spéciaux d'une zone de texte.
function clean_feeeds($str){
    $search = array(
        "#\r?\n|<CRLF>#",
        "#\r#",
    );
    $replace = array(
        '\\n',
        '',
    );
    return trim(preg_replace($search, $replace, $str));
}
