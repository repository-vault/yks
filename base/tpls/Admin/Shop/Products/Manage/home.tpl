<box theme="&pref.dft;" id="product_manage" caption="Ajout d'un produit" options="modal,fly,close,reload">



<ks_form ks_action="product_manage" submit="Sauvegarder">
<fields caption="Informations générales">
<em>Les champs vides seront enregistrés NULL</em>

    <field type="product_name" title="Nom du produit" value="<?=$product_infos['product_name']?>"/>
    <field type="product_descr" title="Description" style="height:130px"><?=$product_infos['product_descr']?></field>
    <field type="product_descr_long" title="Description(suite)" style="height:130px"><?=$product_infos['product_descr_long']?></field>
    <field type="product_price" title="Prix" value="<?=$product_infos['product_price']?>"/>
</fields>

<fields caption="Emplacement">
<i>Le produit sera accessible à partir de l'utilisateur suivant</i>

    <field title="Utilisateur">
        <box src="/?/Admin/Users/check_name//user_id;&user_id;"/>
    </field>

<i>Le produit sera accessible à partir de l'utilisateur suivant</i>

    <field title="Produit parent">
        <select name="parent_id"><option value=''>-- Produit racine--</option>
            <?=dsp::dd($products_roots_tree, array('col'=>'product_name','selected'=>$product_infos['parent_id'] ))?>
        </select>
    </field>

    <field title="Type de derivation" type="product_relation_type" null="&option.choose;" value="<?=$product_infos['product_relation_type']?>"/>
</fields>
</ks_form>

<hr class="clear"/>

<?if($product_id){?>
<box src="?/Admin/Yks/Scaffolding/Tables//ks_shop_products_specifications;product_id=&product_id;"/>
<?}?>


</box>