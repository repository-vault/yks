<?
class locale_tag extends _sql_base {
  const upload_type = 'locale_tag_sshot';

  protected $manager = "locale_tag_manager"; // NOT PRIVATE
  const sql_table = 'ks_locale_tags_list';
  const sql_key = 'tag_id';

  protected $sql_table = "ks_locale_tags_list";
  protected $sql_key = "tag_id";

  public static $sshot_big_path; //oninit
  public static $sshot_tn_path;  //oninit

  public function __construct($from){
    parent::__construct($from);
  }

  private static $tags_tree; //used for tags_path generation

  public static function init(){
    $sshot_big_path = yks::$get->config->localization->sshot['big_path'];
    $sshot_tn_path  = yks::$get->config->localization->sshot['tn_path'];

    self::$sshot_big_path = paths_merge(PUBLIC_PATH, $sshot_big_path);
    self::$sshot_tn_path  = paths_merge(PUBLIC_PATH, $sshot_tn_path);


    files::create_dir(self::$sshot_big_path);
    files::create_dir(self::$sshot_tn_path);


    tpls::export(array(
        'locale.sshot_big_path'=>$sshot_big_path,
        'locale.sshot_tn_path'=>$sshot_tn_path,
    ));
  }

  function update($data, $sshot_file = false){
    if($sshot_file) $this->attach_file($sshot_file);
    if($data) $this->sql_update($data);
  }

  function get_sshot_file(){
    if($this->sshot_width) return true;
    return false;
  }

  function get_big_url(){
    return sprintf("&locale.sshot_big_path;/%d.jpg", $this->tag_id);
  }

  function delete(){
    $del = $this->sql_delete();
    if(!$del) throw rbx::error("Impossible de supprimer #{$this->tag_id}");
    $this->trash_files();
  }

  function get_sshot_dims(){
    return "width:{$this->sshot_width}px;height:{$this->sshot_height}px;";
  }

  function get_items_list(){
    sql::select("ks_locale_tag_items", $this);
    return sql::brute_fetch("item_key");
  }

  function __toString(){
    $parents = $this->paths_str;
    if($parents) $parents.=" &gt; ";
    return $parents.$this->tag_name;
  }

  function get_paths(){
        //recupere la list des ids des parents de ce tag
    $tags_tree = array_keys(linearize_tree(self::get_tags_tree("tags_parents", $this->tag_id)));
    $tags_tree = array_filter($tags_tree);
    return $tags_tree;
  }

  static private function get_tags_tree($type, $value = false){
    if(is_null(self::$tags_tree)) {
        sql::select(self::sql_table, "true", "tag_id, parent_tag");
        $tag_tree = sql::brute_fetch('tag_id', 'parent_tag');
        $tags_parents   = make_tree($tag_tree, false, true);
        $tags_children  = make_tree($tag_tree, false);
        self::$tags_tree = compact('tag_tree', 'tags_parents', 'tags_children');
    } return $value?self::$tags_tree[$type][$value]:self::$tags_tree[$type];
  }

  static function from_where($where){

    return parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
  }


}



