<box>
<div  id="products_list_tools">

Voir à partir de : <br/>
<select onchange="Jsx.open('/?&href_fold;//'+this.value, false, this)">
    &select.choose;
    <?=dsp::dd($products_users, array('col'=>'user_name','selected'=>$user_id))?></select>

<hr/>

<button href="/?&href_base;/Manage" target="product_manage">Ajouter</button>
</div>

  <box src="/?&href_base;/list" id="products_list"/>

</box>