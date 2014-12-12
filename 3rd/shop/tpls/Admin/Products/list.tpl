<box theme="&pref.dft;" caption="Liste des produits" id="products_list">
  <div><?=$pages?></div>
  <table class='table'>
    <tr class='line_head'>
      <th>#</th>
      <th>Product name</th>
      <th>Product Price</th>
      <th>Category</th>
      <th>Action</th>
    </tr>

    <?php
      foreach($products_list as $product_id => $product_infos){
        if($product_infos->parent_id != null) continue;

        $actions = "";
        $actions .="<div class='shop_icon shop_icon_trash'
  onclick=\"Jsx.action({ks_action:'product_delete',product_id:$product_id},this,this.title)\" title='Supprimer ce produit'>&#160;</div>";

        $actions .="<a class='shop_icon shop_icon_edit' href='/?&href_base;/Manage//$product_id' target='product_manage' title='Editer le produit'>&#160;</a>";
        $actions .="<a class='shop_icon shop_icon_clone' onclick='if(confirm(\"Cloner le produit ?\")) Jsx.open(\"/?&href_base;/Manage//$product_id;clone\",\"product_manage\",this)' target='product_manage' title='Cloner le produit'>&#160;</a>";

        $categs = array();
        if($product_infos['products_categories']) foreach($product_infos['products_categories'] as $category)
          $categs[] .= $category->category_name ? $category->category_name : $category->category_id;

        echo "<tr class='line_pair'>
              <td>[$product_id] {$product_infos['product_ref']}</td>
              <td>{$product_infos['product_name']}</td>
              <td align='middle'>{$product_infos['product_price']}</td>
              <td>".join("<br/>", $categs)."</td>
              <td>$actions</td>
              </tr>";

        if($product_infos->product_declinaisons) foreach($product_infos->product_declinaisons as $variation) {
          $actions = "";
          $actions .= "<div class='shop_icon shop_icon_trash'
onclick=\"Jsx.action({ks_action:'product_delete',product_id:{$variation['product_id']}},this,this.title)\" title='Supprimer ce produit'>&#160;</div>";
          $actions .= "<a class='shop_icon shop_icon_edit' href='/?&href_base;/Manage//{$variation['product_id']}' target='product_manage'>&#160;</a>";

          $categs = array();
          if($variation['products_categories']) foreach($variation['products_categories'] as $category)
            $categs[] .= $category->category_name ? $category->category_name : $category->category_id;

          echo "<tr class='line_pair' style='color:#9c9c9c;'>
                <td>∟[{$variation['product_id']}] {$variation['product_ref']}</td>
                <td>{$variation['product_name']}</td>
                <td align='middle'>{$variation['product_price']}</td>
                <td>".join("<br/>", $categs)."</td>
                <td>$actions</td>
                </tr>";
        }
      } if(!$products_list) echo "<tfail>Aucun produit à ce niveau</tfail>";

    ?>

  </table>
  <div><?=$pages?></div>
</box>
