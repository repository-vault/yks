<?

list($sub0, $sub1, $sub2, $sub3) = array(null,null,null,null);


    include "types.php";
    include "xsl.php";
    include "trad.php";


    if($process_sql)
        include "sql.php";

rbx::ok("Done");

die(sys_end(exyks::tick('generation_start')));
