<?php


$ks_shop_orders_xml = yks::$get->tables_xml->ks_shop_orders;


$fields_list = array_combine($fields_list = vals($ks_shop_orders_xml,"field"), $fields_list );

if($action=="order_set")try{

    $order_by = $_POST['order_by'];

    $current_liste->order_by($order_by);

    $current_liste->repage();
}catch(rbx $e){}

if($action=="filters_remove") try{

    $filters=true;
    $current_liste->filters_apply($filters);

    $current_liste->repage();

}catch(rbx $e){}

if($action=="filters_set")try{
    $order_by = $_POST['order_by'];
    $current_liste->order_by($order_by,"DESC");

    $filter_field = first($_POST['filter_by']); //uniquement le 1er pr l'instant ( peu être dyn.)
    $filter_value = $_POST[$filter_field];

    $filters=array( $filter_field => $filter_value );
    $filters = array_filter($filters);
    $current_liste->filters_apply($filters);

    $current_liste->repage();


}catch(rbx $e){}

if($action=="get_field_def") try{
    $field_type = $_POST['field_type'];
    $xpath  ="ks_shop_orders/field[string(.)='$field_type']";
    $tmp = $ks_shop_orders_xml->xpath($xpath); $tmp=$tmp[0];
    if(!$tmp)
        throw rbx::error("Invalide type");

    $type= $tmp['type']?(string)$tmp['type']:$field_type;

    $mykse_xml = yks::$get->types_xml->$type;
    if(!$mykse_xml)
        throw rbx::error("Invalide type");

    jsx::$rbx=false;
    tpls::body("$subs_fold/filter_value");
}catch(rbx $e){}
