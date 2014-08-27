<?
class dpkg_test {
/**
* @alias run
*/
  function test_all() {

    $this->assertEquals(dpkg::parse_dependency("activisu-database (= 2014.2.18.0) [melon]"), array(
      "dependency_raw"   => "activisu-database (= 2014.2.18.0) [melon]",
      "package_name"     => "activisu-database",
      "version_operator" => "=",
      "package_version"  => "2014.2.18.0",
      "restricted_arch"  => "melon",
      "arch_operator"    => "",
    ));

    $this->assertEquals(dpkg::parse_dependency("  activisu-database  "), array(
      "dependency_raw"   => "  activisu-database  ",
      "package_name"     => "activisu-database",
    ));


    $this->assertEquals(dpkg::parse_dependency("yks [!i386]"), array(
      'dependency_raw'   => 'yks [!i386]',
      'package_name'     => 'yks',
      'version_operator' => '',
      'package_version'  => '',
      'restricted_arch'  => 'i386',
      'arch_operator'    => '!',
    ));

    die("yatta");
  }

  // use phpunithere
  private function assertEquals($a,$b){
    if($a != $b)
      throw new Exception("Nope ".var_export($a,1)." != ".var_export($b,1));
  }
}