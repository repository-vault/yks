<?php

class pgsql_auto_inc_sync {

  static function doit($tables_xml, $types_xml) {

    $done = 0;
    foreach($types_xml  as $mykse_type=>$mykse_xml) {
        $birth = (string) $mykse_xml['birth'];
        $base_type = (string) $mykse_xml['type'];
        if(!$birth) continue;
        if($base_type != 'int') continue;

        $sql_max = sql::value($birth, "true", "MAX($mykse_type)");
        $auto_inc = sql::auto_indx($birth);
        if($sql_max == $auto_inc) continue; //nothing to do

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