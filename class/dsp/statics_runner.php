<?

class statics_runner {

/**
* @alias css
*/
  public static function styles(){
    $style = yks::$get->config->style;
      $output_file = $style['target'];
      if($style['name'] == "legacy"){
        file_put_contents($output_file, css_processor::delivers($style->file['source'], true));
      }
      if($style['name'] == "stylus"){
        file_put_contents($output_file, css_processor::delivers_stylus($style->file['source']));
      }
      rbx::ok("Generated file {$output_file}");
  }
}