<?php


abstract class myks_installer extends myks_parsed {
  abstract function delete_def();
  abstract function get_name();
  function hash_key(){
    $name = $this->get_name();
    return $name['hash'];
  }
}