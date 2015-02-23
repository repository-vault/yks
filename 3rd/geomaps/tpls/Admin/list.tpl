<box>

  <table class='table'>
    <tr class='line_head'>
      <td>#</td>
      <td>Map</td>
      <td>User group</td>
      <td>Actions</td>
    </tr>
    <? if($maps_list) foreach($maps_list as $map_id=>$map) {
        $actions = "<a href='?$href_fold//$map_id/edit'>Open</a>";
        echo "<tr class='line_pair'>
        <td>$map_id</td>
        <td>{$map['map_name']}</td>
        <td>{$map['user_id']}</td>
        <td>$actions</td>
        </tr>";
      }
      else echo "<tfail>No maps</tfail>";
    ?>
  </table>
</box>