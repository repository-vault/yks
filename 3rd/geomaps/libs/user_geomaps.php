<?php



class user_geomaps extends geomaps {
  private $area_user   = array();
  private $users_colors = array();

  function __construct($map_id, $users_colors = false){
    parent::__construct($map_id);
    $verif_map = compact('map_id');
    sql::select("ks_users_geomaps_area", $verif_map);
    $this->area_user = sql::brute_fetch("area_id", "user_id"); //one area per user in this one
    if($users_colors === false) {
        $users_list  = array_unique(array_filter(array_values($this->area_user)));
        $users_colors = array();
        foreach($users_list as $user_id) $users_colors[$user_id] = hexdec(substr(md5($user_id),0,6));
    }
    $this->users_colors = $users_colors;
  }

  function render($default_color = imgs::COLOR_WHITE) {
    $this->area_colors = array();
    foreach($this->area_user as $area=>$user_id) {
        if(!$this->users_colors[$user_id]) continue;
        $this->area_colors[$area] = $this->users_colors[$user_id];
    }
    parent::render($default_color);
  }
}
