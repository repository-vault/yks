<?

    //register additionnal classes paths
classes::register_class_paths(array(
    "__native"            => LIBS_PATH."/natives/__native.php",
    "__wrapper"           => LIBS_PATH."/natives/__wrapper.php",

    "exyks"               => CLASS_PATH."/exyks/exyks.php",


    "xsl"                 => CLASS_PATH."/stds/xsl.php",
    "xml"                 => CLASS_PATH."/stds/xml.php",
    "files"               => CLASS_PATH."/stds/files.php",
    "date"                => CLASS_PATH."/stds/date.php",
    "data"                => CLASS_PATH."/stds/data.php",

    "dsp"                 => CLASS_PATH."/dsp/display.php",
    "css_processor"       => CLASS_PATH."/dsp/css/processor.php",
    "isql"                => CLASS_PATH."/sql/isql.php",
    "sql"                 => CLASS_PATH."/sql/".SQL_DRIVER.".php",
    "ksql"                => CLASS_PATH."/sql/k".SQL_DRIVER.".php", //prototype
    "sql_func"            => CLASS_PATH."/sql/functions.php",
    "yks_list"            => CLASS_PATH."/list/yks_list.php",
    "dtd"                 => CLASS_PATH."/dom/dtds.php",

    "http"                => CLASS_PATH."/exts/http/http.php",
    "sock"                => CLASS_PATH."/exts/http/sock.php",

    "exyks_paths"         => CLASS_PATH."/exyks/paths.php",
    "tpls"                => CLASS_PATH."/exyks/tpls.php",
    "highlight_xml"       => CLASS_PATH."/dsp/code_format/highlight_xml.php",
    "json_parser"         => CLASS_PATH."/apis/json_parser.php",
    "mediawiki_auth_api"  => CLASS_PATH."/apis/mediawiki/AuthApi.php",

    "xsl_cache"           => CLASS_PATH."/xsl/generator.php",


    "imgs"                => CLASS_PATH."/imgs/imgs.php",
    "png"                 => CLASS_PATH."/apis/png/png.php",

    "cli"                 => CLASS_PATH."/cli/cli.php",
    "pclzip"              => CLASS_PATH."/exts/pclzip.php",
    "interactive_runner"  => CLASS_PATH."/exts/cli/interactive_runner.php",
    "win32_service"       => CLASS_PATH."/exts/cli/win32_service.php",
    "charset_map"         => CLASS_PATH."/stds/encodings/cp.php",


    "dom"                 => CLASS_PATH."/exts/selectors/dom.php",


    "yks_runner"          => CLTOOLS_PATH."/yks_runner.php",
));
