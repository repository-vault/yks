<?


$item_key=base64_decode($sub0);
//$mode_img=(bool)$sub1;

sess::$sess['trad_tmp']['item_key']=$item_key;

if($item_key)
    $item = new locale_item($item_key);

