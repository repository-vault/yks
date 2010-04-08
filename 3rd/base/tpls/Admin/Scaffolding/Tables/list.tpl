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
$line = $data[0];


    $key_name  = key($table_keys);
    $key_value = $line[$key_name];

    $actions = "<ks_form ks_action='delete' submit='Supprimer'><input type='hidden' name='uid[{$key_name}]' value='$key_value'/></ks_form>";
if($data) {
  foreach($table_fields as $field_name=>$field_type){
    echo "<p class='clear'><span class='float_left'><b>$field_name</b></span><var class='float_right'>{$line[$field_name]}</var></p>";
  }
    echo "$actions<hr class='clear'/>";
}else {
    echo "<p><i>Aucune donnée</i></p>";
}


}
?>


</box>