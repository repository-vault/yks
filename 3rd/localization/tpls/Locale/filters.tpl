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
    if(count($locale_languages)>1){?>
        <span onclick="$('lang_keys').set('multiple','multiple').set('size',5)"> (multiples ?)</span>
    <?}?>
    <br/>
    <select id='lang_keys' name='lang_keys[]'>
        <?=dsp::dd($locale_languages,array('mykse'=>'lang_key'))?>
    </select>
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