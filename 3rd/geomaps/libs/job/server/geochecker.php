<?php
/**
*@property array $geochecker
*/
 class job_server_geochecker extends job_server {

    const sql_table = 'ks_job_geochecker';
    const sql_key = 'job_id';

    protected $sql_table = self::sql_table;
    protected $sql_key   = self::sql_key;
    protected static $job_type  = 'geochecker';
    protected static $geochecker_types = array();


    public function __construct_static(){
      sql::select('ks_geochecker');
      self::$geochecker_types = sql::brute_fetch('geochecker_id');
    }

    protected function load_geochecker(){
      return $this->geochecker = self::$geochecker_types[$this->geochecker_id];
    }

    public static function getgeochecker($name){//not yks magic method
      $array = array_extract(self::$geochecker_types, 'geochecker_name');
      return self::$geochecker_types[array_search($name, $array)];
    }

    /**
    * Create && initialize job_geochecker
    *
    * @param int $this->geochecker_id id
    * @param string $origin_id id de la ligne contenant les infos de l'addresse
    * @param string $target_id id de la ligne contenant les infos de l'addresse de geopos
    *
    * @return job_geochecker
    */
    public static function create($geochecker_id, $origin_id, $target_id){

      $extra_data = compact('geochecker_id', 'origin_id', 'target_id');

      $job = job_server_manager::create(self::$job_type, $extra_data);
      if(!$job)
        throw new Exception("No job created");

      return $job;
    }

    public function get_addr_infos(){
      $cols = array(
        'addr_field1',
        'addr_field2',
        'city',
        'state',
        'zipcode',
        'country',
      );

      foreach($cols as $col){
        if($this->geochecker[$col]){
          $new_cols[] = $this->geochecker[$col].' as '.$col;
        }
      }

      sql::select($this->geochecker['origin_table'], array($this->geochecker['origin_field_id'] => $this->origin_id), join(', ', $new_cols));

      return first(sql::brute_fetch());
    }

    /**
    * Send information to job client
    *
    * @return array
    */
    public function get_description(){
      $desc = parent::get_description();
      if(is_array($this->addr_infos) && is_array($desc)){
         return array_merge($desc, $this->addr_infos);
      }

      return $desc;
    }

    /**
    *
    *
    * @param array $data
    *
    * @return void
    */
    public function post_done($data = null){

      if($data == geotools::NO_RESULT){
        $lat = 0;
        $lon = 0;
      }
      elseif($data == geotools::OVER_QUERY_LIMIT){
        $this->add_constraint(job_type_constraint::WAIT, $_SERVER['REQUEST_TIME'] + 24 * 60 * 60);// wait 24h
        $this->publish();
        return;
      }
      else{
        $lat = $data->lat;
        $lon = $data->lon;
      }

      $vals = array(
        $this->geochecker['target_long_field'] => $lon,
        $this->geochecker['target_lat_field'] => $lat,
      );

      $where = array(
        $this->geochecker['target_field_id'] => $this->target_id,
      );


      sql::update($this->geochecker['target_table'], $vals, $where);
    }
 }