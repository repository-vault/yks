<box>

  <button href='?&href_base;/manage' target='manage_meta_product'>Ajouter un méta-produit</button>

  <table class="table">
    <tr class="line_head">
      <th>#</th>
      <th>name</th>
      <th>action</th>
    </tr>
    <?

      foreach($meta_products_list as $meta_product_id => $meta_product) {

        $actions = array();   
        $actions[] = "<a href='?&href_base;/manage//$meta_product_id' target='manage_meta_product'>edit</a>";
        $actions[] = "<img onclick=\"Jsx.action({ks_action:'meta_product_trash',meta_product_id:$meta_product_id},this,'Delete this meta product')\" src='&COMMONS_URL;/css/Ivs/icons/trash'/>";

        echo "<tr class='line_pair'>";
        echo "<td>{$meta_product['meta_product_id']}</td>".
             "<td>{$meta_product['meta_product_name']}</td>".
             "<td>".join(' - ',$actions)."</td>";
        echo "</tr>";

      }
    ?>
  </table>

</box>