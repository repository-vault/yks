<box theme="&pref.dft;" style="width:400px" options="modal,fly,close, reload" id="user_infos" caption="Modifier un utilisateur">

Vous êtes ici : <?=$parent_path?><br/>

<ks_form ks_action="user_edit" class="nullable">

<?

foreach($dsp_profile as $field_name=>$field_type){
    $disabled = is_null($user_infos[$field_name])?"disabled='disabled'":'';
    //!!
    if($field_type == "text") 
        echo "<field $disabled title=\"&user_profile.$field_name;\" type='$field_type' name='$field_name'>".specialchars_encode($user_infos[$field_name])."</field>\n";
    else  echo "<field $disabled title=\"&user_profile.$field_name;\" type='$field_type' name='$field_name' value=\"{$user_infos[$field_name]}\"/>\n";
}
?>

<hr class="clear"/>


<toggler caption="Authentification" class="closed">

<field title="&user_profile.auth_type;"><select name="auth_type" onchange="<?="Jsx.open('/?$href_fold/auths/'+\$(this).get('value')+'//$user_id','auth_type_box',this)"?>">
    <option value='none'>--Aucune connexion autorisée--</option>
    <?=dsp::dd('auth_type',$user_infos['auth_type'])?>
</select>
</field>

<box id='auth_type_box' <?=($user_infos['auth_type']?"src='/?$href_fold/auths/{$user_infos['auth_type']}//$user_id'":'')?>/>

</toggler>



<hr/>

<button class='float_left' ks_action="su" confirm="this.alt">Se connecter avec ce compte</button>
<button class='float_right'>Enregistrer</button>
<clear/>
</ks_form>

<hr/>

<?
/* En attente correction YKS*/
foreach($tables_children_list as $table_children_name=>$table_children){
  echo "<toggler class='closed users_table_child' caption='$table_children_name'>";
    echo "<box  src='/?/Admin/Yks/Scaffolding/Tables//$table_children_name;user_id=$user_id'/>";
  echo "</toggler>";

}
/**/
?>

<?=$tables_children_list?"<hr/>":''?>

<a class='float_left' href='<?="/?/Admin/Users//$prev_user_id/Manage"?>'>&lt;&lt; Utilisateur précédent</a>
<a class='float_right' href='<?="/?/Admin/Users//$next_user_id/Manage"?>'>Utilisateur suivant &gt;&gt;</a>

</box>