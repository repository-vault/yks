<?

//DebugBreak();

$mode = $locale_tag?"manage":"add";


  tpls::export(compact('mode'));


if($action=="tag_manage")try { 
    $data=array(
        'tag_name'   => $_POST['tag_name'],
        'tag_prefix' => $_POST['tag_prefix'],
        'project_id'   => $_POST['project_id'],
        'parent_tag'   => $_POST['parent_tag']?$_POST['parent_tag']:null,
    ); $locale_tag->update($data, $_POST['sshot_file']);
    jsx::js_eval(JSX_PARENT_RELOAD);
    jsx::js_eval("this.getBox().close();");
} catch(rbx $e){}


if($action=="tag_add")try{
    $data=array(
        'tag_name'   => $_POST['tag_name'],
        'tag_prefix' => $_POST['tag_prefix'],
        'parent_tag'   => $_POST['parent_tag']?$_POST['parent_tag']:null,
        'project_id'   => $_POST['project_id'],
    );
    
    $locale_tag = locale_tag_manager::create($data, $_POST['sshot_file']);

    //jsx::$rbx=false;
    jsx::js_eval(JSX_PARENT_RELOAD);
    rbx::ok("Votre tag a bien été enregistré {$locale_tag->tag_id}");
}catch(rbx $e){}
