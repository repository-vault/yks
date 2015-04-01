<?php

$report_to = specialchars_decode($config->bug['report_to']);

if(!$report_to) abort(452);


$name_mask = "#(.*?)\s*<[^<]*>#";
$webmaster_name = preg_match($name_mask, $report_to,$out)?$out[1]:"webmaster";

if(!sess::$connected) return;

if($action=="bug_report") try {

    $report_contents = txt::rte_clean($_POST['report_contents']).CRLF;
    $report_contents.= CRLF.print_r(sess::$sess,1);
    $report_contents.= CRLF.print_r($_SERVER,1);
    $constants=get_defined_constants(true);$constants=$constants['user'];
    $report_contents.= CRLF.print_r($constants,1);

    smtp_lite::smtpmail($report_to, "[".SITE_DOMAIN."] Bug report", $report_contents);

    rbx::ok("Bug report has been sucessfully sent to «".specialchars_encode($report_to)."»");

}catch(rbx $e){}
catch(Exception $e){ rbx::error("Error while reporting bug report."); }
