<box theme="&pref.dft;" caption="Browser">
<ks_form ks_action='browse' submit="Browse">
    <field type="string" title="Type" name="myks_type" value="<?=$myks_type?>"/>
    <field type="string" title="Value" name="myks_value" value="<?=$myks_value?>"/>
    <field type="bool" null="&option.choose;" title="Recurse" name="recurse" value="<?=$recurse?>"/>
</ks_form>

<div style="font-family:monospace;white-space: pre-wrap;width:800px;overflow:auto"><?php
if($myks_data)
  foreach($myks_data as $table_name => $lines) {
    $table_xml    = yks::$get->tables_xml->$table_name;
    $table_fields = fields($table_xml); $fields_birth = array();
    foreach(array_unique($table_fields) as $field_type) {
        $field_myks = yks::$get->types_xml->$field_type;
        if(! $birth = (string) $field_myks['birth']) continue;
        $fields_birth[$field_type] = $birth;
    }

    foreach($lines as $i=>$line){
        foreach($line as $field_name => $value) {
            $field_type = $table_fields[$field_name];
            $str = "$value";
            if(isset($fields_birth[$field_type]))
                $str = "<a href='/?&href;//$field_type;$value'>$value</a>";
            $myks_data[$table_name][$i][$field_name] = $str;
        }
    }
}

print_r($myks_data);

?></div>

</box>