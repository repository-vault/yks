<box theme="&pref.dft;" style="width:400px" options="modal,fly,close" id="user_infos" caption="Modifier un utilisateur">

Vous êtes ici : <?=$parent_path?><br/>

<ks_form ks_action="user_edit" submit="Enregistrer">
<field title='Utilisateur parent' type='user_id' name='parent_id' value="<?=$parent_id?>"/>
<field title='Nom' type='user_name' value="<?=$user_infos['user_name']?>"/>

<?if($profile_tpls) include $profile_tpls?>
</ks_form>

<a href="<?="/?$href_fold/access//$user_id"?>">gerer les droits</a>

</box>