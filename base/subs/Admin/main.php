<?
$types_xml = yks::$get->types_xml;
auth::verif("admin","access",403);
include "$class_path/sql/functions.php";


tpls::$nav=array_merge(tpls::$nav, array(
    "/Admin/Users"=>array(
        'href'=>'/?/Admin/Users',
        'title'=>'Utilisateurs',
        'target'=>'_top',
        'access'=> array('admin'=>'admin')
    ),

    "/Admin/Mails"=>array(
        'href'=>'/?/Admin/Mails',
        'title'=>'Mails',
        'target'=>'_top',
        'access'=> array('admin'=>'admin')
    ),


));
