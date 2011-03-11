<?

$fields = array('talk_content');

if($action == "talk_manage") try {

    $data = array(
        'talk_content' => $_POST['talk_content'],
        'talk_title'   => $_POST['talk_title'],
        'talk_date'    => date::validate($_POST['talk_date'], DATETIME_MASK),
    );
    $talk->update($data);

    rbx::ok("Modification enregistrées");
}catch(rbx $e){}