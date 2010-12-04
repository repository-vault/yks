<?

class torrents {
  static function output(torrent $torrent){
    $str = "";
    $str .="<h2>{$torrent['info']['name']}</h2>";

    $str .= "<p>Tracker(s) : ".join(', ', $torrent->trackers)."</p>";
    $files = array();
    foreach($torrent['info']['files'] as $file)
      $files[] = "<tr>
          <td>".join(DIRECTORY_SEPARATOR, $file['path'])."</td>
          <td>".dsp::file_size($file['length'])."</td>
      </tr>";
    if(!$files) $files = "<tr class='line_fail'><td colspan='2'>Aucun fichier</td></tr>";
    else $files = join('', $files);
    $str .= "<p>Files</p>";
    $str .= "<table border='1'><tr class='line_head'><th>File</th><th>Size</th></tr>$files</table>";
    return $str;
  }
}

