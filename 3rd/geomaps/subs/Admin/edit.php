<?
$users_list = users::get_children($geomap->root_user);
$users_list = users::get_infos($users_list);

$action = $_POST['ks_action'];

if($action == "area_add") try {
    $user_id = (int)$_POST['user_id'];
    $area_key = $geomap->toggle_user_at((int)$_POST['x'], (int)$_POST['y'], $user_id);

} catch(Exception $e){}