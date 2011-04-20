<?
/*

Array
(
  [format] => flv
  [file_path] => water_drop.flv
  [duration] => 00:00:27.2
  [streams] => Array
    (
    [0] => Array
        (
          [stream_type] => Video
          [stream_format] => flv
          [stream_codec] => yuv420
          [stream_width] => 320
          [stream_height] => 240
          [stream_fps] => 30.00
        )

    [1] => Array
        (
          [stream_type] => Audio
        )

  )
)
*/

class ffmpeg_helper {
  private static $ffmpeg_path;

  public static function init(){
    if(!is_file(self::$ffmpeg_path = cli::which("ffmpeg.exe")))
      throw new Exception("Could not find ffmpeg");
  }
  public static function analyse($file_path){
    
    if(!is_file($file_path))
      throw new Exception("Invalid input file");
    return self::analyse_file($file_path);
  }

  private static function analyse_file($file_path){
    $ffmpeg_args = sprintf('-i "%s"', $file_path); //might also work with multipe input:!!
    $output = procs::exec_all_pipes(self::$ffmpeg_path, $ffmpeg_args);
    //les sources sont decrites sur ^Input: #1 puis des lignes par stream et decalées en whitespaces
    if(!preg_match_all("#\r?\nInput \#([0-9]+).*(?:\r?\n\s+.*)+#", $output, $out))
      throw new Exception("Could not find input files");

    $input_contents = array_combine($out[1], $out[0]);
    $input_streams  = array();

    foreach($input_contents as $input_id=>$input_content){
      $input = null;
      if(!preg_match("#Input \#$input_id, (.*?),.*from '(.*?)'.*Duration: ([0-9:.]+)#s", $input_content, $out))
        continue;

      $input = array('format' => $out[1], 'file_path' => $out[2], 'duration' => $out[3]);
      if(!preg_match_all("#^\s+Stream \#$input_id.([0-9]+): (Video|Audio):(.*?)$#m", $input_content, $out, PREG_SET_ORDER))
        continue;

      foreach($out as $stream) {
        list(, $stream_id, $stream_type, $stream_details) = $stream;
        if($stream_type == 'Video') {
          $details = preg_split("#, #", trim($stream_details));
          list($stream_format, $stream_codec, $stream_dim, $stream_fps) = $details;
          list($stream_width, $stream_height) = explode('x', preg_reduce("#^([0-9]+x[0-9]+)#", $stream_dim));
          $stream_fps = preg_reduce("#^([0-9.]+)#", $stream_fps);
          $input['streams'][$stream_id] = compact('stream_type', 'stream_format', 'stream_codec', 'stream_width', 'stream_height', 'stream_fps');
        } else $input['streams'][$stream_id] = compact('stream_type');
      }

      //is it a simple file ?
      $video_streams = array();
      foreach($input['streams'] as $stream)
        if($stream['stream_type'] == 'Video') $video_streams[] = $stream;
      if(count($video_streams)==1 && $stream = $video_streams[0])
        $input = array_merge($input, array(
          'movie_width'  => $stream['stream_width'],
          'movie_height' => $stream['stream_height'],
          'movie_fps'    => $stream['stream_fps'],
      ));

      $input['movie_ratio'] = self::detect_ratio($input['movie_width'],$input['movie_height']);
      $input_streams[$input_id] = $input;
    }
    
    return $input_streams[0];
  }

  private static function detect_ratio($width, $height){
    $ratio = $width/$height;
    if(self::isNear(16/9, $ratio, 0.1))
      return "16_9";
    if(self::isNear(9/16, $ratio, 0.1))
      return "9_16";

    return "4_3";
  }

  private static function isNear($x, $y, $pc){
    return abs($x-$y) < $x * $pc;
  }

}

