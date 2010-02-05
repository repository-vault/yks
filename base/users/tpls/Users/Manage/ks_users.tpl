<box theme="&pref.dft;" style="width:400px" options="modal,fly,close, reload" id="user_infos" caption="Modifier un utilisateur">

Vous êtes ici : <?=$parent_path?><br/>

<ks_form ks_action="user_edit">

<?

foreach($dsp_profile as $field_name=>$field_type){
echo "<field title=\"&user_profile.$field_name;\" type='$field_type' name='$field_name' value=\"{$user_infos[$field_name]}\"/>\n";
}
?>

<hr/>

<toggler caption="Propriétés héritées :" class="closed">
<?
foreach($dsp_profile as $field_name=>$field_type){
    $checked=is_null($user_infos[$field_name])?"checked='checked'":'';
    echo "<field title='&user_profile.$field_name;'>
        <input type='checkbox' $checked name='fields_inherit[{$field_name}]'/>
    </field>";
}
?>

<domready>
$$('input[type=checkbox][name^=fields_inherit]').addEvent('change',function(){
    var flag=this.name.match(/\[(.*)\]/)[1],sel ='[name='+flag+'],[name="'+flag+'[]"]', el=$E(sel, this.getBox().anchor);
    if(el) el.set('disabled', this.checked);
});$$('input[type=checkbox][name^=fields_inherit]').fireEvent('change');

</domready>
</toggler>

<hr/>

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

foreach($tables_children_list as $table_children_name=>$table_children){
  echo "<toggler class='closed users_table_child' caption='$table_children_name'>";
    echo "<box  src='/?/Admin/Yks/Scaffolding/Tables//$table_children_name;user_id=$user_id'/>";
  echo "</toggler>";

}
?>

<?=$tables_children_list?"<hr/>":''?>

<a class='float_left' href='<?="/?/Admin/Users//$prev_user_id/Manage"?>'>&lt;&lt; Utilisateur précédent</a>
<a class='float_right' href='<?="/?/Admin/Users//$next_user_id/Manage"?>'>Utilisateur suivant &gt;&gt;</a>

</box>