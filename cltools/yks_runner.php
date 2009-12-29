<?php

class yks_runner {

  function sql($query){
    sql::query($query);
    print_r(sql::brute_fetch());
  }


  function clear_caches(){
    $titles_cache_path = CACHE_PATH."/imgs/titles";
    files::delete_dir($titles_cache_path);
    rbx::ok("Delete dir $titles_cache_path");
  }

  function myks(){
    rbx::ok("Loading myks_runner");
    interactive_runner::start(new myks_runner());    

  }


}
