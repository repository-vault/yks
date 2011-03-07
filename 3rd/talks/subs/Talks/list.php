<?
$page_id = (int)$sub0;
$by = 10;
$start = $page_id * $by;



if($action == "delete") try{
    $node_id = $_POST['talk_id'];
    $node = talk::instanciate($node_id);
    $talk->abandon($node);

    jsx::$rbx = false;
} catch(rbx $e){}




$max  =$talk->children_nb;
$children = $talk->get_children_range($start, $by);
$pages = dsp::pages($max, $by, $page_id, "/?$href//");
