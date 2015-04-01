<?php


sql::select("ks_shop_products_list",true, "*,('(#'||product_id||') '||product_name) as product_name");
$products_infos = sql::brute_fetch('product_id');

sql::select("ks_shop_categories_list");
$categories_infos = sql::brute_fetch('category_id');


if($action=="dd_load")try{
    $way  = $_POST['way'];
    $values = is_array($_POST['values'])? $_POST['values'] : explode(',',$_POST['values']);
    $source = $way=="categs_to_products" ? "category_id" : "product_id";
    $dest = $way=="categs_to_products" ? "product_id" : "category_id";

    $where = array($source => $values);
    sql::select("ks_shop_products_categories", $where, "DISTINCT $dest");
    $results = sql::brute_fetch(false,$dest);
    jsx::end($results,true);
}catch(rbx $e){}


if($action=="products_categories_assign")try{
    $way = $_POST['assignation_way'];

    $products_list = array_intersect(
        (array)$_POST['products_list'], array_keys($products_infos));
    $categories_list = array_intersect(
        (array)$_POST['categories_list'], array_keys($categories_infos));

    $verif_delete = $way=="categs_to_products" ? array('category_id'=>$categories_list)
        : array('product_id'=>$products_list);

    sql::delete("ks_shop_products_categories", $verif_delete);

    if(!$products_list)
        throw rbx::error("Veuillez selectionner au moins un produit");

    if(!$categories_list)
        throw rbx::error("Veuillez selectionner au moins une categorie");

    $res_inserts =  0;
    foreach($products_list as $product_id){
            foreach($categories_list as $category_id){
                sql::insert("ks_shop_products_categories", compact("product_id","category_id"));
                $res_inserts++;
            }
    }
    rbx::ok("$res_inserts assignation(s) effectuée(s)");





}catch(rbx $e){}