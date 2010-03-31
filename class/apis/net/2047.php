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

  static function quoted_printable_encode($input, $line_max = 75, $trim = false) {
   $hex = array('0','1','2','3','4','5','6','7',
                          '8','9','A','B','C','D','E','F');
   $lines = preg_split("/(?:\r\n|\r|\n)/", $input);
   $linebreak = "=0D=0A=\r\n";
   /* the linebreak also counts as characters in the mime_qp_long_line
    * rule of spam-assassin */
   $line_max = $line_max - strlen($linebreak);
   $escape = "=";
   $output = "";
   $cur_conv_line = "";
   $length = 0;
   $whitespace_pos = 0;
   $addtl_chars = 0;

   for ($j=0; $j<count($lines); $j++) {
     $line = $lines[$j];
     $linlen = strlen($line);

     for ($i = 0; $i < $linlen; $i++) {
       $c = substr($line, $i, 1);
       $dec = ord($c);

       $length++;

       if ($dec == 32) {
       // space occurring at end of line, need to encode
      if (($i == ($linlen - 1))) {
         $c = "=20";
         $length += 2;
      }

      $addtl_chars = 0;
      $whitespace_pos = $i;
    } elseif ( ($dec == 61) || ($dec < 32 ) || ($dec > 126) ) {
      $h2 = floor($dec/16); $h1 = floor($dec%16);
      $c = $escape . $hex["$h2"] . $hex["$h1"];
      $length += 2;
      $addtl_chars += 2;
    }

    // length for wordwrap exceeded, get a newline into the text
    if ($length >= $line_max) {
      $cur_conv_line .= $c;

      // read only up to the whitespace for the current line
      $whitesp_diff = $i - $whitespace_pos + $addtl_chars;
      $output .= substr($cur_conv_line, 0,
                            (strlen($cur_conv_line) - $whitesp_diff)) .
                            $linebreak;

      /* the text after the whitespace will have to be read
          * again ( + any additional characters that came into
          * existence as a result of the encoding process after the whitespace) */
      $i =  $i - $whitesp_diff + $addtl_chars;

      $cur_conv_line = "";
      $length = 0;
      $whitespace_pos = 0;
    } else {
      // length for wordwrap not reached, continue reading
      $cur_conv_line .= $c;
    }
     } // end of for

     $length = 0;
     $whitespace_pos = 0;
     $output .= $cur_conv_line;
     $cur_conv_line = "";

     if ($j<=count($lines)-1) {
       $output .= $linebreak;
     }
  }
    if($trim && ends_with($output, $linebreak))
        $output = substr($output, 0, -strlen($linebreak));
    return trim($output);
  }
}