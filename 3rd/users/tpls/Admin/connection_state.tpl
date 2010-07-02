

<box theme="&pref.dft;" caption="Etat des connexions utilisateurs" style="width: 100%;">
 
 <div style="width:300px">
 <toggler class="closed" caption="Liste des zones" >
 <ks_form ks_action="set_zones" submit="Show zones">
 <select multiple="multiple" name="access_zones[]">
 <?=dsp::dd($access_zone_list, array('col'=>'access_zone_path'))?>
 </select>
 </ks_form>
 </toggler>
 </div>
 
<table style="width:100%" id="results_list" class="table">
    <tr class="line_head">
      <th style="width: 40px;">#</th>
      <th style="width: 40px;"> </th>
      <th>Connect date</th>
      <th>User name</th>
      <?
      foreach($access_zone_list_dsp as $zone) {
        echo '<th>'.$zone['access_zone_parent'].':<br/>'.$zone['access_zone'].'</th>';
      }
      ?>
    </tr>
<?
$users_list_buff = $users_list;
foreach($users_list as $user_id=>$user){
  
    //On ignore l'utilisateur 'racine', il se connecte à chaque utilisateur anonyme, il n'est donc pas pertinent.
    if($user_id == USERS_ROOT)  continue; 
    if($user['user_type'] != "ks_users") continue;

    $parents_tree = $user['parent_tree'];

    // On récupère les parents, on les reordonnes et on joint sur le tableau des détails
    $path = array_extract(array_sort($users_list,  $parents_tree), "user_name"); unset($path[USERS_ROOT]);//yeah 
    $users_path = join(' &gt; ', array_merge($path, array("<b>{$user['user_name']}</b>")));
    
    $can_auth = (bool)($user['auth_type']);
    $auth = "auth".($can_auth?"":"_disabled");
    $links = "";
    $links .= "<a class='user_icon icon_user_infos' href='/?/Admin/Users//$user_id/Manage' target='user_infos'>&#160;</a>";
    $links .= "<a class='user_icon icon_user_$auth' href='/?/Admin/Users//$user_id/Manage/access' target='user_access'>&#160;</a>";
  
    
    // display des infos générales
    echo
    "<tr class='line_pair'>
        <td>{$user['user_id']}</td>
        <td>$links</td>
        <td>".dsp::date($user['user_connect'], '$d/$m/$Y $H:$i')."</td>        
        <td>{$users_path}</td>";

    // display des droits
    foreach($access_zone_list_dsp as $zone) {
      $str = "";
      $zone_path = $zone['access_zone_path'];
      foreach($access_lvls as $access_lvl) {
        if($user['local_rights'][$zone_path][$access_lvl])
          $str.="<b>$access_lvl</b>,";
        elseif($user['inherited_rights'][$zone_path][$access_lvl])
          $str.="$access_lvl,";
      }$str= trim($str, ',');
      echo "<td>$str</td>";

    }
      
    
    echo "</tr>";

} if(!$users_list) echo "<tfail>No users</tfail>";
?>
</table>

</box>