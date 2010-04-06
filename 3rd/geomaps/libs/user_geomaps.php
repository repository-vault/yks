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
        foreach($users_list as $user_id) $users_colors[$user_id] = self::user_color($user_id);
    }
    $this->users_colors = $users_colors;
  }


  static function user_color($user){
    $user_id = is_numeric($user)?$user:$user['user_id'];
    return hexdec(substr(md5($user_id),0,6));
  }

  function __get($key){
    if(method_exists($this, $getter = "get_$key"))
        return $this->$getter();
  }

  function get_root_user(){
    return $this->data['user_id'];
  }

  function toggle_user_at($x,$y, $user_id){
    $area_id = $this->png_map->hash_key_at($x, $y);

    $verif_area = compact('area_id');
    if(isset($this->area_user[$area_id]))
        sql::delete("ks_users_geomaps_area", $area_id);

    $data = compact('area_id', 'user_id');
    $data['map_id'] = $this->data['map_id'];
    sql::insert("ks_users_geomaps_area", $data);
    $this->area_user[$area_id] = $user_id;
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
