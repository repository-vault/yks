<?


        //there is NO WAY birth field are altered/inserted, just ignore them
$table_fields = array_diff_key($table_fields, $birth_field);

$batch_mode = (bool)$sub0;
if($action=="add")try{
    
    $data = array_sort($_POST, array_keys($table_fields));
    $data = array_merge($data, $initial_criteria);


    if($batch_mode) {
        $data_start = array_intersect_key($data, $initial_criteria);
        $data       = array_diff_key($data, $initial_criteria);
        if(count($data)!=1)
            throw rbx::error("Batch mode is not available for complex data");
        list($key, $data_vals) = each($data);


        sql::delete($table_name, $data_start);
        foreach($data_vals as $val)
            sql::insert($table_name, array_merge($data_start, array($key=>$val)));

    } else {
            //check for multi depth in $data
        $multi_depth = array_filter($data, 'is_array');
        if($multi_depth) {
            $msg = "I cannot insert multi-dimmensional data.";
            if($multi_depth_criteria) $msg .= "Please simplify your criteria.";
            throw rbx::error($msg);
        }
        $data = mykse_validate($data, $table_fields);
        $res = sql::insert($table_name, $data);
        if(!$res ) throw rbx::error("Unable to insert data");
    }


    jsx::js_eval("Jsx.open('/?$href_base/list', '$unique_scaffold_key_list', this)");
}catch(rbx $e){}
