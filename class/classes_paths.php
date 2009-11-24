<?

    //register additionnal classes paths
classes::register_class_paths(array(
    "__native"            => LIBS_PATH."/natives/__native.php",
    "__wrapper"           => LIBS_PATH."/natives/__wrapper.php",

    "exyks"               => CLASS_PATH."/exyks/exyks.php",

    "mail"                => CLASS_PATH."/mails/mail.php",

    "xsl"                 => CLASS_PATH."/stds/xsl.php",
    "xml"                 => CLASS_PATH."/stds/xml.php",
    "files"               => CLASS_PATH."/stds/files.php",
    "date"                => CLASS_PATH."/stds/date.php",
    "data"                => CLASS_PATH."/stds/data.php",

    "users"               => CLASS_PATH."/users/users.php",
    "dsp"                 => CLASS_PATH."/dsp/display.php",
    "sql"                 => CLASS_PATH."/sql/".SQL_DRIVER.".php",
    "sql_func"            => CLASS_PATH."/sql/functions.php",
    "yks_list"            => CLASS_PATH."/list/yks_list.php",
    "dtd"                 => CLASS_PATH."/dom/dtds.php",
    "myks"                => CLASS_PATH."/myks/myks.php",
    "http"                => CLASS_PATH."/exts/http/http.php",
    "exyks_paths"         => CLASS_PATH."/exyks/paths.php",
    "tpls"                => CLASS_PATH."/exyks/tpls.php",
    "highlight_xml"       => CLASS_PATH."/dsp/code_format/highlight_xml.php",
    "rfc_2047"            => CLASS_PATH."/apis/net/2047.php",
    "json_parser"         => CLASS_PATH."/apis/json_parser.php",
    "win32_service"       => CLASS_PATH."/cli/win32_service.php",

    "myks_parser"         => CLASS_PATH."/myks/parser.php",
    "xsl_cache"           => CLASS_PATH."/xsl/generator.php",

    "cli"                 => CLASS_PATH."/cli/cli.php",
    "pclzip"              => CLASS_PATH."/exts/pclzip.php",
    "interactive_runner"  => CLASS_PATH."/cli/interactive_runner.php",
    "charset_map"         => CLASS_PATH."/stds/encodings/cp.php",


    "dom"                 => CLASS_PATH."/exts/selectors/dom.php",


    "yks_runner"          => CLTOOLS_PATH."/yks_runner.php",
    "myks_runner"         => CLTOOLS_PATH."/myks_runner.php",
    "sql_runner"          => CLTOOLS_PATH."/sql/runner.php",
    "sql_sync"            => CLTOOLS_PATH."/sql/sync.php",
));
