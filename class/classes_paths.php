<?

    //register additionnal classes paths
classes::register_class_paths(array(
    "__native"            => LIBS_PATH."/natives/__native.php",
    "__wrapper"           => LIBS_PATH."/natives/__wrapper.php",

    "exyks"               => CLASS_PATH."/exyks/exyks.php",


    "xsl"                 => CLASS_PATH."/stds/xsl.php",
    "xml"                 => CLASS_PATH."/stds/xml.php",
    "files"               => CLASS_PATH."/stds/files.php",
    "mime_types"          => CLASS_PATH."/stds/mime/types.php",
    "date"                => CLASS_PATH."/stds/date.php",
    "data"                => CLASS_PATH."/stds/data.php",
    "crypt"               => CLASS_PATH."/stds/crypt.php",

    "dsp"                 => CLASS_PATH."/dsp/display.php",
    "css_processor"       => CLASS_PATH."/dsp/css/processor.php",
    "isql"                => CLASS_PATH."/sql/isql.php",

    "_sql_pdo"            => CLASS_PATH."/sql/kpdo.php",
    "_sql_pgsql"          => CLASS_PATH."/sql/kpgsql.php",
    "_sql_mysqli"         => CLASS_PATH."/sql/kmysqli.php",
    "_sql_mysql"          => CLASS_PATH."/sql/kmysql.php",

    "_storage_apc"        => CLASS_PATH."/stds/storage/apc.php",
    "_storage_var"        => CLASS_PATH."/stds/storage/var.php",
    "_storage_sql"        => CLASS_PATH."/stds/storage/sql.php",

    "timeout"             => CLASS_PATH."/stds/timeout.php",

    "sql_func"            => CLASS_PATH."/sql/functions.php",
    "yks_list"            => CLASS_PATH."/list/yks_list.php",
    "dtd"                 => CLASS_PATH."/dom/dtds.php",

    "http"                => CLASS_PATH."/exts/http/http.php",
    "urls"                => CLASS_PATH."/exts/http/urls.php",
    "sock"                => CLASS_PATH."/exts/http/sock.php",
    "http_proxy"          => CLASS_PATH."/exts/http/proxy.php",
    "http_cb_proxy"       => CLASS_PATH."/exts/http/proxy_callback.php",

    "exyks_paths"         => CLASS_PATH."/exyks/paths.php",
    "tpls"                => CLASS_PATH."/exyks/tpls.php",
    "highlight_xml"       => CLASS_PATH."/dsp/code_format/highlight_xml.php",
    "json_parser"         => CLASS_PATH."/apis/json_parser.php",
    "exyks_auth_api"      => CLASS_PATH."/apis/AuthApi.php",

    "yksauthdrupal"       => CLASS_PATH."/apis/drupal/AuthDrupal.php",


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

    "torrent"             => CLASS_PATH."/exts/torrents/bencode.php",
    "bencode"             => CLASS_PATH."/exts/torrents/bencode.php",
    "torrents"            => CLASS_PATH."/exts/torrents/torrents.php",

));

if(SQL_DRIVER == "pgsql")
    classes::register_class_path("sql", CLASS_PATH."/sql/pgsql.php");

classes::register_alias("sql",     "_sql_".SQL_DRIVER);
classes::register_alias("storage", "_storage_".STORAGE_DRIVER);
