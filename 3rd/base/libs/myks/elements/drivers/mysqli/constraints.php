<?php
  class myks_constraints extends myks_constraints_base {
    protected $keys_name = array(
      'PRIMARY'=>"PRIMARY",
      'UNIQUE'=>"%s_%s_%s",
    );

    public $key_mask = array("PRIMARY"=>"PRIMARY KEY", "UNIQUE"=>"UNIQUE `%s`");
  }