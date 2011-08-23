<box>
  <div  id="products_list_tools">

    Voir à partir de : <br/>
    <select name="product_owner">
      &select.choose;
    <?=dsp::dd($products_users, array('col'=>'user_name','selected'=>$user_id))?></select>
    <input type="checkbox" name="with_parent"  <?=$with_parent ? "checked=\"checked\"" : ""?>/> With parent
    <button onclick="Jsx.open('/?&href_fold;//'+$N('product_owner').value+';'+($N('with_parent').checked ? 1:0), false, this)">Show</button>
    <hr/>

    <button href="/?&href_base;/Manage" target="product_manage">Ajouter</button>
  </div>

  <box src="/?&href_base;/list" id="products_list"/>

</box>