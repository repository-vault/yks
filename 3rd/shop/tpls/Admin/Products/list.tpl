<box theme="&pref.dft;" caption="Liste des produits" id="products_list">

<table class='table'>
  <tr class='line_head'>
    <th>#</th>
    <th>Product name</th>
    <th>Product Price</th>
    <th>Category</th>
    <th>Action</th>
  </tr>

  <?
    foreach($products_list as $product_id=>$product_infos){
      if($product_infos->parent_id != null) continue;

      $actions = "";
      $actions .="<div class='shop_icon shop_icon_trash'  onclick=\"Jsx.action({ks_action:'product_delete',product_id:$product_id},this,this.title)\" title='Supprimer ce produit'>&#160;</div>";

      $actions .="<a class='shop_icon shop_icon_edit' href='/?&href_base;/Manage//$product_id' target='product_manage' title='Editer le produit'>&#160;</a>";
      $actions .="<a class='shop_icon shop_icon_clone' onclick='if(confirm(\"Cloner le produit ?\")) Jsx.open(\"/?&href_base;/Manage//$product_id;clone\",\"product_manage\",this)' target='product_manage' title='Cloner le produit'>&#160;</a>";

      $categs = $product_infos['product_categs'] ? join(',', $product_infos['product_categs']) : '';
      echo "<tr class='line_pair'>
            <td>[$product_id] {$product_infos['product_ref']}</td>
            <td>".dsp::element_truncate($product_infos['product_name'],20,"span")."</td>
            <td>{$product_infos['product_price']}</td>
            <td>$categs</td>
            <td>$actions</td>
            </tr>";

      if($product_infos->product_declinaisons) 
        foreach ($product_infos->product_declinaisons as $variation) {
          $actions = "";
          $actions .="<div class='shop_icon shop_icon_trash'  onclick=\"Jsx.action({ks_action:'product_delete',product_id:{$variation['product_id']},this,this.title)\" title='Supprimer ce produit'>&#160;</div>";
          $actions .="<a class='shop_icon shop_icon_edit' href='/?&href_base;/Manage//{$variation['product_id']}' target='product_manage'>&#160;</a>";
          $categs = $variation['product_categs'] ? join(',', $variation['product_categs']) : '';
          echo "<tr class='line_pair' style='color:#9c9c9c;'>
                <td>∟[{$variation['product_id']}] {$variation['product_ref']}</td>
                <td>".dsp::element_truncate($variation['product_name'],20,"span")."</td>
                <td>{$variation['product_price']}</td>
                <td>$categs</td>
                <td>$actions</td>
                </tr>";

      }

    } if(!$products_list) echo "<tfail>Aucun produit à ce niveau</tfail>";

  ?>

</table>
</box>
