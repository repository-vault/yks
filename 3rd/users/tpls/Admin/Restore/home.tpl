<box theme="&pref.dft;" caption="Restore">
<table class='table'>
  <tr class='line_head'>
    <th>Deletion #</th>
    <th>Deletion date</th>
    <th>Deletion user</th>
    <th>Deletion object</th>
    <th>Deletion reason</th>
    <th>Action</th>
  </tr>

<?

foreach($deletions_list as $deletion_id=>$deletion){
  $actions = array();
  $actions[] = "<span onclick=\"Jsx.action({ks_action:'deletion_restore', deletion_id:$deletion_id}, this, this.innerHTML)\">Restore</span>";


  echo "<tr class='line_pair'>
    <td>$deletion_id</td>
    <td>".dsp::date($deletion['deletion_time'], '$Y-$m-$d $H:$i')."</td>
    <td>&user.{$deletion['user_id']};</td>
    <td>{$deletion['mykse_type']}  (#{$deletion['mykse_value']})</td>
    <td>{$deletion['deletion_reason']}</td>
    <td>".join('-', $actions)."</td>
  </tr>";
}if(!$deletions_list)
    echo "<tfail>No deletion</tfail>";



?>

</table>

</box>