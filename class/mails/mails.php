<?


class mails {


  static function get_parts($part_id,$depth=-1){
    $children = sql_func::get_children($part_id,'ks_mails_parts','part_id',$depth,'parent_part');
    return array_merge(array($part_id), $children);
  }


  static function load_children($mail, $part_infos, $child_part_class='mime_part' ){
    if(is_numeric($part_id = $part_infos))
        $part_infos  = sql::row("ks_mails_parts", compact('part_id') );
    if(! ($part_id = $part_infos['part_id']) ) return false;

    $child_part = new $child_part_class($mail, $part_infos);

    $verif_children = array('parent_part'=>$part_id);
    sql::select("ks_mails_parts", $verif_children);
    while($child_infos = sql::fetch())
        $child_part->add_child( self::load_children( $mail, $child_infos, $child_part_class ) );

    return $child_part;
    
  }

}







