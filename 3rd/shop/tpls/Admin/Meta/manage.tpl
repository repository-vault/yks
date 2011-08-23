<box theme="&pref.dft;" caption="Manage Meta product" id="meta_product_manager" options="fly, modal, close">
  <ks_form ks_action="edit_meta_product" submit="Save">
    <field title="Meta product name">
      <input type="text" name="meta_product_name" value="<?=$meta_product->meta_product_name?>"/>
    </field>
  </ks_form> 
</box>