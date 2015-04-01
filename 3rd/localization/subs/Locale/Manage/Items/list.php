<?php

$items_list = sess::fetch("items_list_manage");
$items_list = locale_item::from_where(array('item_key'=>$items_list));


if($action == "item_delete") try {
    $item_key = $_POST['item_key'];
    $item = $items_list[$item_key];
    $item->delete();
    unset($items_list[$item_key]);
    jsx::$rbx = false;
} catch(rbx $e){}