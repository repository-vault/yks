<box theme="&pref.dft;" caption="Filters" id="products_filter" style="width: 100%">
  <style>
    .textboxlist-bits {
      border: 0 !important;
      background-color: #ffffff;
    }

    label {
      float: left;
      margin-top: 8px;
    }
  </style>
  <div id="products_list_tools">
    <form id="filter_product">
      <fields>
        <field title="Voir à partir de">
          <select name="product_owner" style="margin-left: 32px">
            &select.choose;
            <?= dsp::dd($products_users, array('col' => 'user_name', 'selected' => $user_id)) ?></select>
          <input type="checkbox" name="with_parent"  <?= $with_parent ? "checked=\"checked\"" : "" ?>/> With parent
        </field>
        <field>
          <label>Product Name :</label>
          <input name="product_blob" type="text" style="margin-left: 36px;width:300px"/>
        </field>
        <div class="clear" />
        <field>
          <label style="margin-bottom: 13px">Category Name :</label>
          <box src="/?/Admin/Shop/Categories/autocomplete" id="category_autocomplete" />
        </field>
      </fields>
    </form>
    <hr class="clear" />
    <!--    <button onclick="Jsx.open('/?&href_fold;//'+$N('product_owner').value+';'+($N('with_parent').checked ? 1:0), false, this)" style="float: right;">Filter</button>
    -->
    <button onclick="Jsx.action(Jsx.sendForm('filter', 'filter_product'), this)" style="float: right;">Filter</button>
    <button href="/?&href_base;/Manage" target="product_manage">Ajouter</button>
    <button ext="/?&href_base;/list//;1">Export</button>
  </div>
</box>