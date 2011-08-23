<box>
  <table class="table">
    <tr class="line_head">
      <td>Meta produit</td>
      <td>action</td>
    </tr>
    <?
    foreach($product_meta_products as $meta_product_id=>$meta_product) {
      $actions = array();
      $actions[] = "<img onclick=\"Jsx.action({ks_action:'meta_delete',meta_product_id:{$meta_product['meta_product_id']}},this,this.title)\" title='Supprimer' alt='trash_icon' src='/?/Yks/Scripts/Contents|path://skin/icons/trash_24.png'/>";
      echo "<tr class='line_pair'>";
      echo "<td>".$meta_products_list[$meta_product['meta_product_id']]->meta_product_name."</td>".
           "<td>".join(' - ',$actions)."</td>";
      echo "</tr>";
    }
    ?>
  </table>

  <ks_form ks_action="meta_product_add" submit="Ajouter">
      <field title="Meta produit / regroupement">
        <select name="meta_product_id">
          <?=dsp::dd($meta_products_list, array('col'=>'meta_product_name' ))?>
        </select>
      </field>
  </ks_form>

</box>