<?

$talk_root  = (int) exyks::$get->module->config['root'];

if(!$talk_root)
    die("No talk['root']!!");
$talk_id    = (int) $sub0;
if(!$talk_id)
    reloc("/?$href_fold//$talk_root");


$talk     = talk::instanciate($talk_id);
$parents  = $talk->parents;

$parents[$talk_id] = $talk;
$parents_path = array();
foreach($parents as $node_id => $node)
    $parents_path[] = "<a href='/?$href_fold//$node_id'>$node</a>";
$parents_path = join(' &gt; ',$parents_path);
