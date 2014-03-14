<?


class dpkg {
  public static function package_version($package_name){
    return trim(`dpkg-query -W -f '\${version}'  $package_name 2>/dev/null`);
  }
}