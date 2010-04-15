<?php

class rfc_2047 {


  static function encoding_encode($str, $encoding = '7bit'){
        if($encoding == "quoted-printable") return self::quoted_printable_encode($str);
        elseif($encoding == "base64") return chunk_split(base64_encode($str));
        else return $str; //8bit LOL
    }

  static function header_encode($str){
    if(preg_match("#[^\x20-\x7E]#", $str))
        $str = "=?UTF-8?Q?".self::quoted_printable_encode($str,1024,true)."?=";

    return $str;
  }

  static function  quoted_printable_encode_native($str) {
    $fp = fopen("php://memory", 'r+');

    stream_filter_append($fp, 'convert.quoted-printable-encode', STREAM_FILTER_READ);
    fwrite($fp, $str); rewind($fp);$str = stream_get_contents($fp);
    return $str;
  }


  static function quoted_printable_encode($input){
    $bEmulate_imap_8bit=true;

    $aLines = preg_split("/(?:\r\n|\r|\n)/", $input);

    for ($i=0;$i<count($aLines);$i++) {
    $sLine =& $aLines[$i];
    if (strlen($sLine)===0) continue; // do nothing, if empty

    $sRegExp = '/[^\x09\x20\x21-\x3C\x3E-\x7E]/e';

    // imap_8bit encodes x09 everywhere, not only at lineends,
    // for EBCDIC safeness encode !"#$@[\]^`{|}~,
    // for complete safeness encode every character :)
    if ($bEmulate_imap_8bit)
      $sRegExp = '/[^\x20\x21-\x3C\x3E-\x7E]/e';

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
    preg_match_all( '/.{1,73}([^=]{0,2})?/', $sLine, $aMatch );
    $sLine = implode( '=' . chr(13).chr(10), $aMatch[0] ); // add soft crlf's
    }

    // join lines into text
    return implode(chr(13).chr(10),$aLines);
  }

/**
    This application cause crashes, sometimes, ... GTFO !
  static function quoted_printable_encode3($input, $line_max = 75, $trim = false) {
   $hex = array('0','1','2','3','4','5','6','7',
                          '8','9','A','B','C','D','E','F');

**/


}