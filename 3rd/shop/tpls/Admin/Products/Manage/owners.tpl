<box>
  <table class="table">
    <tr class="line_head">
      <td>User</td>
      <td>action</td>
    </tr>
    <?
      if($owners_list) foreach($owners_list as $owner_id=>$owner) {
        $actions = array();
        $actions[] = "<img onclick=\"Jsx.action({ks_action:'owner_delete',user_id:{$owner['user_id']}},this,this.title)\" title='Supprimer' alt='trash_icon' src='/?/Yks/Scripts/Contents|path://skin/icons/trash_24.png'/>";
        echo "<tr class='line_pair'>";
        echo "<td>".users::print_path($owner['user_id'])."</td>".
        "<td>".join(' - ',$actions)."</td>";
        echo "</tr>";
      }
      else echo "<tfail>No owner for this product.</tfail>";
    ?>
  </table>

  <ks_form ks_action="owner_add" submit="Ajouter">
    <field title="Utilisateur">
      <input style="width:300px" type="text" id="user_id_CP" name="user_id"/>
    </field>
  </ks_form>


  <domready src="/?/Yks/Scripts/Js|path://3rd/usage/TextboxList.js">
    //<![CDATA[
    var categLst = new WTextboxList('user_id_CP', {
      unique: true,
      max: 1,
      plugins: {
        autocomplete: {
          minLength: 2,
          queryRemote: true,
          remote: {url: '?<?=$href_fold?>/owners//search'}
        }
      }
    });
    //]]>
  </domready>
</box>