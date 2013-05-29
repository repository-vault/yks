<?php
if($action == "add_language"){
  $language_list = explode(',', $_POST['language_search']);

  $lang_infos = locale::languages_infos($language_list);//verif

  foreach($lang_infos as $language){

    if(in_array($language['lang_key'], $user_languages)){
      continue;
    }

    $vals = array(
      'user_id'  => $user['user_id'],
      'lang_key' => $language['lang_key'],
    );

    sql::insert('ks_users_profile_locale_languages', $vals);
  }

  rbx::ok('OK');
  jsx::js_eval(jsx::RELOAD);
}

