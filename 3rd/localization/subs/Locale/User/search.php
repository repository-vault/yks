<?php


$ajax_search_items = array();
$search = $_POST['search'];
$domain_id = (int)$_POST['domain'];

$where = array(
  'locale_domain_id' => $domain_id,
);

$like = array(
  "lang_key ILIKE '%{$search}%'",
  "lang_name ILIKE '%{$search}%'",
);

$where[] = '('.join(' OR ', $like).')';
$cols = array('lang_key', 'lang_name');
$cols = join(', ', $cols);

$res = sql::select(locale::sql_table, $where, $cols, 'Group By '.$cols);

$result = array();


while(($l = sql::fetch())) {
  $result[] = array($l['lang_key'], $l['lang_key'].' '.$l['lang_name'].' - '.$locale_domains_list[$domain_id]['locale_domain_name'] );
}


echo json_encode($result);die; // AJAX !