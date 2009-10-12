<?

abstract class myks_base {
  const PAD = '==========';

  protected function sign($type, $name, $contents, $signature){
    $pad  = self::PAD;
    $mask = "$pad <definition signature='%2\$s'> $pad\r\n%1\$s\r\n$pad </definition> $pad\r\n";
    $tmp  = sprintf($mask, $contents, $signature);
    return sprintf("COMMENT ON %s %s IS %s", $type, $name, sprintf("E'%s'", addslashes($tmp)));
  }

  protected function parse_signature_contents($description){
    $pad  = self::PAD;
    $mask = "#{$pad} <definition\s+signature='([a-f0-9]+)'>"
           ." {$pad}[\r\n]+(.*?)[\r\n]+{$pad} </definition> {$pad}[\r\n]?#s";

    preg_match($mask, $description, $out);

    if(!$out) return array();
    $data = array();
    $data['base_definition'] = myks_gen::sql_clean_def($out[2]);
    $data['signature'] = (string)$out[1];
    //initial comment ???
    return $data;
  }

  protected function crpt(){
    $args = func_get_args();
    return crpt(join('', $args),'FLAG_SQL',8);
  }

  protected function calc_signature(){}
}