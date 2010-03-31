<?php


abstract class mime extends mail_base {


  function output_headers( $headers=array() ){
    $headers = array_filter(array_merge(array(
        "Subject"     => rfc_2047::header_encode(
                            locales_manager::translate(
                                specialchars_decode($this->subject))),
        "From"        => $this->from,
        "To"          => join(', ', $this->to),
        "CC"          => join(', ', $this->cc),
    ),$headers)); $headers = mask_join(CRLF,$headers, '%2$s: %1$s').CRLF;

    return $headers;
  }

}



