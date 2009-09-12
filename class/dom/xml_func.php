<?

function rise_xml($tree){
    $str ='';
    foreach($tree as $k=>$elem){ 

        if($k{0} =='<') {
            $node_type = trim($k, '<>');

            foreach($elem as $elem){
                $res = array($node_type=>$elem) ;

                $str.= rise_xml( $res );
                
            }

        } else {

            $node_type = $k;
            $node_attr = array();

            $node_content = false;
            if(is_string($elem))
                $node_content = $elem;

            if(is_array($elem)) 
              foreach($elem as $k=>$v){
                if(is_numeric($k)){
                    $node_content.=$v ;
                    unset($elem[$k]);
                }
                if($k == '@') {
                    foreach($v as $attr_name=>$v)
                        $node_attr[$attr_name] = $v;
                    unset($elem [$k]);
                } elseif($k{0} == '@') {
                    $attr_name = trim($k, '@');
                    $node_attr[$attr_name] = $v;
                    unset($elem [$k]);
                }
            }

            $child = is_array($elem) ? rise_xml($elem).$node_content: $node_content;

            $elem = "<$node_type ".mask_join(' ', $node_attr, '%2$s="%1$s"');
            $elem = $elem. ($child ? ">$child</$node_type>" : "/>");

            $str .= $elem.CRLF;

            
        }
    }

    return $str;

}
