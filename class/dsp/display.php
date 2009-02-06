<?
/*	"Exyks display" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2007 
*/

class dsp{

    /**
        Helps you to create easily <ks_forms' fields based on a table_name
        Part 1/∞
        $skipped_locals : fields to skip ( there's defined in the page context (ie: $sub0))
    */
  static function fields($table_name, $skipped_locals=array()){
    $ret="";
    $table_infos = yks::$get->tables_xml->$table_name;
    if(!$table_infos) return "";

    foreach($table_infos->field as $field){
        if(in_array($field, $skipped_locals)) continue;
        $ret.="<field title='&mykse.$field;' type='$field'/>";
    }
    return $ret;
    print_r($table_infos);die;
  }


  // Find documentation in the manual
  static function pages($max,$by,$page_id,$href,$target=false,$step=true){ $str="";
    for($a=0;$a<$max;$a+=$by){
        $b=$a/$by;$tmp=$b+1;$total=ceil($max/$by);
        if($b==$page_id)$tmp="[$tmp]";
        if($step && $b>4 && $b<$page_id-2){
            if($b==5) $str.="... "; continue;
        } elseif($step && $b>$page_id+2 && $b<$total-5){
            if($b==$page_id+3) $str.="... "; continue;
        } $str.="<a href='$href$b' ".($target?"target='$target'":'').">$tmp</a>&#160;";
    } return $str." ($max)";
  }

  static function radio($type,$actives=false,$mode="radio"){
    global $xml_types;$tmp="";
    if(!is_array($actives)) $actives=array($actives);
    foreach($xml_types->$type->enum->val as $elem){
      $selected=in_array("$elem",$actives)?" checked='checked'":'';
      $tmp.="<input type='$mode' name='$type' value='$elem' $selected/> &$type.$elem;<br/>";
    }return $tmp;
  }

  static function dds($data,$options=array()){
    $text=''; foreach($data as $k=>$v)
        $text.="<optgroup label=\"$k\">".self::dd($v,$options)."</optgroup>\n";
    return $text;
  }

  static function ccc($city,$name){
   if(is_numeric($city)||!$city){
    extract(sql::row("SELECT commune FROM `ks_geo_zipcode` WHERE insee='$city' LIMIT 1"));
    return "<select name='$name' id='$name'><option value='$city'>$commune</option></select>";
   } else return "<input type='text' name='$name' id='$name' value='$city'/>";
  }
  static function resolve($types_xml,$type){
    $mykse = $types_xml->$type;
    $types= array('enum','int','string','text','time');

    if(in_array((string)$mykse['type'],$types)) return $mykse;
    elseif(!$mykse) return array();
    else return self::resolve($types_xml,$mykse['type']);
  }
  static function resolve_enum($types_xml,$type){
    $mykse = self::resolve($types_xml,$type);
    return $mykse['type']!='enum'?array():vals($mykse);
  }

  static function mailto($str, $subject=false){
    $to = specialchars_decode($str);
    $name = preg_match("#(.*?)\s*<.*?>#",$to,$out)?$out[1]:$to;
    $subject = $subject?"?subject=".mailto_escape($subject):'';
    return "<a href='mailto:".mailto_escape($str).$subject."'>$name</a>";
  }

    //find doc in the manual
  static function element_truncate($str, $len, $element, $alternative=false){
    $empty = !((bool)trim($str)); $truncated = truncate($str, $len);
    if($empty && $alternative) $truncated = $alternative;
    $element_name = preg_reduce('#([^\s]+)#', $element);
    $title = !$empty && $truncated!=$str?" title=\"$str\"":'';
    return "<{$element}{$title}>$truncated</$element_name>";
  }
  static function dd($data,$opts=false){
    if(!is_array($opts))$opts=array('selected'=>array($opts));
    $selected=$opts['selected']; if(!is_array($selected))$selected=array($selected);
    $mykse=$opts['mykse'];$col=$opts['col'];if(!$col)$col="value";
    if(!$data) $data = array();
    $list=!is_array($data)?array_combine(
            $list=self::resolve_enum(yks::$get->types_xml,$data),
            array_mask($list,"&$data.%s;")):$data;

    $options="";$pad=$opts['pad']?$opts['pad']:"&#160;&#160;";
    $truncate=$opts['truncate']?$opts['truncate']:50;
    foreach($list as $k=>$v){
        if(!is_array($v))$v=array("value"=>$v);
        $options.="<option value='$k' "
        .((!$mykse) && $truncate&&mb_strlen($v[$col])>$truncate?"title='{$v[$col]}' ":'')
        .($v['class']?"class='{$v['class']}' ":'')
        .($v['disabled']?"disabled='{$v['disabled']}' ":'')
        .($v['selected']||in_array($k,$selected)?'selected="selected" ':'')
        .">".str_repeat($pad,(int)$v['depth'])
            .($mykse?"&$mykse.$k;":truncate($v[$col],$truncate))
        ."</option>";
    } return $options;
  }

