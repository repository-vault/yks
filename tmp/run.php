<?
include 'yks/cli.php';

include 'xml_to_xlsx.php';

$current_dir    = realpath(dirname(__FILE__));
$excel_dir      = $current_dir.'/zipbase/';
$data_xml_file  = $current_dir."/data.xml";
$data_xml_file  = $current_dir."/test.xml";


$xlsx = new xml_to_xlsx($data_xml_file);
$xlsx->create();
$xlsx->save('test.xlsx');