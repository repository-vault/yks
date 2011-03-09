<?

$fields = array('talk_content');

if($action == "talk_manage") try {

    $data = array(
        'talk_content' => $_POST['talk_content'],
    );
    $talk->update($data);

    rbx::ok("Modification enregistrées");
}catch(rbx $e){}