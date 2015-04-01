<?php

class http_progress_filter extends php_user_filter {
  var $start = 0;
  var $step=0;
  function filter($in, $out, &$consumed, $closing)
  {
    while ($bucket = stream_bucket_make_writeable($in)) {
      //$bucket->data = strtoupper($bucket->data);
      $consumed += $bucket->datalen;
      $this->start += $bucket->datalen;
      stream_bucket_append($out, $bucket);
    }
    if($this->step != ($this->step = floor($this->start / 102400) ))
      echo "Progress {$this->start} \n";
    return PSFS_PASS_ON;
  }
}
