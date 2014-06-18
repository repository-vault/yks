<?


class dpkg {

  public static function extract_control($package_file){
    return trim(`dpkg-deb -I $package_file control 2>/dev/null`);
  }

  public static function package_version($package_name){
    return trim(`dpkg-query -W -f '\${version}'  $package_name 2>/dev/null`);
  }


  public static function parse_control_file($control_file)
  {
    return self::parse_control(file_get_contents($control_file));
  }

  public static function parse_control($control)
  {
    $blocks = [];
    $current_block = 0;
    $index = null;
    $lines = explode("\n", $control);
    foreach ($lines as $line) {
      if (preg_match('/^\s*$/', $line)) {
        $current_block ++;
        $index = null;
        continue;
      }
      if (!preg_match('/^\s+/', $line)) {
          list($index, $value) = preg_split('#:\s*#', trim($line), 2);
          $blocks[$current_block][$index] = $value;
      } elseif ($index) {
          $blocks[$current_block][$index] .="\n".rtrim($line);
      }
    }
    $items = array('Package', 'Source');
    $ret = [];
    foreach ($blocks as $block) {
      $item = first(array_intersect($items, array_keys($block)));
      if (!$item)
        continue;
      $ret[$item] = $block;
    }

    return $ret;
  }

  public static function write_control_file($blocks, $control_path)
  {
    $control = "";
    foreach ($blocks as $block) {
      $control .= mask_join("\n", $block, '%2$s: %1$s')."\n\n";
    }
    file_put_contents($control_path, $control);
  }
}