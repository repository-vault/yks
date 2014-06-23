<?php

$user_id = (int)$sub0;

auth::verif("yks", "admin", 403);

$module_users = false;
foreach(exyks::get_modules_list() as $modules){
  if($modules->key == 'users'){
    $module_users = true;
    break;
  }
}

try{
  if(!$module_users){
    throw new Exception('User module not loaded.');
  }

  $user = user::instanciate($user_id);
  if(!$user) Throw rbx::error("Invalid user");
}
catch(rbx $e){
}

sql::select('ks_locale_domains_list', sql::true);
$locale_domains_list = sql::brute_fetch('locale_domain_id');
$user_languages = array_extract($user->lang_key, 'lang_key');


