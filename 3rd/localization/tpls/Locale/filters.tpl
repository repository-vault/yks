<box>

<box theme="&pref.dft;"  id="trad_filters" caption="Search">

<ks_form ks_action="apply_filters">
<div style="float:left;width:300px;">


Select a project :<br/>
<select name="project_id">
    <?=dsp::dd($projects_dsp,array('mykse'=>'project_id', 'pad'=>"|&#160;&#160;&#160;"))?>
</select><br/>


Select a language : 
<?
if(!count($locale_languages))
    echo "-- No available language, please check your configuration --";
else {
    if(count($lang_display)>1){?>
        <span onclick="$('lang_str').set('multiple','multiple').set('size',5)"> (multiples ?)</span>
    <?}?>
    <br/>
    <select id='lang_str' name='lang_str[]'>
      <?=dsp::dd($lang_display)?>
    </select>
    <br/>    
    <? if(count($locale_domains_list) > 1) { ?>
    Select the domain :  <span onclick="$('locale_domain_id').set('multiple','multiple').set('size',5)"> (multiples ?)</span>
    <br/>
    <select id='locale_domain_id' name='locale_domain_id[]'>
      <?=dsp::dd($locale_domains_list,array('col'=>'locale_domain_name'))?>
    </select>
    <? } else { $domain = reset($locale_domains_list) ?>
    Domain : <i><?=$domain['locale_domain_name']?></i>
    <? } ?>
<?}?>

</div>

<div style="float:left;width:300px;">

<input type="checkbox" checked="checked" name="untranslated_item"/> Show un-translated items<br/>
<input type="checkbox" checked="checked" name="translated_item"/> Show translated items<br/>
<input type="text" name="item_key"/> Item name <br/>
<input type="text" name="item_trad"/> Translation body <br/>
<input type="checkbox" checked="checked" name="strict_search"/> Approximate search<br/>

</div>
<button class="float_right" style="margin-top:55px">Apply filter</button>
</ks_form>
</box>


</box>