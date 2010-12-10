<?


class stdflow_filter extends php_user_filter {
  private static $FILTER_NAME = __CLASS__;
  private static $callbacks = array();

 public static function init(){
   stream_filter_register(self::$FILTER_NAME.".*", __CLASS__);
 }
 
 public static function transform($str, $filter_name, $callback = false){
  $fp = fopen("php://memory", 'r+');
  $res = @stream_filter_append($fp, $filter_name, STREAM_FILTER_READ);
  if(!$res && $callback) {
      $filter_name = self::make_filter($callback);
      $res = stream_filter_append($fp, $filter_name, STREAM_FILTER_READ);
    }
    fwrite($fp, $str);
    rewind($fp);
    $str = stream_get_contents($fp);
    return $str; 
 }

 private static function make_filter($callback){
     $filter_name = self::$FILTER_NAME.".".substr(md5(serialize($callback)),0,10);
     self::$callbacks[$filter_name] = $callback;
     return $filter_name;
 }

  function filter($in, $out, &$consumed, $closing)
  {
    $callback = self::$callbacks[$this->filtername];

    while ($bucket = stream_bucket_make_writeable($in)) {
      $consumed += $bucket->datalen;
      $bucket->data = (string)call_user_func($callback, $bucket->data);
      stream_bucket_append($out, $bucket);
    }
    return PSFS_PASS_ON;
  }
}