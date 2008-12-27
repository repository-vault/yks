<?
/*
Compress CSS rules
*/

function compute_css($file,$files_path=array()){
    static $current_dir=false; if(!$current_dir) $current_dir="/";
    static $site_url=false;
    if(!$site_url) {
        $file_infos=parse_url($file);
        $file=$file_infos['path'];
        $site_url=$file_infos['host']?"http://{$file_infos['host']}":SITE_URL;
    }

    $old_dir=$current_dir;

    $dir=dirname($file);
    $file=basename($file);
    $current_dir=($dir[0]!="/")?$current_dir.($current_dir=='/'?'/':'/').$dir:$dir;

    $current_dir=rp($current_dir);
    $file_path="$current_dir/$file";
    $file_url=$site_url.$file_path;

    $contents='';
    if(!in_array($file_url,$files_path)){
        $files_path[]=$file_url;
       
        $contents=@file_get_contents($file_url);

            //strip comments
        $contents=preg_replace("#/\*.*?\*/#",'',$contents);

            //Look for imports
        if(preg_match_all("#@import ([\"'])(.*?)\\1\s*;[\s]*#",$contents,$out))
            $imports_list=array_combine($out[2],$out[0]);
        else $imports_list=array();

            //resolve url() calls
        if(preg_match_all("#url\(\s*([\"']?)([^\\1]*?)\\1\s*\)#",$contents,$out)){
            //POWNED
            foreach($out[2] as $k=>$file){
                $search=$out[0][$k]; $sep=$out[1][$k];
                //this line is a mystery, if someone understand why i was trying to do...
                //$file=preg_replace(array('#\\\?{#','#\\\?}#'),array("%7B","%7D"),$file);
                if(!preg_match("#^http|/#",$file)) $file="$current_dir/$file";
                if($search!=($url="url($sep$file$sep)"))
                        $contents=str_replace($search,$url,$contents);
                        
            }
        }

        foreach($imports_list as $file=>$search){
            $inner=compute_css($file,$files_path);
            $contents=str_replace($search,$inner."\n",$contents);
        }

          //fuck @media & co
        preg_match_all("#[\s\r\n]*(.*?)\{([^{]*?)\}#s",$contents,$out);

        $contents=join('',array_map('join_bracets',
            array_map("valid_selectors",$out[1])
            ,array_map("valid_rules",$out[2])
        ));

    }

    $files_path[]=$file_url;

    $current_dir=$old_dir;
    return $contents;
}


function join_bracets($a,$b){ return $a."{".$b."}";}
function join_dbldotted($a,$b){ return $a.":".$b;}

function valid_selectors($str){
    return trim(preg_replace("#\s*,\s*#",",",$str));
}


function valid_rules($str){
    preg_match_all("#\s*([^:\r\n;]*):\s*([^;]*)[;\r\n]?#",$str,$out);
    return join(';',array_map('join_dbldotted',$out[1],$out[2]));
}
