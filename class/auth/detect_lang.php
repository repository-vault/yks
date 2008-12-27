<?


function find_best_lang($accept_language,$lang_list){
    $lang_filtered=array(); $lang_order=array();

    $accept_language_mask='#([a-z]{2}(?:-[a-z]{2})?)(?:\s*;q=([0-9.]+))?\s*,#';
    if(preg_match_all($accept_language_mask,strtolower("$accept_language,"),$out)) {
        if(!$out[2][0]) $out[2][0]=1;     //defaut q=1
        $lang_order=array_combine($out[1],$out[2]);arsort($lang_order);
        foreach($lang_order as &$pow) $pow+=1; //weights <1 are bad since they cannt be easily multiplied
    }

    $langs_nb = count($lang_list)-1;

    foreach($lang_order as $lang_want=>$lang_weight){
        $lang_want_root = substr($lang_want,0,2);
    
        foreach($lang_list as $lang_order=>$lang_key){
            $lang_key_root = substr($lang_key,0,2);

            if($lang_want == $lang_key) $lang_filtered[$lang_key][]=$lang_weight;
            elseif($lang_key_root == $lang_want_root)$lang_filtered[$lang_key][]=0.8*$lang_weight;
            else $lang_filtered[$lang_key][]=($langs_nb-$lang_order)/$langs_nb; 
        }
    }
    $lang_filtered=array_map( "max", $lang_filtered);
    return $lang_filtered?array_search( max($lang_filtered),$lang_filtered):'en-us'; //last fallback (bad config)
}
