<?
$upload_title = $locale_tag->sshot_file?"Remplacer le fichier":"Attacher le fichier";
?>

<?=$locale_tag->tag_name?>
<box options="modal,fly,close" theme="&pref.dft;" caption="&locale_tag.&mode;;" style="width:500px;">
    <ks_form id="locale_tag_form" ks_action="tag_&mode;" submit="Save">
      <fields>
        <field title="Nom" type="title" name="tag_name" value="<?=$locale_tag->tag_name?>"/>

        <field title="Project"><select name="project_id">&select.choose;
    <?=dsp::dd($projects_dsp,array('selected'=>$locale_tag->project_id, 'mykse'=>'project_id', 'pad'=>"|&#160;&#160;&#160;"))?>
        </select></field>

        <field title="Tag parent"><select name="parent_tag">&select.choose;
            <?=dsp::dd($tags_list,array('selected'=>$locale_tag->parent_tag))?>
        </select></field>


        <field title="Préfixe des items" type="title" name="tag_prefix" value="<?=$locale_tag->sshot_prefix?>"/>

        <field title="Fichier" type="upload" upload_type="locale_tag_sshot" upload_title="<?=$upload_title?>" name="sshot_file"/>

      </fields>
    </ks_form>
</box>