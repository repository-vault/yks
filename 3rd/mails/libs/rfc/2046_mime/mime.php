<?php


abstract class mime extends mail_base {

  function trace() {
    return array(
        'to'       => join(', ', $this->to),
        'cc'       => join(', ', $this->cc),
        'subject'  => $this->apply_context($this->subject),
        'contents' => $this->encode(true),
    );
  }
 
  function output_headers( $headers=array() ){
    $subject = $this->apply_context($this->subject);
    $headers = array_filter(array_merge(array(
        "Subject"     => rfc_2047::header_encode($subject),
        "From"        => $this->from,
        "To"          => join(', ', $this->to),
        "CC"          => join(', ', $this->cc),
    ),$headers)); $headers = mask_join(CRLF,$headers, '%2$s: %1$s').CRLF;

    return $headers;
  }

}



