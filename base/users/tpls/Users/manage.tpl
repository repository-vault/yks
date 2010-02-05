

<box theme="&pref.dft;" style="width:400px" options="modal,fly,close" caption="Ajouter un utilisateur">
Vous êtes ici : <?=$parent_path?>
<ks_form ks_action="user_manage" submit="Creer">

 <p><span>Nom</span><input type="text" name="user_name"/></p>
 <p><span>Type</span>
	<select name="user_type"><?=dsp::dd("user_type")?></select>
  </p>

</ks_form>

	
</box>