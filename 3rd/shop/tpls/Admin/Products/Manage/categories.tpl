<box theme="&pref.dft;" caption="Assignation des categories">

<ks_form ks_action="products_categories_assign" submit="Assigner">


<select name="products_list[]" size="10" multiple="multiple">
<option value=''>-- None --</option>
<?=dsp::dd($products_infos,array('col'=>'product_name'))?>

</select>


<select name="categories_list[]" size="10">
<option value=''>-- None --</option>
<?=dsp::dd($categories_infos,array('mykse'=>'category_id'))?>

</select>


<p>Mode d'assignation</p>

<input  type="radio" name="assignation_way" value="products_to_categs"/> Produits =&gt; Categorie<br/>
<input checked="checked" type="radio" name="assignation_way" value="categs_to_products"/> Produits &lt;= Categorie<br/>



</ks_form>


<domready>
var load_current = function(way){
    var mode = $E("input[name=assignation_way][checked]").value;
    if(mode!=way) return;
    var dest = $N((way=="categs_to_products")?"products_list[]":"categories_list[]");
    var values= this.getElements('[selected]').get('value');
    http_lnk('post', href, {ks_action:'dd_load',way:way,values:values },function(vals){
        vals= vals?vals.map(String):[];
        $A(this.options).each(function(el){ el.selected =  vals.contains(el.value); } );
    }.bind(dest));
}
$N("categories_list[]").addEvent('change',
    load_current.pass('categs_to_products',$N("categories_list[]")));
$N("products_list[]").addEvent('change',
    load_current.pass('products_to_categs',$N("products_list[]")));
</domready>


</box>