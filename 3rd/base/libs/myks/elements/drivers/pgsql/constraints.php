<?php
  class myks_constraints extends myks_constraints_base {
    public $key_mask=array("PRIMARY"=>'PRIMARY KEY',  "UNIQUE"=>'UNIQUE', 'FOREIGN'=>'FOREIGN KEY' );

    function sql_infos(){


      $where = $this->parent->table_where();
      $cols = 'constraint_catalog, constraint_schema, constraint_name, table_schema, table_name, constraint_type';
      $cols.=",is_deferrable";
      sql::select("information_schema.table_constraints", $where, $cols);

      $keys = sql::brute_fetch('constraint_name');
      $this->sql_def=array();
      $keys = array_map('array_change_key_case', $keys);

      $usages=array(); $behavior=array();

      $where['constraint_name']   = array_keys($keys);

      $order ="ORDER BY position_in_unique_constraint ASC";

      sql::select("information_schema.key_column_usage", $where, "constraint_name,column_name", $order);
      while($l=sql::fetch())
          $this->sql_def[$l['constraint_name']]['members'][$l['column_name']]=$l['column_name'];
              //une clée est basé sur au moins UNE colonne ( élimine les checks )

      $fkusages = array();
      sql::select("zks_information_schema_fkcolumns", $where);
      foreach(sql::brute_fetch() as $line)
        $fkusages[$line['constraint_name']][$line['remote_column_name']] = $line['column_name'];


      $verif_contraints = array(
          'constraint_name'   => array_keys($this->sql_def),
          'constraint_schema' => $this->parent->name['schema'],
      );


      sql::select("information_schema.constraint_column_usage", $verif_contraints );
      while($l=sql::fetch())
          $usages[$l['constraint_name']][$l['table_schema']][$l['table_name']][  $fkusages[$l['constraint_name']] [ $l['column_name']  ]  ] = $l['column_name'];
              //="{$l['table_name']}({$l['column_name']})";

      sql::select("information_schema.referential_constraints", $verif_contraints );
      $behavior = sql::brute_fetch('constraint_name');

      foreach($this->sql_def as $constraint_name=> &$constraint_infos){//!
        $key = $keys[$constraint_name];
        $types= array('PRIMARY KEY'=>"PRIMARY",  "UNIQUE"=>'UNIQUE', 'FOREIGN KEY'=>'FOREIGN' );

        $constraint_infos['type']=$type=$types[$key['constraint_type']];
        $constraint_infos['defer']  = bool($key['is_deferrable']) && bool($key['is_deferrable']) ? 'defer':'strict';

        if($type=="FOREIGN") {

            list($usage_schema, $usage_fields) = each($usages[$constraint_name]);
            list($usage_table, $usage_fields)  = each($usage_fields);

            $constraint_infos['update'] = self::$fk_actions_in[$behavior[$constraint_name]['update_rule']];
            $constraint_infos['delete'] = self::$fk_actions_in[$behavior[$constraint_name]['delete_rule']];
            $constraint_infos['refs']   = self::build_ref($usage_schema, $usage_table, $usage_fields);

        }
    }
  }
}