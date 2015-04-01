<?php

  sql::select('ks_locale_domains_list', array('locale_domain_id' => array_filter($locale_domains)));
  $locale_domains_list = sql::brute_fetch('locale_domain_id');

  if($action == "clean_locale_cache") try {

    if(!auth::verif("zone_distrib:distrib_root",'admin'))
      throw rbx::error("You dont have enought access right.");

    $locale_domain_ids = (array)$_POST['locale_domain_id'];
    if(!array_intersect($locale_domain_ids, array_keys($locale_domains_list)))
      throw rbx::error("Invalid domain");

    $lang_keys = array();
    foreach($locale_domain_ids as $locale_domain_id) {
      $where = array(
        "lang_key like '%_$locale_domain_id'"
      );
      $nb_dels[$locale_domain_id] = sql::delete("ivs_languages_last_update", $where);
    }

    $res = array();
    foreach($nb_dels as $domain_id=>$del) $res[] = "{$locale_domains_list[$domain_id]['locale_domain_name']} ($del elements)";
    rbx::ok("Locale cache cleared for domains : ".join(' - ',$res)." !!");

} catch(rbx $e) {}
