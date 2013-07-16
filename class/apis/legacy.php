<?
/* Legacy are php (now) native function */
class php_legacy {

  static function  header_remove($header_name) { 
    header("$header_name:", true);
  }

  static function stream_resolve_include_path($file_path){
    if(file_exists($file_path))
      return realpath($file_path);

    $paths = explode(PATH_SEPARATOR, get_include_path());
    foreach($paths as $path) 
      if(file_exists($path.DIRECTORY_SEPARATOR.$file_path))
          return realpath($path.DIRECTORY_SEPARATOR.$file_path);
    return false;
  }


    //try with filter first, then with the callback
  static function quoted_printable_encode_filter($str){
    $filter_name     = 'convert.quoted-printable-encode';
    $filter_options = array( 'line-break-chars' => "\n", 'line-length' => 76 );
    $filter_fallback = array('php_legacy', 'quoted_printable_encode');

    $str =  stdflow_filter::transform($str, $filter_name, $filter_fallback, $filter_options);
    return $str;
  }


  static function quoted_printable_encode($input, $line_max = 75){


    $bEmulate_imap_8bit = true;

    $aLines = preg_split("/(?:\r\n|\r|\n)/", $input);

    for ($i=0; $i<count($aLines); $i++) {
    $sLine =& $aLines[$i];
    if (strlen($sLine)===0) continue; // do nothing, if empty

    $sRegExp = '/([^\x09\x20\x21-\x7E]|[.=])/e';
    $sReplmt = 'sprintf( "=%02X", ord ( "$0" ) ) ;';
    $sLine = preg_replace( $sRegExp, $sReplmt, $sLine ); 

    // encode x09,x20 at lineends
    {
      $iLength = strlen($sLine);
      $iLastChar = ord($sLine{$iLength-1});

      //              !!!!!!!!   
      // imap_8_bit does not encode x20 at the very end of a text,
      // here is, where I don't agree with imap_8_bit,
      // please correct me, if I'm wrong,
      // or comment next line for RFC2045 conformance, if you like
      if (!($bEmulate_imap_8bit && ($i==count($aLines)-1)))
         
      if (($iLastChar==0x09)||($iLastChar==0x20)) {
        $sLine{$iLength-1}='=';
        $sLine .= ($iLastChar==0x09)?'09':'20';
      }
    }    // imap_8bit encodes x20 before chr(13), too
    
    
    // although IMHO not requested by RFC2045, why not do it safer :)
    // and why not encode any x20 around chr(10) or chr(13)
    if ($bEmulate_imap_8bit) {
      $sLine=str_replace(' =0D','=20=0D',$sLine);
      //$sLine=str_replace(' =0A','=20=0A',$sLine);
      //$sLine=str_replace('=0D ','=0D=20',$sLine);
      //$sLine=str_replace('=0A ','=0A=20',$sLine);
    }

    // finally split into softlines no longer than 76 chars,
    // for even more safeness one could encode x09,x20
    // at the very first character of the line
    // and after soft linebreaks, as well,
    // but this wouldn't be caught by such an easy RegExp
    preg_match_all( '/.{1,'.($line_max-3).'}([^=]{0,2})?/', $sLine, $aMatch );
    $sLine = implode( '=' . CRLF, $aMatch[0] ); // add soft crlf's
    }

    // join lines into text
    return implode(CRLF,$aLines);
  }
 
  
}