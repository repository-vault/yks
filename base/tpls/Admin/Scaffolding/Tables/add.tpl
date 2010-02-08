<box>
<ks_form ks_action="add" submit="Ajouter" style="width:300px">

<?
if(!$batch_mode) echo "<a href='/?$href//1'>Batch mode</a>";
else echo "<a href='/?$href//'>List mode</a>";


$table_fields = array_diff_key($table_fields, $initial_criteria);

foreach($table_fields as $field_name=>$field_type){
    echo dsp::field_input($field_type, $field_name, false, $batch_mode);
}

?>


</ks_form>
</box>