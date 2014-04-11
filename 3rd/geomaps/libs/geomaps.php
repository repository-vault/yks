<?php

class geomaps {

  const sql_table = 'ks_users_geomaps_list';
  const sql_key   = 'map_id';
  protected $sql_table = self::sql_table;
  protected $sql_key   = self::sql_key;

  private static $maps_path;
  protected $area_colors;
  protected $png_map;
  protected $data;
  static function init(){
    classes::register_class_path("png_map",  CLASS_PATH."/apis/png/map.php");
  }

  public function __construct($map_id){
    $verif_map  = compact('map_id');
    $this->data = sql::row(self::sql_table, $verif_map);
    if(!$this->data)
        throw new Exception("Invalid map #");

    if(!is_file($this->data['map_path']))
        throw new Exception("Map $map_id file not ready");
    $this->png_map = new png_map($this->data['map_path']);

  }


  public function render($default_color = imgs::COLOR_WHITE ){
    $this->png_map->fill($default_color);
    foreach($this->area_colors as $hash_key => $color)
        $this->png_map->set_color($hash_key, $color);

    $this->png_map->output();
  }

  public static function list_maps($where=array(sql::true)) {
    sql::select(self::sql_table,$where);
    return sql::brute_fetch(self::sql_key);
  }

}