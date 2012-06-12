<?php

//suppresion du ban
if($action == "trash") try{
  authfailed::delete(
    array(
      'failed_login' => $_POST['failed_login'],
      'failed_ip'    => $_POST['failed_ip'],
    ));
  //rechargement après la suppression
  jsx::js_eval(jsx::RELOAD);
}
catch(rbx $e){}

//suppression d'une liste de ban
if($action == "fails_delete") try{     
  
  $failed_infos = $_POST['failed_list'];
  
  foreach($failed_infos as $key => $fail){
    $info = explode(',', $key);
    authfailed::delete(
      array(
        'failed_login' => $info[1],
        'failed_ip'    => $info[0],
    ));
  }
  
  return jsx::js_eval(jsx::RELOAD);
}
catch(rbx $e){}

//suppression des anciens ban
authfailed::delete_old();

//critère de recherche
$search_infos = sess::retrieve($search_flag);

if(isset($search_infos)){
  
  $fields = array(
    'count(auth_ldap_failed_id)',
    'failed_login',
    'failed_ip',
  );
  
  //creation de la req
  $where    = array();
  $having   = array();
  $sort_way = $search_infos['sort_way'];
  $sort_by  = $search_infos['sort_by'];
    
  if($search_infos['search_login'] != null)
    $where[] = "failed_login Like '%{$search_infos['search_login']}%'";
  
  if($search_infos['search_ip'] != null)
    $where[] = "failed_ip Like '%{$search_infos['search_ip']}%'";
  
  if($search_infos['search_count_start'])
      $having[] = 'count(auth_ldap_failed_id) >= '.$search_infos['search_count_start'];

  if($search_infos['search_count_end'])
    $having[] = 'count(auth_ldap_failed_id) <= '.$search_infos['search_count_end'];
  
  $order_by = NULL;
  $group_by = 'GROUP BY failed_login, failed_ip';
  
  if(count($where) == 0)
    $where = true;
 
  $order_by = $sort_way&&$sort_by ? "ORDER BY $sort_by $sort_way":'';
  
  //having car aggregate
  $extras[] = $group_by;
  if(count($having) > 0)
    $extras[] = 'HAVING '.implode(' && ', $having);
    
  $extras[] = $order_by;
    
  $list_failed_auth = authfailed::get(implode(', ', $fields), $where, implode(' ', $extras));
}
else{
  $list_failed_auth = authfailed::default_get();
}

$page_id    = (int)$sub0;
$by         = 40;
$date       = _NOW - 60*60*24*14;

$export_mode = $sub1 == "export";
exyks::$head->title = "Liste des fichiers";

//gestion de la pagination
$pages = dsp::pages(count($list_failed_auth),$by,$page_id,"/?$href//");

if(!$export_mode && is_array($list_failed_auth))
  $list_failed_auth = array_slice($list_failed_auth, $page_id * $by, $by);

///preparation du template
$cols = array(
    "failed_ip"     => "<th>IP</th>",
    "failed_login"  => "<th>Login</th>",
    "count"         => "<th>Count</th>",
    "actions"       => "<th>Actions</th>",
    "checkbox"      => "<th style='width:15px'><input type='checkbox' id='failed_list'/></th>",
);

//export excel
if($export_mode) {
    unset($cols['actions']);
    unset($cols['checkbox']);

    exyks_renderer_excel::process();
}

$headers = new data_headers($cols);
