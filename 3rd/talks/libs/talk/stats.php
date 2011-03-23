<?

class talk_stats {

  public static function get_last_stats($roots){

    $tables = array('ks_talks_tree_depth', 'talk_id'=>'ks_talks_list');
    $group  = "GROUP BY parent_id";
    $verif_roots = array('parent_id' =>  array_keys($roots));
    $cols = "parent_id, MAX(talk_date) AS talk_last, COUNT(*) AS talks_nb";
    sql::select($tables,  $verif_roots, $cols, $group);
    $stats = sql::brute_fetch('parent_id');


    foreach($roots as $node_id=>$node)
        $node->stats = $stats[$node_id];

    $lasts = array_extract($stats, 'talk_last');
    if($lasts) {
        $lasts = mask_join(" UNION ", $lasts, 'SELECT %2$s, %1$s');
        sql::query("SELECT * FROM `ks_talks_tree_depth`
            LEFT JOIN `ks_talks_list` USING(talk_id)
            WHERE (parent_id, talk_date) IN ($lasts)");
        $lstats = sql::brute_fetch("parent_id");
    }

    foreach($roots as $node_id=>$node)
        $node->last_stats = $lstats[$node_id];

  }

  public static function get_children_stats($roots = null){
    $verif_root = array('parent_id' => array_keys($roots));
    $cols = "COUNT(talk_id) AS talks, talk_depth";
    sql::select("ks_talks_tree_depth", $verif_root, $cols, "GROUP BY talk_depth, parent_id");
    $children_stats = sql::brute_fetch_depth("parent_id", "talk_depth", "talks");
    foreach($parents as $parent)
        $parent->children_stats  = $children_stats[$parent->talk_id];

  }
}