<?


list($get_action, $arg0) = array($sub0, $sub1);

if($action == "set_lang") try {

    $base = $_POST['lang_key'];
    $user_lang =  locales_manager::find_best_lang($base, exyks::retrieve('LANGUAGES'));
    $_SESSION['langs']['current'] = $user_lang;

    //if(JSX) do something special

    reloc("/");
} catch(rbx $e){}


if($get_action == "rsrcs") try {
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