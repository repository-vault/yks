<?

$mode = $sub0;

if(!$item->item_tag)
    throw rbx::error("There is no available sshot for this item");


if($mode == "embeded")
    return; //continue, show template


if($mode == 'full'){
    $item->get_pict(true);
} else $item->get_pict();

