<?


if($action == "item_trash") try {
    $item_key = (string)$_POST['item_key'];
    $sucess = $locale_tag->trash_item($item_key);
    if(!$sucess)
        throw rbx::error("Unable to delete");
    jsx::$rbx = false;
} catch(rbx $e){}

if($action == "item_save") try {
    $item_create = bool($_POST['item_create']);
    $item_key    = (string) $_POST['item_key'];
    list($item_x, $item_y, $item_w, $item_h) = explode(';', $_POST['item_coords']);
    $position = compact('item_x', 'item_y', 'item_w', 'item_h');

    $data = compact('item_key');
    if(array_filter($position)) $data = array_merge($data, $position);
    

    $success = $locale_tag->add_item($data, $item_create);
    if($success)
        rbx::ok("Enregistrement reussi");
    else throw rbx::error("Une erreur est survenue lors de l'enregistrement de l'item");

 } catch(rbx $e){}

$items_list = $locale_tag->items_list;

