<?

if($action == "talk_create") try {
    sql::begin();
    $data = array(
        'talk_title' => $_POST['talk_title'],
        'user_id'    => USERS_ROOT,
    );
    $node = talk::create($data);
    $talk->adopt($node);
    rbx::ok("Talk create : {$node->talk_id}");
    jsx::js_eval(jsx::PARENT_RELOAD);

    sql::commit();
}catch(rbx $e){}