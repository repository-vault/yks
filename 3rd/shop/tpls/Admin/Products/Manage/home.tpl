<box theme="&pref.dft;" id="product_manage" caption="Ajout d'un produit" options="modal,fly,close,reload">

  <ks_form ks_action="product_manage" submit="Sauvegarder">

    <fields caption="Informations générales">
      <em>Les champs vides seront enregistrés NULL</em>
      <field type="product_name" title="Nom du produit" value="<?=$product['product_name']?>"/>
      <field type="product_ref" title="Ref du produit" value="<?=$product['product_ref']?>"/>
      <field type="product_price" title="Prix" value="<?=$product['product_price']?>"/>      
      <field title="Produit parent">
        <select name="parent_id"><option value=''>-- Produit racine--</option>
          <?=dsp::dd($products_roots_tree, array('col'=>'product_name','selected'=>$product['parent_id'] ))?>
        </select>
      </field>
    </fields>

    <fields caption="Description">
      <field type="product_descr" title="Description" style="height:130px"><?=specialchars_encode($product['product_descr'])?></field>
      <field type="product_descr_long" title="Description(suite)" style="height:130px"><?=specialchars_encode($product['product_descr_long'])?></field>

    </fields>

  </ks_form>

  <?if($product_id){?>
    <fields caption="Méta produit / regroupements">
      <box src='?&href_base;/meta' />
    </fields>
    <fields caption="Owners">
      <box src='?&href_base;/owners' />
    </fields>  
  <?}?>

  <hr class="clear"/>

  <?if($product_id){?>
    <box src="?/Admin/Yks/Scaffolding/Tables//ks_shop_products_categories;product_id=&product_id;"/>
    <box src="?/Admin/Yks/Scaffolding/Tables//ks_shop_products_specifications;product_id=&product_id;"/>
  <?}?>

</box>