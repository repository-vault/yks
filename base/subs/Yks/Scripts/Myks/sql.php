<?
include_once "types.php";
 rbx::title("Starting SQL driver ".SQL_DRIVER);

$limit = $sub0;
$limits = array('procedures', 'views', 'tables');
if(!in_array($limit, $limits)) $limit = false;
$only_stuff = $sub1;
$only_stuff = "#".str_replace("*", ".*", $only_stuff)."#";

    myks_gen::init($types_xml);
   
   include "tmp_views.php";


    myks_gen::reset_types();
    $deps_views = array();
    myks_gen::$tables_ghosts_views = array_keys($views_list); //for table ghost exclusion

 if(!$limit || $limit=='views') {
   rbx::title("Analysing views");
   foreach($views_list as $view_name=>$view_infos){

        $parent_has_been_reloaded_and_so_should_i_be = (bool)array_intersect(
            $view_infos['dependencies'],
            $deps_views
        );
        $view_xml = $view_infos['xml'];
        $name = (string)$view_xml['name'];
        if($only_stuff && !preg_match($only_stuff, $name)) continue;

        list($res, $cascade) = myks_gen::view_check($view_xml, $parent_has_been_reloaded_and_so_should_i_be);
        if(!$res) {
            rbx::ok("-- Nothing to do in $name");
            continue;
        }
        rbx::ok("-- New definition for $name");
        echo $res;
        rbx::ok("-- end of definition");

        if($cascade) $deps_views[] = $name; //we worked on that view

   }
   rbx::line();
 }

    myks_gen::reset_types();
 if(!$limit || $limit=='procedures'){
   rbx::title("Analysing stored procedures");
   foreach($procedures_xml->procedure as $procedure_xml){
        $name = (string)$procedure_xml['name'];
        if($only_stuff && !preg_match($only_stuff, $name)) continue;

        $res = myks_gen::procedure_check($procedure_xml);
        if(!$res) {
            rbx::ok("-- Nothing to do in $name");
            continue;
        }

        rbx::ok("-- New definition for $name");
        echo $res;
        rbx::ok("-- end of definition");
   }
   rbx::line();
 }


    myks_gen::reset_types();
 if(!$limit || $limit=='tables'){
   rbx::title("Analysing database structure");
   foreach($tables_xml->table as $table_xml){
        $name=(string)$table_xml['name'];
        if($only_stuff && !preg_match($only_stuff, $name)) continue;

        $res=myks_gen::table_check($table_xml);
        if(!$res) {
            rbx::ok("-- Nothing to do in $name");
            continue;
        };
        echo sql::unfix($res); flush();
    }
    rbx::line();
 }
