<?php

$lang_infos = locale::languages_infos($user_languages);

if($action=="delete")try{
    $verif_delete = array(
      'lang_key' => $_POST['id'],
      'user_id'  => $user['user_id'],
    );
    sql::delete(ks_users_profile_locale_languages, $verif_delete);
    unset($lang_infos[$verif_delete['lang_key']]);

    jsx::$rbx = false;
}catch(rbx $e){}

$cols = array(
  "lang_key" => "<th>#</th>",
  "lang"     => "<th>Language</th>",
  "domain"   => "<th>Domain</th>",
  "action"   => "<th>Actions</th>",
);

$headers = new data_headers($cols);