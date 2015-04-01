<?php


if($action=="talk_manage") try {
    $data=array(
        'talk_title'=>$_POST['talk_title'],
        'talk_contents' => txt::rte_clean($_POST['talk_text']),
        'talk_lang'=>'fr-fr',
        'talk_author'=>$user_id,
    );

    if(!$data['talk_contents'])
        throw rbx::error("Veuillez saisir un texte");

    if($talk_id) 
        myks::update("ks_talks_list", $data, $verif_talk);
    else {
        $talk_id = myks::insert("ks_talks_list", $data, true);
        if(!$talk_id)
            throw rbx::error("Impossible de sauvegarder la news");
        $data=array(
            'talk_id'=>$talk_id,
            'parent_id'=>$parent_id
        );sql::insert("ks_talks_tree", $data);
    }


    rbx::ok("Talk enregistré $talk_id");

}catch(rbx $e){}