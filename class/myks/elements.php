<?

include_once "elements/mykse.php";
include_once "elements/table.php";
include_once "elements/view.php";
include_once "elements/procedure.php";
include_once "elements/resolver.php";

$driver = SQL_DRIVER;
include_once "elements/drivers/$driver/mykse.php";
include_once "elements/drivers/$driver/table.php";
include_once "elements/drivers/$driver/resolver.php";
include_once "elements/drivers/$driver/view.php";
include_once "elements/drivers/$driver/procedure.php";

