<?




if($action=="product_manage") try {

    $data = array(
        'product_price'=>(int)$_POST['product_price'],
        'product_name'=> $_POST['product_name'],
        'product_descr'=>rte_clean($_POST['product_descr']),
        'product_descr_long'=>rte_clean($_POST['product_descr_long']),
        'parent_id'=>(int)$_POST['parent_id'],
        'user_id'=>(int)$_POST['user_id'],
        'product_relation_type'=>$_POST['product_relation_type'],
     ); 

    if($product_id) {
        foreach($data as &$val) if(!$val)$val = null;
        sql::update("ks_shop_products_list", $data, compact('product_id'));
        rbx::ok("Modifications enregistrées");

    } else {
        $data = array_filter($data);
        $product_id = sql::insert("ks_shop_products_list", $data, true);
        rbx::ok("Nouveau produit inséré : $product_id");
        jsx::js_eval(JSX_PARENT_RELOAD);
        jsx::js_eval(JSX_CLOSE);
    }


}catch(rbx $e){}