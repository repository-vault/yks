<box theme="&pref.dft;" options="fly,close,modal" caption="Ajouter un item"  style="width:1024px;" id="item_manage">


 <ks_form submit="Creer" ks_action="item_manage">

  <div style="width:600px;float:left">

      <p><span>Clée de l'item</span><input <?=$item_exists?'disabled="disabled"':''?> type="text" name="item_key" value="<?=
$item_infos['item_key']?$item_infos['item_key']:$sshot_infos['sshot_prefix']?>"/></p>

    <field title="Tags (s)">
        <select multiple='multiple' size='6' name="tag_id[]"><?=dsp::dd($tags_list, array('selected'=>$item_infos['tag_id']))?></select><br/>
        <a target="manage_tags" href="/?&locale_fold;/Manage/Tags/manage">[+] Ajouter un tag</a><br/>
        <a target="_top" href="/?&locale_fold;/Manage/Tags/list">[o] Gerer les tags</a><br/>
    </field>

    <p><span>Commentaire</span></p>
    <field type='text' name='item_comment' style='height:100px;width:100%'/>
    <p><span>Traduction US</span></p>
    <field type='text' name='value_us' style='height:100px;width:100%'><?=specialchars_encode($item_infos['value'])?></field>
 </div>
    
</ks_form>


</box>

