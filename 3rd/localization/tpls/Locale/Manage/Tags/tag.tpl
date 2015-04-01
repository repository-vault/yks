<box theme="&pref.dft;" options="modal,fly,close" id="tag_edit"  caption="<?=$locale_tag->tag_name?>">
    <a href='/?&href_fold;'>Retour à la liste</a> - <a href='/?&href_ks;'>Recharger l'image</a> <br/>


<div style='<?=$locale_tag->sshot_dims?>' id='img_container'>

<?php
foreach($items_list as $item_key=>$item_infos){
    $props = array(
        'margin-left'=>$item_infos['item_x'].'px',
        'margin-top'=>$item_infos['item_y'].'px',
        'width'=>$item_infos['item_w'].'px',
        'height'=>$item_infos['item_h'].'px',
    ); $props = mask_join(';',$props,'%2$s:%1$s');
    echo "<div class='item' style='$props' key='$item_key'> </div>\r\n";
}

?>
  <?="<img id='sshot_pic' style='position:relative;{$locale_tag->sshot_dims}' src='{$locale_tag->big_url}'/>"?>
</div>


<ks_form ks_action="item_save">
  <input type='hidden' name='item_coords' id='item_coords'/>
  Placer un nouvel item : <input type="text" name="item_key"/> <input type="text" disabled="disabled" id="ghost_coords"/> - 
  <input type="checkbox" name="item_create"/> Création automatique <br/>
  Traduction par défaut (Nouvel item uniquement): <input type="text" name="value_us"/>

  <p class="align_right">
        <button name="ks_action[item_trash]">Supprimer</button>
        <button>Enregistrer</button>
  </p>
</ks_form>


  <domready src="/?/Yks/Scripts/Js|path://yks/Utilities/Imgs/Thumbnailer.js">
    $$("#tag_edit .item").addEvent('click', function(el){
        crop.fireEvent('reset').transform(this);
        $N('item_key', 'tag_edit').value = this.get('key',true);
        crop.tmp = this.dispose();
    });

    var item_key = '<?=$item_stored_key?>';
    var tag_id = <?=$tag_id?>;
    var options  = {maskstyle:{zIndex:10}};



    var crop  = new Thumbnailer($('sshot_pic'), options);
    crop.addEvent('change', function(clip){
        var tmp = $H(clip);
        $N('item_coords').value = [tmp.xl,tmp.yu,tmp.w,tmp.h].join(';');
        $('ghost_coords').value = '('+$N('item_coords').value+')';;
        $N('item_key').focus();
    });
    crop.addEvent('keypress', function(ev){
        alert(ev.keyCode);
    })
    crop.addEvent('reset', function(){
        $N('item_key').value = '';
        if(!crop.tmp) return;
        crop.tmp.inject('img_container', 'top');
        crop.tmp = false;
    });
  </domready>


</box>


