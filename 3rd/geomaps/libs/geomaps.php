<?php

class geomaps {
  private static $maps_path;
  protected $area_colors;
  private $png_map;

  static function init(){
    classes::register_class_path("png_map",  CLASS_PATH."/apis/png/map.php");


    self::$maps_path = yks::$get->config->geomaps['maps_path'];
    if(!is_dir(self::$maps_path))
        throw new Exception("Geomaps directory is not ready");

  }

  public function __construct($map_id){
    $verif_map = compact('map_id');
    $map_infos = sql::row("ks_users_geomaps_list", $verif_map);
    if(!$map_infos)
        throw new Exception("Invalid map #");

    $file_path = self::$maps_path."/$map_id.png";
    $this->png_map = new png_map($file_path);
    
  }

  public function render($default_color = imgs::COLOR_WHITE ){
    $this->png_map->fill($default_color);
    foreach($this->area_colors as $hash_key => $color)
        $this->png_map->set_color($hash_key, $color);

    $this->png_map->output();
  }

}