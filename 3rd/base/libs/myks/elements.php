<?php

$here        = dirname(__FILE__);
$core        = "$here/core";
$elements    = "$here/elements";
$ds_elements = "$elements/drivers/".SQL_DRIVER;

classes::register_class_paths(array(

    "myks_parsed"      => "$core/myks_parsed.php",
    "myks_installer"   => "$core/myks_installer.php",

    "myks_collection"  => "$core/myks_collection.php",
    "table_collection" => "$core/table_collection.php",

    "myks_base"        => "$elements/base.php",
    "mykse_base"       => "$elements/mykse.php",
    "table_base"       => "$elements/table.php",
    "view_base"        => "$elements/view.php",
    "procedure_base"   => "$elements/procedure.php",
    "rules_base"       => "$elements/rules.php",
    "privileges"       => "$elements/privileges.php",
    "myks_triggers"    => "$elements/trigger.php",

    "mykse"            => "$ds_elements/mykse.php",
    "table"            => "$ds_elements/table.php",
    "resolver"         => "$ds_elements/resolver.php",
    "view"             => "$ds_elements/view.php",
    "procedure"        => "$ds_elements/procedure.php",
    "rules"            => "$ds_elements/rules.php",

    "base_type_resolver"        => "$elements/resolver.php",

));

