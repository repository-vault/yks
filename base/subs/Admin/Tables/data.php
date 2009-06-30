<?
if(!$query_infos) abort(405);
$export = (bool) $sub0;

$query_params = $query_infos['params'];
$params_values = sess::retrieve('query_params');
$sql_query = $query_infos['query_def'];

$config->head->title = $query_infos['query_name'];


$ready = true;
if($query_params) {
    foreach($query_params as $param_name=>$param_infos){
        $ready &= isset($params_values[$param_name]);
        $sql_query = strtr($sql_query, array('$'.$param_name=>$params_values[$param_name]));
    }
}

if(!$ready) return;


$res = sql::query($sql_query);

$cols = array();
  for ($i = 0, $max=pg_num_fields($res); $i < $max; $i++) {
    $cols[$fieldname = pg_field_name($res, $i)] = array(
        'name'=>$fieldname ,
        'type'=>pg_field_type($res, $i),
    );
  }

sql::reset($res);
$data = sql::brute_fetch();


if($export){
    header(sprintf(HEADER_FILENAME_MASK, $config->head->title.".xls"));
    exyks::$headers["excel-server"] = TYPE_CSV;
    exyks::store('XSL_SERVER_PATH', RSRCS_PATH."/xsl/specials/excel.xsl");
    exyks::store('RENDER_SIDE', 'server');
    exyks::store('RENDER_MODE', 'excel');
    exyks::store('RENDER_START', '<html');

}

