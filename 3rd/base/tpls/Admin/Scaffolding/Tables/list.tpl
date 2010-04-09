<box>
Table : <?=$table_name?>

<?if($mode=="vertical"){?>

<table class='table'>
<tr class='line_head'>
    <?foreach($table_fields as $field_name=>$field_type){
        echo "<th>$field_name</th>";
    }?>
    <th>Actions</th>
</tr>
<?

if($data) foreach($data as $line){

  echo "<tr class='line_pair'>";
    foreach($table_fields as $field_name=>$field_type){
        $value = $line[$field_name];
        echo "<td>".dsp::field_value($field_type, $value)."</td>";
    }

    $actions = "";

    $uid = "";
    foreach($table_keys as $key_name=>$key_type)
        $uid[$key_name] = $line[$key_name];
    $do = json_encode(array('ks_action'=>'delete', 'uid'=>$uid));

    $actions.="<img alt='trash_icon' onclick='Jsx.action($do, this, \"Supprimer\")' src='/css/Yks/icons/trash_24'/>";
    echo "<td>$actions</td>";


  echo "</tr>";
} else echo "<tfail>No data here</tfail>";


?>
</table>

<?=$pages_str?>

<?} else {
  

if($data) {
    echo "<ks_form ks_action='update' submit='Update' class='nullable'>";

  foreach($table_fields as $field_name=>$field_type){
    if($field_name == $key_name) continue;
    $disabled = is_null($data[$field_name]) ? "disabled='disabled'" : "";
    echo "<field $disabled title='$field_name' type='$field_type' name='$field_name' value='{$data[$field_name]}'/>";
  }
    echo "</ks_form>";

    echo "<ks_form ks_action='delete' submit='Supprimer (Heritage)'><input type='hidden' name='uid[{$key_name}]' value='$key_value'/></ks_form>";
    echo "<hr class='clear'/>";
}else {
    echo "<p><i>Aucune donnée</i></p>";
}


}
?>


</box>