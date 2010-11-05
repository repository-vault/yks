<?


class cms_nodes_manager {

  static function get_children(cms_node $node){
    $verif_node = array('parent_id'=>$node->node_id);
    return self::from_where($verif_node);
  }

  static function build_nav($node_parent){
    $verif_link = array('parent_id' => $node_parent);
    $verif_link['node_type'] = 'link';

    $nodes_list = cms_node::from_where($verif_link);

    // $nav = 
    $nav = array();
    foreach($nodes_list as $node_id=>$node){
        $dest = $node['link_dest_node'];
        $nav[$node_id] = array(
            'href'=>"/?/Corporate//$dest",
            'theme'=>'',
            'id'=>"node_$node_id",
            'target'=>$node['link_target'],
            'title'=>'<span>&#160;</span>',
        );
    }
    return $nav;
  }

  static function restore_state(cms_node $node, $node_date) {
    $verif_revision = array($node, 'node_date'=>$node_date);
    $str = sql::value("ks_cms_nodes_log", $verif_revision, "node_state");

    $obj = unserialize(base64_decode($str));

    if(get_class($node) != get_class($obj)
        || $node->node_id != $obj->node_id)
        throw new Exception("Unable to restore previous state");
    return $obj;
  }

  static function get_states(cms_node $node){

    sql::select("ks_cms_nodes_log", $node, "node_date", "ORDER BY node_date DESC");
    return sql::fetch_all();
  }

  static function log_state(cms_node $node) {

    $str = $node->serialize();


    $data = array(
        'node_id'    => $node->node_id,
        'node_state' => $str,
    ); sql::insert("ks_cms_nodes_log", $data);

  }

  static function serialize(cms_node $node) {
    $str = base64_encode(serialize($node));
    return $str;

  }

/*
    sauve l'info en bdd, complexe parceque cms_node data est slicé en 2 tables (au moins)
*/
  static function save(cms_node $node, $input){

    $old_state = $node->serialize();

    $keys = array('node_key');
    $keys = array_merge($keys, $node->editables);

    $table_type = "ks_cms_nodes_{$node->node_type}";

    $extra_fields = fields(yks::$get->tables_xml->$table_type);
    $base_fields  = fields(yks::$get->tables_xml->{cms_node::sql_table});
    $extra_fields = array_diff_key($extra_fields, $base_fields);

    $extra_data   = array_intersect_key($input, $extra_fields);
    if($extra_data) sql::update($table_type, $extra_data, $node);

    $std_data   = array_intersect_key($input, $base_fields);
    if($std_data) sql::update(cms_node::sql_table, $std_data, $node);
    $data       = array_merge($extra_data, $std_data);
    foreach($data as $k=>$v) $node->_set($k, $v);


    $new_state = $node->serialize();
    if($old_state != $new_state )
        $node->log_state();

  }

  static function get_related(cms_node $node){

    $parent_id = $node['parent_id'];
    if(is_null($parent_id))
        return array();
    $verif_sibling = array(
        'parent_id'=> $parent_id,
        'node_type'=> $node['node_type'],
    );
    $nodes_list = cms_node::from_where($verif_sibling);

    $nodes_ids = array_keys($nodes_list);
    $me = array_search( $node['node_id'], $nodes_ids);

    $siblings = array();
    if($prev = $nodes_ids[$me-1] )
        $siblings['prev'] = $nodes_list[$prev];

    if($next = $nodes_ids[$me+1])
        $siblings['next'] = $nodes_list[$next];

    return $siblings;
  }


  static function from_where($where){
    $nodes_list = self::build_nodes($where);
    foreach($nodes_list as &$node) {
        $node_class = "cms_node_{$node['node_type']}";
        $node = new $node_class($node, true);
    }

    return $nodes_list;
  }

  static function build_nodes($where) {
    sql::select(cms_node::sql_table, $where, "*", "ORDER BY node_order");
    $nodes_list = sql::brute_fetch("node_id");

    $nodes_type = array_extract($nodes_list, "node_type", true);
    
    $node_extras = array();
    foreach($nodes_type as $node_type){
        $verif_nodes = array('node_id' => array_keys($nodes_list));
        $extra_table = cms_node::$table_type[$node_type];
        if(!$extra_table) continue;
        sql::select($extra_table, $verif_nodes);
        $node_extras = array_merge_numeric($node_extras, sql::brute_fetch("node_id"));
    }

    
    foreach($nodes_list as $node_id=>&$node)
        if(isset($node_extras[$node_id])) $node = array_merge($node, $node_extras[$node_id]);

    return $nodes_list;
  }


  static function lnk($node, $txt = false, $target=false, $class=false ){
    $txt = $txt ? $txt : $node->node_id;
    $class = $class?"class='$class'":''; 

    if($node['node_type'] == 'link')
        $node_dest = $node['link_dest_node'];
    else $node_dest = $node->node_id;

    $href =  "/?/Corporate//$node_dest";

    $title = $title? "title='$title'":'';
    return "<a $title $class href='$href'>$txt</a>";
  }

 
  static function get_contents(cms_node $node, $inner_contents){ //internal


    $related_nodes = array();
    $related_str = '';

    if($node->node_type == 'article') {
        $contents  = $node->node_contents;
        $related_nodes  = $node->related;
    }else if($node->node_type == 'container') {
        $contents  = $inner_contents;
    } else throw new Exception("Unimplemented");

    $template_id = $node->parent_node->node_template;
    if(!$template_id)
        return $contents;


    $template  = cms_node::instanciate($template_id);
    $xsl = $template->template_xsl;

    if($prev = $related_nodes['prev'])
        $related_str .= self::lnk($prev, "&lt; prev", false, "prev");
    if($next = $related_nodes['next'])
        $related_str .= self::lnk($next, "next &gt;", false, "next");

    if($related_str)
        $related_str = sprintf("<hnav>%s</hnav>", $related_str);

        //on pourrait aussi injecter hnav en <xsl:variable (node-set))
    $contents.= $related_str ? "<cms>$related_str</cms>":'';


    $xsl_str = XML_HEAD;
    $xsl_str .="<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' version='1.0'>";
    $xsl_str .="<xsl:output method='xml' encoding='utf-8' omit-xml-declaration='yes'/>";
    $xsl_str .="<xsl:template match='/*'>$xsl</xsl:template>";
    $xsl_str .="</xsl:stylesheet>";

    $xml_str = XML_HEAD."<null>$contents</null>";


    $xsl = new DOMDocument('1.0','UTF-8');
    $xsl->loadXML($xsl_str, LIBXML_YKS);

    $xml = new DOMDocument('1.0','UTF-8');
    $xml->loadXML($xml_str, LIBXML_YKS);



    $xslt = new XSLTProcessor();
    $xslt->importStyleSheet($xsl);
    $xml = $xslt->transformToDoc($xml);

    $head = "<{$xml->documentElement->nodeName}";
    $str = $xml->saveXML(null, LIBXML_NOXMLDECL);
    $str = strstr($str, $head);

    return $str;
  }

  static function get_leaf(cms_node $node){
    $children = $node->children;
    if(!$children) return $node;
    return reset($children)->leaf;
  }

  static function deliver(cms_node $node, $recursive_up = true, $inner_contents = ''){

    $my_contents = $node->get_contents($inner_contents);
    if($recursive_up) {
        $parent = $node->parent_node;
        if($parent)
            return $parent->deliver($recursive_up, $my_contents );
    }
    return $my_contents;

  }


}