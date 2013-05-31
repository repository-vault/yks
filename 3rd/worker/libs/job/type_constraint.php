<?php
 class job_type_constraint extends _sql_base {
    const sql_table = 'ks_jobs_type_constraint';
    const sql_key = 'job_type';

    const WAIT = 'wait';

    protected static $job_constraint = array(
      self::WAIT,
    );

    protected $sql_table = self::sql_table;
    protected $sql_key   = self::sql_key;


    /**
    *
    *
    * @param string $job_type
    * @param string $constraint
    */
    public function search($job_type, $constraint){

      if(!in_array($constraint, self::$job_constraint)){
        Throw new Exception('Invalid job constraint type');
      }

      $where = array(
        'job_type'       => $job_type,
        'job_constraint' => $constraint,
      );

      return sql::row(self::sql_table, $where);
    }

    /**
    * Add constraint for type
    *
    * @param string $job_type
    * @param string $constraint
    * @param string $data
    */
    public function replace($job_type, $constraint, $data){


      $vals = array(
        'job_type'       => $job_type,
        'job_constraint' => $constraint,
      );

      $where = array(
        'data'           => (string)$data,//string in db
      );

      return sql::replace(self::sql_table, $vals, $where);
    }
 }
