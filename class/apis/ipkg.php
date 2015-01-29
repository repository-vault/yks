<?php

class ipkg {
  public static function controlindex($file_path, $file_name = null){
    $control   = self::extract_control($file_path);
    $data = array(
        'Filename' => pick($file_name, basename($file_path)),
        'Size'     => filesize($file_path),
        'MD5Sum'   => md5_file($file_path),
    );
    $control = preg_replace("/^Description:/m", mask_join("\n", $data, '%2$s: %1$s'). "\n\\0", $control);
    return $control . "\n";
  }

  public static function extract_control($archive){
    $tmp_out = files::tmppath();
      //stdout redirect mess with newlines style, use tee pipe instead
    $cmd = "tar -xzOf $archive ./control.tar.gz | tar -xzOf - ./control | tee $tmp_out";
    exec($cmd, $out, $exit);
    if($exit !== 0)
      throw new Exception("Invalid tar command");
    $contents = file_get_contents($tmp_out);
    unlink($tmp_out);
    return $contents;
  }

  public static function mk2($control_files, $files_list){

    $tmp_dir = files::tmppath();
    files::create_dir($tmp_dir);

    foreach(array('data' => $files_list, 'control' => $control_files) as $dir => $files_list)
    foreach($files_list as $file_name => $file_paths) {
      $file_paths = is_array($file_paths) ? $file_paths : [$file_paths];
      foreach($file_paths as $file_path) {
        files::create_dir(dirname($dest = "$tmp_dir/$dir/$file_name"));
        $cmd = "rsync -a $file_path $dest";
        passthru($cmd);
      }
    }

    $archive = self::forge($tmp_dir);
    files::empty_dir($tmp_dir);
    return $archive;


    $data    = files::tar($files_list);
    $control = files::tar($control_files);
    file_put_contents($extras = files::tmppath(), "2.0\n");
    $archive = files::tar(array(
        './debian-binary'  => $extras,
        './control.tar.gz' => $control,
        './data.tar.gz'    => $data,
    ));
    unlink($data); unlink($control); unlink($extras);
    return $archive;
  }

  public static function forge($path){
    $path = realpath($path);
    $old  = getcwd(); $tmp_dir = files::tmppath();
    files::create_dir($tmp_dir); chdir($tmp_dir);
    exec("tar --owner=0 --group=0 -C $path/control -cvzf control.tar.gz  .");
    exec("tar --owner=0 --group=0 -C $path/data -cvzf data.tar.gz  .");
    file_put_contents("debian-binary", "2.0\n");


    $name = basename(files::tmppath());
    exec("tar -C . -cvzf ../$name .");
    $archive = realpath("../$name");
    chdir($old);

    files::delete_dir($tmp_dir, true);

    return $archive;
  }
}
