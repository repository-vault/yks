<box theme="fieldset" caption="Rechercher" id="search_failed_auth" style="width:95%" >
  <ks_form ks_action="search_failed_auth" style="width:100%">
  <table style='margin:5px; border-spacing: 3px;'  id="search_criteria" >
    <tr>
      <td>Login</td>
      <td><input type="text" name='search_login'/></td>
      <td colspan="2"></td>
    </tr>
    <tr>
      <td>Ip</td>
      <td><input type="text" name='search_ip'/></td>
      <td colspan="2"></td>
    </tr>
    <tr>
      <td>Count entre</td>
      <td><input type="text" name='search_count_start'/></td>
      <td>et</td>
      <td><input type="text" name='search_count_end'/></td>
    </tr>
    <tr>
      <td>Trier par </td>
      <td>
        <select name="sort_by">&select.choose;
        <?php
          foreach($sort_fields as $key => $value){
            echo '<option value="'.$key.'">'.$key.'</option>';
          }
        ?>
        </select>
        <select name="sort_way">
          <option value="ASC">Asc</option>
          <option value="DESC">Desc</option>
        </select>
      </td>
      <td colspan="2"></td>
    </tr>
  </table>
  <button class="float_right">Rechercher</button>
</ks_form>
</box>