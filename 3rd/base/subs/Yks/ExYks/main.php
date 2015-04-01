<?php

$action = pick($action, $sub0);

//RewriteRule ^(fr-fr|en-us)$  /?/Yks/ExYks//set_lang;$1 [L]

if($action == "set_lang") try {
    $base = pick($_POST['lang_key'], $sub1);
    $user_lang =  locales_manager::find_best_lang($base);
    $_SESSION['langs']['current'] = $user_lang;

    //if(JSX) do something special

    reloc("/");
} catch(rbx $e){}


if($action == "rsrcs") try {
    ///?/Yks/ExYks//rsrcs|video_player;320;240
    $file = $argv0;
    if($file == "flvplayer") {
        $file_path = RSRCS_PATH."/swfs/medias/flv_player.swf";
        header(TYPE_SWF);readfile($file_path);die;
    }
    if($file == "skin") {
        $file_path = RSRCS_PATH."/swfs/medias/ClearOverAll.swf";
        header(TYPE_SWF);readfile($file_path);die;
    }

    


} catch(rbx $e){}