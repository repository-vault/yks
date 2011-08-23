<box>
  <table class="table">
    <tr class="line_head">
      <td>User</td>
      <td>action</td>
    </tr>
    <?
    foreach($owners_list as $owner_id=>$owner) {
      $actions = array();
      $actions[] = "<img onclick=\"Jsx.action({ks_action:'owner_delete',user_id:{$owner['user_id']}},this,this.title)\" title='Supprimer' alt='trash_icon' src='/?/Yks/Scripts/Contents|path://skin/icons/trash_24.png'/>";
      echo "<tr class='line_pair'>";
      echo "<td>".users::print_path($owner['user_id'])."</td>".
           "<td>".join(' - ',$actions)."</td>";
      echo "</tr>";
    }
    ?>
  </table>

  <ks_form ks_action="owner_add" submit="Ajouter">
    <field title="Utilisateur">
      <box src="/?/Admin/Users/check_name//user_id;&user_id;"/>
    </field>
  </ks_form>

</box>