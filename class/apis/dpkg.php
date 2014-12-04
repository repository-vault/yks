<?

class dpkg {

  public static function extract_control($package_file){
    return trim(`dpkg-deb -I $package_file control 2>/dev/null`);
  }

  /**
   * Return the current version of an installed dpkg package.
   *
   * @param string $package_name
   * @return string|null version or null if the package is not installed.
   */
  public static function package_version($package_name)
  {
    $version = exec(sprintfshell(
      'dpkg-query -W -f \'${Version}\' %s 2>/dev/null',
      $package_name
    ), $exit);

    // Bad exit on no package found, empty string on no installed version.
    if($exit !== 0 || !strlen($version))
        return null;

    return $version;
  }


  public static function parse_control_file($control_file)
  {
    return self::parse_control(file_get_contents($control_file));
  }


  public static function parse_dependency($dependency_raw) {
    $PATTERN_DEPENDENCIES = <<<EOS
    /
      (?P<package_name>[\w\d-]+)\s*
      (?:\(\s*
          (?P<version_operator>[<>=]{1,3})? \s* (?P<package_version>[\d\.\-]+)
       \s*\))?\s*
      (?:\[\s*
        (?P<arch_operator>[!])? \s* (?P<restricted_arch>[\w0-9-]+)
      \s*\])?\s*
    /x
EOS;

    $dependency = compact('dependency_raw');

      if(preg_match($PATTERN_DEPENDENCIES, $dependency_raw, $out))
        $dependency = array_merge($dependency, array_sort($out, array('package_name', 'version_operator', 'package_version', 'restricted_arch', 'arch_operator')));

    return $dependency;
  }

  public static function parse_dependencies($depend_line){
    $dependencies = array();
    $matches_dependencies = preg_split("#\s*,\s*#", $depend_line, -1, PREG_SPLIT_NO_EMPTY);
    foreach($matches_dependencies as $dependency_part)
        $dependencies[] = self::parse_dependency($dependency_part);

    return $dependencies;
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


  public static function write_control($blocks)
  {
    $control = "";
    foreach ($blocks as $block) {
      $control .= mask_join("\n", $block, '%2$s: %1$s')."\n\n";
    }
    return $control;
  }

  public static function write_control_file($blocks, $control_path)
  {
    $control = self::write_control($blocks);
    file_put_contents($control_path, $control);
  }
}
