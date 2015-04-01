<box>
<ks_form class="box" ks_action="item_save" id="trad_save">

<table style='width:100%' class="table items_list" id="items_list" cellspacing="0">
<tr class='line_head'>
    <th style='width:12em'>Item #</th>
    <th style='width:5em'>Language</th>
    <th style='width:400px'>Translation US</th>
    <th style='width:400px'>Translation</th>
    <th>Notes</th>
</tr>

<style>
.domain {
  font-size: 9px;
  font-style: italic;
}
</style>
<?php

$tab = 1;

$items = locale_item::from_ids(array_extract($items_list, 'item_key', true));

foreach($items_list as $item_infos){ $tab++;


    $item_key  = $item_infos['item_key'];
    $lang_key  = $item_infos['lang_key']; //TODO : 
    $lang_str = $languages[$lang_key]['lang_code'].'-'.$languages[$lang_key]['country_code'];    
    
    $value     = $item_infos['value'];
    $value_us  = $item_infos['value_us'];
    
    $item_infos = $items_infos[$item_key];

    $item_safe = trim(base64_encode($item_key),"=");
    $height    = max(strlen($value_us)/70,min(strlen(value)/60,15),3);
    $tags = "<br/>". mask_join(' - ', array_extract($items[$item_key]->tags, "tag_name"), '<a target="sshot_item" href="/?/Admin/Locale/Manage/Tags//%2$s/tag">%1$s</a>');
    
    $item_integration = "";
    if(bool($item_infos['item_sshot']))
        $item_integration .= "<a target='sshot_item' href='/?&href_fold;/Manage/Items//$item_safe/pict//embeded'><img src='/?&href_fold;/Manage/Items//$item_safe/pict' alt='integration'/></a>";

    if($item_infos['item_comment'])
        $item_integration .=  (bool($item_infos['item_sshot'])?"<hr/>":"")
                             .specialchars_encode(strip_replace($item_infos['item_comment']));
    if(!$item_integration)
        $item_integration= "-";
    if(!auth::verif("yks", "admin"))
        $tags = "";

echo "<tr class='line_pair' item_pict='$item_pict' item_safe='$item_safe' item_key='$item_key' lang_key='$lang_key'>
    <td class='item_key'>".dsp::element_truncate($item_key,16,"span")."<div class='domain'>{$locale_domains_list[$lang_domains[$lang_key]['locale_domain_id']]['locale_domain_name']}</div>$tags</td>
    <td>$lang_str</td>
    <td>".specialchars_encode($value_us)."</td>
    <td><textarea tabindex='$tab' rows='$height' name='items_vals[{$item_safe}][{$lang_key}]'>".specialchars_encode($value).XML_EMPTY."</textarea></td>
    <td>$item_integration</td>
  </tr>";
} if(!$items_list) echo "<tfail>No element found</tfail>";


?>

</table>
<domready>
$$('.item_key').addEvent('click', function(){
    if(this.opened) return;
    var d = $n('input', {type:'text',value:this.getElement('span').get('title') || this.getElement('span').get('text') });
    this.getElement('span').empty().adopt(d);
    this.opened  = true;
});

$('items_list').getElements('textarea')
    .addEvent('reset', function(){ this.removeClass('changed').store('base', this.value); })
    .addEvent('blur', function(){
        if(this.value == this.retrieve('base')) return; //nothing to do
        var me = (/^items_vals\[(.*?)\]\[(.*?)\]$/).exec(this.name),
            data = $H({ks_action:'item_save','items_vals':{}}), tmp={};
        if(!me) return false;
            //[item_key][lang_key] = value;
        tmp[me[2]] = this.value; data.items_vals[me[1]] = tmp;
        Jsx.action(data, this);
      })
    .addEvent('change', function(){this.addClass('changed'); })
    .fireEvent('reset');

</domready>

<p class="end">Pages : <?=$pages_str?> - <i>If you think some elements were not automatically saved, you can <button>force data saving</button></i></p>
</ks_form>
</box>