  static function addr($addr,$format,$extras=array()){
    if((!is_array($addr)) && $addr=(int)$addr)
        $addr=sql::row("SELECT * FROM `ks_addr` WHERE addr_id='$addr' LIMIT 1");
    if(!$addr) return ''; extract(array_merge($addr,$extras));
    if(is_numeric($addr_city) && function_exists('get_city_insee'))
        $addr_city=get_city_insee($addr_city);
    $format=preg_replace(array(FUNC_MASK,VAR_MASK),VAR_REPL,$format);
    $format=preg_replace('#<([a-z]+)>\s*</\\1>#','',$format);
    $format=join("<br/>",array_filter(preg_split('#(<br\s*/>\s*)#',$format)));
    $format=preg_replace("#&[a-z0-9_-]+\.;#","",$format);
    return $format;
  }

  static function file_size($size){
    $size=sprintf("%u",$size);
    return ($size>>30)?round($size/(1<<30),2).' Go':
        (($size>>20)?round($size/(1<<20),2).' Mo':
            (($size>>10)?round($size/(1<<10),2).' Ko':"$size octets"));
  }

  static function datef($date=_NOW,$format=DATE_MASK){
    return self::date($date, preg_replace("#[a-z]#i",'$$0', $format));
  }
  static function date($date=_NOW,$format=DATE_DAY,$format_rel=false){
    static $rs=false; if(!$rs) $rs=array(
            date('z/Y',_NOW)=>'&date.today;',
            date('z/Y',_NOW-86400)=>'&date.yesterday;');
    list($d,$m,$n,$Y,$H,$i,$s,$z,$N)=explode(',',date("d,m,n,Y,H,i,s,z,N",$date));
    if($z<79 or $z>354)$a=4; elseif($z<172)$a=1; elseif($z<265)$a=2;else $a=3; //a = season
    if('0/1970'=="$z/$Y") return "&date.0;";
    if($date==2147483647) return "&date.never;";
    $t=ceil($n/3); $rel=$rs["$z/$Y"]; 
    return preg_replace(VAR_MASK,VAR_REPL,$rel&&$format_rel?$format_rel:$format);
  }

  // Find documentation in the manual
  static function nav($tree,$id=false, $depth=0){
    $str="<ul ".($id?"id='$id'":'').">";
    foreach($tree as $link_key=>$link_infos){
        if($link_infos['acces']
             && !auth::verif(key($link_infos['acces']),current($link_infos['acces'])))
                continue;
        $title=$link_infos['title'];
        $children=(bool)$link_infos['children'];
        $current=(substr(exyks::$href,0,strlen($link_key))==$link_key);
        $target=$link_infos['target']?"target=\"{$link_infos['target']}\"":'';
        $class=$children?"class='parent'":'';
        $str.="<li $class>";
        if($link_infos['theme'])
            $title="<title theme='ivs_".($current?'on':'off')."'>$title</title>";
        $href=$link_infos['href']?"href='{$link_infos['href']}'":'';
        $class=$current?"class='current'":'';
        $str.="<a $class $target $href>$title</a>";
        if($children) $str.=self::nav($link_infos['children'],false,$depth+1)."";
       $str.="</li>";
    }
    $str.="</ul>";
    return $str;
 }

}

