<?


define('DIFF_PAD',' ');
define('DIFF_REM','-');
define('DIFF_ADD','+');
define('DIFF_CMD','@');
define('TAB_PAD','&#160;..&#160;'); //althought the output is html, tab cannot be used as so



//    $contents=  xdiff_string_diff ( $from,$to);
//    $contents= str_replace("\n\\ No newline at end of file","",$contents);



function format_diff($contents){
$class_names=array(
    DIFF_PAD=>'pad',
    DIFF_REM=>'del',
    DIFF_ADD=>'ins',
);


    $output_data=array(); //v0 number, v1_number, line mode, line contents

    $lines=preg_split("#\r?\n#",$contents);

    foreach($lines as $line){
        $line_data =  array();
        $key = $line[0];

        if($key == DIFF_CMD){
            if($v1_line) $output_data[]=array('v1_line'=>'…','v2_line'=>'…');
            preg_match('#^@@\s*\-([0-9]+),([0-9]+)\s*\+([0-9]+),([0-9]+)\s*@@$#', $line,$out);
            list(,$v1_line_start,$v1_lines,$v2_line_start,$v2_lines)=$out;
            $v1_line = $v1_line_start; $v2_line = $v2_line_start;

           continue;
        }

        $line_data['line_mode']=$key;
        $line_data['line_contents']=substr($line,1);

        if($key == DIFF_PAD ) {
                $line_data['v1_line'] = $v1_line++;
                $line_data['v2_line'] = $v2_line++;
        }elseif($key == DIFF_REM ) {
                $line_data['v1_line'] = $v1_line++;
                $line_data['v2_line'] = '';
        } elseif($key == DIFF_ADD ) {
                $line_data['v1_line'] = '';
                $line_data['v2_line'] = $v2_line++;
        }

        $output_data[]= $line_data;
    }

    $str="";
    foreach($output_data as $k=>$line_data){
        $mode = $line_data['line_mode'];
        $class = $class_names[$mode];
        $contents= $line_data['line_contents'];
        if($mode == DIFF_REM && $output_data[$k+1]['line_mode'] == DIFF_ADD && $output_data[$k+2]['line_mode'] != DIFF_ADD ){
            $contents= diff_string($contents,$output_data[$k+1]['line_contents']);
        }
        if($mode == DIFF_ADD && $output_data[$k-1]['line_mode'] == DIFF_REM && $output_data[$k-2]['line_mode'] != DIFF_REM ){
            $contents= diff_string($output_data[$k-1]['line_contents'],$contents,2);
        }
        $contents= specialchars_encode($contents);
        $contents= strtr($contents,array(" "=>"&#160;","\t"=>TAB_PAD));

        $contents= strtr($contents,array("#ins#"=>"<span class='ins'>","#del#"=>"<span class='del'>","#/ins#"=>"</span>","#/del#"=>"</span>"));

        if(!$line_data['v1_line'])$line_data['v1_line']="&#160;";
        if(!$line_data['v2_line'])$line_data['v2_line']="&#160;";
        if(!$contents) $contents = "&#160;";

        $str.="<tr class='diff_$class'>
            <td>{$line_data['v1_line']}</td>
            <td>{$line_data['v2_line']} </td>
            <td>{$contents}</td>
        </tr>";
    }

    $str="<table cellspacing='0'>$str</table>";
    return $str;

}




function diff_string($str1,$str2,$wanted="1"){

    $base1=$str1;$base2=$str2;
    $mask ="#[a-z]+|[^a-z\s]+|\s+#i";   // $mask ="#.#";
    $str1 = preg_replace($mask,"$0\n","$str1 ");
    $str2 = preg_replace($mask,"$0\n","$str2 ");

    $pad1=array();$sum=0;
    foreach(explode("\n",$str1) as $k=>$elem){
        $pad1[$k]=$sum;$sum+=strlen($elem);
    }

    $pad2=array();$sum=0;
    foreach(explode("\n",$str2) as $k=>$elem){
        $pad2[$k]=$sum;$sum+=strlen($elem);
    }

    $diff= xdiff_string_diff($str1,$str2);

    $str1=""; $str2=""; $current_mode =false;
    $lines=explode("\n",$diff);
    foreach($lines as $line){
        $key= $line[0]; $char=substr($line,1);

        if($key == DIFF_CMD ) {
           $old_line1 = $pad1[$v1_line_start+$v1_lines];
           $old_line2 = $pad2[$v2_line_start+$v2_lines];

           preg_match('#^@@\s*\-([0-9]+),([0-9]+)\s*\+([0-9]+),([0-9]+)\s*@@$#', $line,$out);
           list(,$v1_line_start,$v1_lines,$v2_line_start,$v2_lines)=$out;
           $v1_line_start--;$v2_line_start--;

           $str1 .=substr($base1,$old_line1,$pad1[$v1_line_start]-$old_line1);
           $str2 .=substr($base2,$old_line2,$pad2[$v2_line_start]-$old_line2);

        }

        if($current_mode!=$key){
            if($current_mode ==DIFF_REM) $str1.="#/del#";
            if($current_mode ==DIFF_ADD) $str2.="#/ins#";

            if($key ==DIFF_REM) $str1.="#del#";
            if($key ==DIFF_ADD) $str2.="#ins#";
            $current_mode = $key;
        }

        if($key == DIFF_PAD) {
            $str1.=$char;$str2.=$char;
        } elseif($key == DIFF_ADD) {
            $str2.=$char;
        } elseif($key == DIFF_REM) {
            $str1.=$char;
        }
        
    }
           $str1 .=substr($base1,$pad1[$v1_line_start+$v1_lines]);
           $str2 .=substr($base2,$pad2[$v2_line_start+$v2_lines]);

    $str="str$wanted";
    return $$str;
}

