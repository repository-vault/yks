<box theme="&pref.dft;"  id="trad_filters" caption="Search">

<ks_form ks_action="apply_filters">
<div style="float:left;width:300px;">


Select a project :<br/>
<select name="project_id">
    <?=dsp::dd($projects_dsp,array('mykse'=>'project_id', 'pad'=>"|&#160;&#160;&#160;"))?>
</select><br/>


</div>

<div style="float:left;width:300px;">
<input type="text" name="item_key"/> Item name <br/>
<input type="checkbox" checked="checked" name="strict_search"/> Approximate search<br/>
</div>
<button class="float_right" style="margin-top:55px">Apply filter</button>
</ks_form>
</box>

