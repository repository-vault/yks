<?

function mykse_validate($data,$filter_in){
    $types_xml = yks::$get->types_xml;
    $out=array();$filter_unique=false;
    if($filter_in instanceof simpleXmlElement) $filter_in=fields($filter_in);
    if(!is_array($filter_in)){
        $filter_in = array($filter_unique=$filter_in);
        if(!is_array($data)) $data=array($filter_unique=>$data);
    }
    //if(!is_array($data))  DONT cast here since $data might of an array type user_flags[]
    foreach($filter_in as $mykse_key=>$mykse_type){
        if(is_numeric($mykse_key)) $mykse_key=$mykse_type;

        if(!isset($data[$mykse_key]) && !is_null($data[$mykse_key])) continue;
        $val_init = $val = $data[$mykse_key];
        $mykse=$types_xml->$mykse_type;
        $null = is_null($val);

      while(true) {    //loop to recurse
        if(!$mykse) break;
        $mykse_type=(string) $mykse['type'];

        $nullable = $mykse['null']=='null';
        if($null && $mykse['null']=='not_null' && is_not_null($mykse['default']))break;
        if($null && $nullable){ $out[$mykse_key]=null; break;}

        if($mykse_type=="text"){
            $out[$mykse_key]=rte_clean($val);
        }elseif($mykse_type=='bool'){
            $out[$mykse_key]=bool($val,true);
        }elseif($mykse_type=='mail'){
            $val = trim(strtolower($val));
            $out[$mykse_key]= mail_valid($val)?$val:false;
        }elseif($mykse_type=='int'){
            if($null) break;
            $out[$mykse_key]=(int) $val;
        }elseif($mykse_type=='time'){
            if($val=="" && $nullable) { $out[$mykse_key]=null; break;}
            if(is_numeric($val)) $out[$mykse_key] = $val;
            else $out[$mykse_key]=date::validate($val);
        }elseif($mykse_type=='string'){
            $out[$mykse_key]=$val;
        } elseif($mykse_type=='enum'){
            $vals=vals($mykse);
            if($mykse['set']) {
                if(!is_array($val)) $val=explode(',',$val);
                $val=array_intersect($vals, $val);
                if($val_init && !$val) { $out[$mykse_key]='';break; }
                $out[$mykse_key]=join(',', $val);
            } else {
                $key=array_search($val,$vals);
                if($key===false) { $out[$mykse_key]=null;break; }
                $out[$mykse_key]=$val;
            }
        } elseif($mykse_type){
            $mykse=$types_xml->$mykse_type;
            continue;
        }
        break;
      } //loop

    } return $filter_unique?$out[$filter_unique]:$out;
}


class mykses {
  static function value($mykse_type, $val){
    static $types_xml = false; if(!$types_xml) $types_xml = yks::$get->types_xml;
    $mykse = $types_xml->$mykse_type;
    if(!$mykse) return $val; 
    $mykse_type = $mykse['type'];
    if($mykse_type=='bool') return bool($val,true);
    elseif($mykse_type=='time') return date('d/m/Y',$val);
    elseif($mykse_type == 'text') return specialchars_encode($val);
    elseif(in_array($mykse_type, array('text', 'string','int')) )
        return $val;
    elseif($mykse_type) return self::value($mykse_type, $val);
  }
}




function mykse_out($data, $fields=array()){
    $types_xml = yks::$get->types_xml; $out=array();

    foreach($data as $field_name=>$val){
        $mykse_type = isset($fields[$field_name])?$fields[$field_name]:$field_name;
        $out[$field_name] = mykses::value($mykse_type, $val);
     } return $out;
}

