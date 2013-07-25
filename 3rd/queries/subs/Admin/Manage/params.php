<?

$param_type = $sub0;


//************ gestion des templates de sous parametrage ********
if($param_type) {
    tpls::top("Yks/blank", tpls::ERASE);
    tpls::body("$subs_fold/params/$param_type");
    tpls::bottom("Yks/blank", tpls::ERASE);
}
//***************************************************************

if($action == "param_trash") try {
    $param_id = (int)$_POST['param_id'];
    $param = new queries_param($param_id);
    $param->trash();
    jsx::$rbx = false;

} catch(rbx $e){}

if($action == "params_add") try {
    $data = array(
        'param_key'      => $_POST['param_key'],
        'param_type'     => $_POST['param_type'],
        'param_descr'    => $_POST['param_descr'],
        'param_nullable' => bool($_POST['param_nullable']),
        'param_multiple' => bool($_POST['param_multiple']),
    );

    $query_param = queries_param::create($data, $_POST);

    jsx::$rbx = false;


} catch(rbx $e){}



$params_list = queries_param::from_where(array("true"));



