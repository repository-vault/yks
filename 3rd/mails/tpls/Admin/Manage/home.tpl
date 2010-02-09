<box theme="&pref.dft;" id="mail_manage" caption="<?=$mail_id?"Modifier un mail":"Nouveau mail"?>">
    <p class="align_right"><a href="/?/Admin/Mails">Retour à la liste des mails</a></p>

<ks_form ks_action="mail_manage" submit="Enregistrer">
<fields caption="Informations générales">
    <field type="mail_name" title="Clée du modèle" value="<?=$mail_infos['mail_name']?>"/>
    <field type="mail_title" title="Titre du mail" value="<?=$mail_infos['mail_title']?>"/>
    <field type="textarea" name="mail_descr" title="Description" ><?=$mail_infos['mail_descr']?></field>
    <field type="textarea" name="mail_cc" title="CC"><?=$mail_infos['mail_cc']?></field>


<?if(!$mail_first_part){?>
    <field type="mime_content_type" title="Type de contenu"/>
<?}else{
    echo "<p>
            <span>Type de contenu :</span>
            <var>{$mail_infos['first_part_infos']['content-type']}</var>
        </p>";
}?>

</fields>


</ks_form>

<fieldset><legend>Contenu</legend>
<?if($mail_first_part){
    echo "<box src='/?$href_fold//$mail_id/Parts//$mail_first_part'/>";
}?>
</fieldset>

</box>