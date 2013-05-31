<?php
  class job_client_geochecker extends job_client {

    protected $addr_field1;
    protected $addr_field2;
    protected $city;
    protected $state;
    protected $country;
    protected $zipcode;

    public function __construct($conf){
      parent::__construct($conf);

      $this->addr_field1 = $conf['addr_field1'];
      $this->addr_field2 = $conf['addr_field2'];
      $this->city        = $conf['city'];
      $this->state       = $conf['state'];
      $this->zipcode     = $conf['zipcode'];
      $this->country     = $conf['country'];

    }

    public function run(){

      $data = array(
        'street'  => $this->addr_field1.' '.$this->addr_field2,
        'city'    => $this->city,
        'postal'  => $this->zipcode,
        'state'   => $this->state,
      );

      try{
        $result = geotools::geodecode_request($data);
      }
      catch(Exception $e){
        if($e->getMessage() == geotools::OVER_QUERY_LIMIT){
          $result = geotools::OVER_QUERY_LIMIT;
        }
        else{
          $result = geotools::NO_RESULT;
        }
      }

      $this->done($result);
    }

}