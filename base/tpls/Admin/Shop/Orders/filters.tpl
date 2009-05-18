<box theme="&pref.dft;" caption="Filtrer" class="float_right">
<ks_form ks_action="filters_set" submit="Appliquer les filtres">

Trier par :

<select name='order_by'>
    <option value=''>-- Choisissez --</option>
    <?=dsp::dd($fields_list)?>
</select>
<hr/>
Filtre : 

<select name='filter_by[0]'>
    <option value=''>-- Choisissez --</option>
    <?=dsp::dd($fields_list)?>
</select>

<div id='filter_by_value' class='box'>

</div>

<domready>
$N('filter_by[0]').addEvent('change',function(){
    var data= {
        target:'filter_by_value',
        ks_action:'get_field_def',
        field_type:this.value
    }; Jsx.action(data,this);

});

</domready>
<button  name="ks_action[filters_remove]">Supprimer les filtres</button>
</ks_form>
</box>