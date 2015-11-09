<?
  $category_id = (string)$sub1;
  $action = (string) $argv0;

  if($category_id) $category = category::instanciate($category_id);

  if($action == "search") {
    $result = array();
    $where = "category_name ILIKE '%{$_POST['search']}%'
              OR category_id ILIKE '%{$_POST['search']}%'";
    $category_list = category::from_where($where);

    foreach ($category_list as $category) {
      $result[] = array($category->category_id, $category->category_id. " - " .$category->category_name);
    }

    echo json_encode($result);
    die; // AJAX !
  }