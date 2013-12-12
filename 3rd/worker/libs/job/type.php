<?php
  /**
  * Server side!!
  */
  class job_type extends _sql_base {
    const sql_table = 'ks_jobs_type';
    const sql_key = 'job_type';

    protected $sql_table = self::sql_table;
    protected $sql_key = self::sql_key;

    public static $db_types = array();

    public static function __construct_static(){
      self::$db_types = self::from_where(sql::true);;
    }

    public static function from_ids($ids){
      $ret = self::from_where(array(self::sql_key => $ids));
      return $ret;
    }

    public static function from_where($where){
      return parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
    }

    public static function instanciate($id){
      $where =  array(
        self::sql_key => $id
      );
      return first(self::from_where($where));
    }
  }
