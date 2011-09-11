<?

class torrents {
  static function output(torrent $torrent){
    $str = "";
    $str .="<h2>{$torrent['info']['name']}</h2>";

    $trackers = $torrent->trackers;
    $str .= "<p>Tracker(".count($trackers).") : ".join(', ', $trackers)."</p>";
    $files = array();
    foreach($torrent->files as $file)
      $files[] = "<tr>
          <td>{$file['path']}</td>
          <td>".dsp::file_size($file['size'])."</td>
      </tr>";

    if(!$files) $files = "<tr class='line_fail'><td colspan='2'>Aucun fichier</td></tr>";
    else $files = join('', $files);
    $str .= "<p>Files</p>";
    $str .= "<table border='1'><tr class='line_head'><th>File</th><th>Size</th></tr>$files</table>";
    return $str;
  }

  function get_trackers(torrent $torrent){
    $ret = array($torrent['announce']);
    if($torrent['announce-list'])
      foreach($torrent['announce-list'] as $tracker)
        foreach($tracker as $announce)
          $ret[] = $announce;
    return array_unique(array_filter($ret));
  }

  function get_files(torrent $torrent){
    $files = array();
    if($torrent['info']['files'])
      foreach($torrent['info']['files'] as $file)
        $files[] = array(
          'size' => $file['length'],
          'path' => join(DIRECTORY_SEPARATOR, $file['path']),
        );
    else $files[] = array(
          'size' => $torrent['info']['length'],
          'path' => $torrent['info']['name'],
    );
    return $files;
  }
}

