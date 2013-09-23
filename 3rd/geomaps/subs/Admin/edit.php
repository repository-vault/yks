<?
$action = $_POST['ks_action'];

if($action == "area_add") try {
    $user_id = (int)$_POST['user_id'];
    if(!isset($geomap->users_list[$user_id]))
      throw rbx::error("Please selet a valid user");

    $area_key = $geomap->toggle_user_at((int)$_POST['x'], (int)$_POST['y'], $user_id);
} catch(Exception $e){}