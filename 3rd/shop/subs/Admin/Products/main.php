<?php

tpls::export(compact('user_id'));

sql::select("ivs_shop_products_owners",true,"user_id");
$products_owners = array_unique(array_filter(sql::brute_fetch(false, "user_id")));
$products_users_tree = array();

    //choppe tt les parents
$users = sql_func::get_parents($products_owners, "ks_users_tree", "user_id");

sql::select("ks_users_tree", array('user_id'=>$users));
$res = linearize_tree( make_tree(sql::brute_fetch('user_id', "parent_id"), USERS_ROOT));

    //desactive les noeuds simplement traversés
$disabled = array_fill_keys(
    array_diff($users, $products_owners, array(USERS_ROOT)),
    array('disabled'=>'disabled'));

$products_users = array_merge_numeric(array_merge_numeric($res,$disabled), users::get_infos($users));
