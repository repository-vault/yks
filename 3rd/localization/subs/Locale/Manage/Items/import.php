<?


class items_import {

  private static function parse_ini_string($string){
    if(!preg_match_all("#^\s*([^=\s]*?)\s*=([^\r\n]*)#m", $string, $out))
        return array();
    return array_combine($out[1], $out[2]);
  }

  function parse($str, $lang_key){

    $res =  self::parse_ini_string($str);

    foreach($res as $item_key => $item_value){
        $verif_item = compact('item_key', 'lang_key');
        $data = array('value'=> self::format_out($item_value));
        sql::replace("ks_locale_values", $data, $verif_item);
    }

    // Mise à jour de la table de date de generation
    locale::lang_update(array($lang_key));

    throw rbx::error($res);

  }

  private static function format_out($str){

    return str_replace("\\n", CRLF, $str);
  }
}


if($action == "items_import") try {
    $item_file = $_POST['items_file'];
    if($item_file){
        $res = users::upload_check('locale_file', $item_file);
        if(!$res) $items_list = '';
        else $items_list = file_get_contents($res['tmp_file']);
    } else $items_list = specialchars_decode($_POST['items_list']);

    //throw rbx::error(print_r($_POST,1));

    items_import::parse($items_list, $_POST['lang_key']);

    rbx::error("ok $items_list");

} catch( rbx $e){}