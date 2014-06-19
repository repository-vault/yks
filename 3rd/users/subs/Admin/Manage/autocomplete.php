<?php
$result = array();

$where = sql::make_search_blob("user_id || user_name || user_mail", $_POST['search']);

$cols = array("user_id", "user_name", "user_mail");
$tables = array(
  'ks_users_profile',
  'user_id' => 'ks_users_tree'
);
sql::select($tables, $where, join(',', $cols), "GROUP BY ".join(',', $cols)." ORDER BY user_name");

$sql_results = sql::brute_fetch();
foreach($sql_results as $row){
  $result[] = array($row['user_id'], join(' - ', array_filter($row)));
}

echo json_encode($result);die; // AJAX !
