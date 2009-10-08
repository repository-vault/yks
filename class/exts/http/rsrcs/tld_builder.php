<?

/*
    Tld level fetcher - by François Leurent - mozilla.exyks.org,
    returns an associative Hash of mozilla discovered tld and the level they belong to
    that's the simpliest base to start a domain validator

    Ie :
        'jp'=>1,
        'ac.jp'=>2,
        'yokohama.jp'=>3,
        'metro.tokyo.jp'=>2,
        
*/
$from_url = 'http://mxr.mozilla.org/mozilla-central/source/netwerk/dns/src/effective_tld_names.dat?raw=1';
$to_file  = 'tlds.php';

$tlds = array();
$str  = file_get_contents($from_url);

    // list formating
$str  = preg_replace('#^.*?//.*?$#m',"", $str);
$str  = preg_replace('#[ \t]#', '', $str);
$list = preg_split("#\r?\n#", $str, -1,  PREG_SPLIT_NO_EMPTY);

    //list processing
foreach($list as $tld){
    preg_match('#^(\!|\*\.)?(?:(.*?)\.)?([a-z]+)$#', $tld, $out);
    $tld = array_merge(array_filter(explode('.', $out[2])), array($out[3]));
    $lvl = count($tld); $tld = join('.', $tld);
        //modifiers
    if($out[1]=="*.") $lvl ++; elseif($out[1] == "!") $lvl --;

    $tlds[$tld] = $lvl;
}
$var = str_replace(' ', '', var_export($tlds, true));
file_put_contents($to_file, "<?return $var;");