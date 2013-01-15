<box theme="&pref.dft;" options="fly,close,modal" caption="Gestion des item"  style="width:1024px;" id="item_manage">
  <style>
  .rte_container {
    height: 165px !important;
  }
  </style>

 <ks_form submit="Save" ks_action="item_manage">

  <div style="width:600px;float:left">

      <p><span>Clée de l'item</span><input <?=$item?'disabled="disabled"':''?> type="text" name="item_key" value="<?=
$item['item_key']?$item['item_key']:$sshot_infos['sshot_prefix']?>" style="width: 350px;"/></p>

    <field title="Tags (s)">
        <select multiple='multiple' size='25' name="tag_id[]"><?=dsp::dd($tags_list, array('selected'=>array_extract($item->tags,'tag_id')))?></select><br/>
        <a target="manage_tags" href="/?&locale_fold;/Manage/Tags/manage">[+] Ajouter un tag</a><br/>
        <a target="_top" href="/?&locale_fold;/Manage/Tags/list">[o] Gerer les tags</a><br/>
    </field>

    <p><span>Commentaire</span></p>
    <field type='text' name='item_comment' style='height:160px;width:99%'><?=specialchars_encode($item['item_comment'])?>&XML_EMPTY;</field>
    <p><span>Traduction US</span></p>
    <field type='text' name='value_us' style='height:160px;width:99%'><?=specialchars_encode($value_en_us)?>&XML_EMPTY;</field>

 </div>
    
</ks_form>

</box>

