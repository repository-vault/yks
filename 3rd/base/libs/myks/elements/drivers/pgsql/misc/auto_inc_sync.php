<?php

class pgsql_auto_inc_sync {

  static function doit($tables_xml, $types_xml) {

    $done = 0;
    foreach($types_xml  as $mykse_type=>$mykse_xml) {
        $birth = (string) $mykse_xml['birth'];
        $base_type = (string) $mykse_xml['type'];

        if(!$birth) continue;
        if($base_type != 'int') {
            rbx::ok("$birth $base_type != 'int', skipping");
            continue;
        }

            //mykse_type is not always the table_field, but it's the only on field of this type
        $table_keys = array_flip(fields($tables_xml->$birth, "primary")); //liste des primary
        $primary_field = $table_keys[$mykse_type];

        $sql_max = sql::value($birth, "true", "MAX($primary_field)");
        $auto_inc = sql::auto_indx($birth);
        if($sql_max == $auto_inc) {
            rbx::ok("$birth ($base_type) up to date #$sql_max, skipping");
            continue; //nothing to do
        }

        $table_infos = sql::resolve($birth);
        $table_name  = $table_infos['name'];

        $data = array(
            'increment_value'   => $sql_max,
            'increment_lastval' => $sql_max,
        );

        $verif_key = array(
            'increment_key'   => $mykse_type,
            'increment_table' => $table_name,
        );

        $done++;
        sql::replace("zks_autoincrement", $data, $verif_key);
        rbx::ok("-- ".SQL_DRIVER." Updating {$mykse_type}($table_name) : $sql_max sequence");

    }
    if(!$done)
        rbx::ok("-- ".SQL_DRIVER." All sequences are up-to-date");

  }


}