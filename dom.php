<?

class dom {

  static function simplexml_load_html($str, $charset = "utf-8", $class="Element"){

    $str = self::html_prepare_utf8($str, $charset);

    libxml_use_internal_errors(true);

    $doc = new DomDocument("1.0");
    $doc->loadHTML($str);

    $xpath = new DOMXPath($doc);
    $query = "/html/head/meta[@http-equiv='Content-Type']";
    $entries = $xpath->query($query);
    if($entries->length  && $meta=$entries->item(0) )
        $meta->parentNode->removeChild($meta);

    libxml_clear_errors();
    libxml_use_internal_errors();
    $res = simplexml_import_dom($doc, $class);
    
    //$res->head=null;
    return $res;
  }



/*
  dom::LoadHTML behave wrong when charset != utf-8
  html_prepare make sure that your html will properly be parsed
  and convert your HTML in parse-ready UTF-8
*/

  static function html_prepare_utf8($str, $charset = "utf-8"){

    if(!$charset) $charset = "utf-8";


    $utf8_head = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
    $xml_encoding_mask = "#<\?xml[^>]*encoding=(?:\"([^\"]*)\"|'([^']*)'|(.*?)[\s?])#i";
    $meta_content_type_mask = "#<meta\s*http-equiv=([\"']?)Content-Type\\1\s+content=([\"'])[^\\2]+charset=([a-z0-9-]+)[^\\2][^<]*>#";
    $meta_content_type_existing = "#<meta http-equiv=.Content-Type.*?>#";

    $headers= "";
    if(!preg_match("#<([a-z-]+)[^<>]*>#i", $str, $out)) {
        $str = "<body>$str</body>";
    } else {
        $i = strpos($str, $out[0]);
        $headers = substr($str, 0, $i);
        //if(preg_match("#[^<]*$#s", $headers, $out))
        $clean_head = "<!--{$headers}-->";
        $str = substr($str, $i);
    }

    if(preg_match($meta_content_type_mask, $str, $out)) {
        $charset = $out[3];
        $str = str_replace($out[0], "", $str);
    }

    if(preg_match($xml_encoding_mask, $headers, $out))
        $charset = pick($out[1], $out[2], $out[3]);

    if(stripos($str, "<body")===false) $str = "<html><body>$clean_head$str</body></html>";
    elseif(stripos($str, "<html")===false) $str = "<html>$clean_head$str</html>";
    if(stripos($str, "<head")===false)
        $str = substr($str, 0, $i=stripos($str, ">")+1)."<head></head>".substr($str, $i);

    $str = preg_replace($meta_content_type_existing, $utf8_head, $str);
    $str = preg_replace("#<head.*?>#i", "$0$utf8_head", $str);

    if($charset != "utf-8")
        $str = mb_convert_encoding($str, "UTF-8", $charset);

    $str = html_entity_decode($str, ENT_COMPAT, "UTF-8");
    $str = preg_clean('\r', $str, false);

    return $str;
  }
}

