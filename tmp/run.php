<?
include 'yks/cli.php';

include 'xml_to_xlsx.php';

$current_dir    = realpath(dirname(__FILE__));
$excel_dir      = $current_dir.'/zipbase/';
$data_xml_file  = $current_dir."/data.xml";


$xlsx = new xml_to_xlsx($excel_dir, $data_xml_file);
$xlsx->create();
$xlsx->save($current_dir.'/tmp/');