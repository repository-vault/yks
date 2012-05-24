<box theme="&pref.dft;" id="users_list" caption="<?="Utilisateur : ".$user_infos['user_name']?>" style="width:500px">

<?

  $links = "";
  $links .= "<a class='user_icon icon_user_infos' href='/?$href_fold//$user_id/Manage' target='user_infos'>&#160;</a>";
  $links .= "<a class='user_icon icon_user_auth' href='/?$href_fold//$user_id/Manage/access' target='user_access'>&#160;</a>";

?>

<p>Vous êtes ici : <?="$parent_path - $links"?></p>

<?
foreach($addrs_list as $addr_id) {
    echo "<box src='/?$href_fold//$user_id/Manage/Addrs//$addr_id'/>";
}

if($addrs_list)echo "<clear/><hr/>";
?>


<ks_form id="users_manage">
Pages : <?=$pages?><br/>
<table style='width:100%' class='table'>
  <tr class='line_head'>
	<th style="width:30px">#</th>
	<th style="width:32px"> </th>
	<th>Nom</th>
	<th>Type</th>
	<th>Actions</th>
	<th style="width:15px"><input type='checkbox' id='users_ids'/></th>
  </tr>
<tbody>
<?

if($children_infos) foreach($children_infos as $user_id=>$user_infos){

  $actions="";
  if(auth::verif("yks","admin"))
        $actions.="<span class='user_icon icon_user_trash' onclick='user_delete($user_id)'>&#160;</span>";

  $auth_class = pick($user_infos['auth_type'], "auth_disabled");

  $links = "";
  $links .= "<a class='user_icon icon_user_infos' href='/?$href_fold//$user_id/Manage' target='user_infos'>&#160;</a>";
  $links .= "<a class='user_icon icon_user_$auth_class' href='/?$href_fold//$user_id/Manage/access' target='user_access'>&#160;</a>";
  $user_name = $user_infos['user_name'];
  if(!$user_name) $user_name = "&yks.users.unnamed; #$user_id";

echo <<<EOS
<tr class='line_pair'>
	<td>$user_id</td>
        <td>$links</td>
	<td><a href='/?$href_fold//$user_id/list'>$user_name</a></td>
	<td>&user_type.{$user_infos['user_type']};</td>
	<td>$actions</td>
	<td><input type='checkbox' name='users_id[{$user_id}]'/></td>
</tr>
EOS;
} else echo "<tfail>Aucun utilisateur à ce niveau</tfail>";
?>
</tbody>
</table>
Pages : <?=$pages?><br/>

<hr/>

<?if(auth::verif("yks","admin")){?>

Pour la selection : <br/>
Deplacer vers<br/>
<box src="?&href_fold;/check_name//where_id"/>
<br/>
    <button name="ks_action[users_move]">Deplacer</button>
    <button confirm="this.alt" name="ks_action[users_delete]">Supprimer</button>
<?}?>

<div class='align_right'>
<button theme="action"  target="user_manage" href="/?/Admin/Users//<?=$parent_id?>/manage">Ajouter un utilisateur ici</button>
<button theme="action" target="addr_manage" href="/?/Admin/Users//<?=$parent_id?>/Manage/Addrs/manage">Ajouter des coordonnees ici</button>
</div>

</ks_form>

<script>

function user_delete(user_id){
  Jsx.action({
	ks_action:'users_delete',
	user_id:user_id
  }, $('users_manage'), 'Supprimer');
}

</script>
<domready>



$('users_ids').addEvent('click',function(){
	this.getParent('table').getElements('input[name^=users_id]').set("checked",this.checked)
});
</domready>


</box>