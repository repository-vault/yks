<?php

class yks_runner {

  function sql($query){
    sql::query($query);
    print_r(sql::brute_fetch());
  }

  function myks(){
    rbx::ok("Loading myks_runner");
    interactive_runner::start(new myks_runner());    

  }


}
