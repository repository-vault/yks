<?

    //register additionnal classes paths
classes::register_class_paths(array(
    "__native"            => LIBS_PATH."/natives/__native.php",
    "__wrapper"           => LIBS_PATH."/natives/__wrapper.php",

    "exyks"               => CLASS_PATH."/exyks/exyks.php",


    "php"                 => CLASS_PATH."/stds/php.php",
    "xsl"                 => CLASS_PATH."/stds/xsl.php",
    "xml"                 => CLASS_PATH."/stds/xml.php",
    "files"               => CLASS_PATH."/stds/files.php",
    "procs"               => CLASS_PATH."/stds/procs.php",
    "mime_types"          => CLASS_PATH."/stds/mime/types.php",
    "date"                => CLASS_PATH."/stds/date.php",
    "data"                => CLASS_PATH."/stds/data.php",
    "crypt"               => CLASS_PATH."/stds/crypt.php",
    "idn"                 => CLASS_PATH."/stds/idn.php",

    "dsp"                 => CLASS_PATH."/dsp/display.php",
    "css_processor"       => CLASS_PATH."/dsp/css/processor.php",
    "data_headers"        => CLASS_PATH."/dsp/data_headers.php",
    "navigation"          => CLASS_PATH."/dsp/navigation.php",
    "isql"                => CLASS_PATH."/sql/isql.php",
    "sql_tunner"          => CLASS_PATH."/sql/tunner.php",

    "_sql_pdo"            => CLASS_PATH."/sql/kpdo.php",
    "_sql_pgsql"          => CLASS_PATH."/sql/kpgsql.php",
    "_sql_mysqli"         => CLASS_PATH."/sql/kmysqli.php",
    "_sql_mysql"          => CLASS_PATH."/sql/kmysql.php",

    "_storage_apc"         => CLASS_PATH."/stds/storage/apc.php",
    "_storage_var"         => CLASS_PATH."/stds/storage/var.php",
    "_storage_sql"         => CLASS_PATH."/stds/storage/sql.php",
    "_storage_redis"       => CLASS_PATH."/stds/storage/redis.php",

    "timeout"              => CLASS_PATH."/stds/timeout.php",

    "sql_func"             => CLASS_PATH."/sql/functions.php",
    "yks_list"             => CLASS_PATH."/list/yks_list.php",
    "dtd"                  => CLASS_PATH."/dom/dtds.php",

    "telnet"               => CLASS_PATH."/exts/net/telnet.php",
    "http"                 => CLASS_PATH."/exts/http/http.php",
    "url"                  => CLASS_PATH."/exts/http/url.php",
    "tlds"                 => CLASS_PATH."/exts/http/tlds.php",
    "browser"              => CLASS_PATH."/exts/browser/browser.php",
    "urls"                 => CLASS_PATH."/exts/http/urls.php",
    "sock"                 => CLASS_PATH."/exts/http/sock.php",
    "http_aserver"         => CLASS_PATH."/exts/http/aserver.php",
    "http_server"          => CLASS_PATH."/exts/http/server.php",
    "http_proxy"           => CLASS_PATH."/exts/http/proxy.php",
    "http_server"          => CLASS_PATH."/exts/http/server.php",
    "http_aserver"         => CLASS_PATH."/exts/http/aserver.php",
    "http_cb_proxy"        => CLASS_PATH."/exts/http/proxy_callback.php",
    "http_progress_filter" => CLASS_PATH."/exts/http/progress_filter.php",
    "ftp_http_put"         => CLASS_PATH."/exts/http/ftp_http_put.php",
    "rsa_keymgr"           => CLASS_PATH."/apis/rsa_keymgr.php",
    "sys_wol"              => CLASS_PATH."/apis/wol.php",

    "exyks_paths"          => CLASS_PATH."/exyks/paths.php",
    "tpls"                 => CLASS_PATH."/exyks/tpls.php",
    "highlight_xml"        => CLASS_PATH."/dsp/code_format/highlight_xml.php",
    "json_parser"          => CLASS_PATH."/apis/json_parser.php",
    "exyks_auth_api"       => CLASS_PATH."/apis/auth/api.php",
    "WSAuthBasic"          => CLASS_PATH."/apis/auth/WSAuthBasic.php",
    "WSAuthExternal"       => CLASS_PATH."/apis/auth/WSAuthExternal.php",
    "stdflow_filter"       => CLASS_PATH."/apis/stdflow_filter.php",
    "php_legacy"           => CLASS_PATH."/apis/legacy.php",
    "unix"                 => CLASS_PATH."/apis/unix.php",
    "yphar"                => CLASS_PATH."/apis/yphar.php",
    "ssh_agent_helper"     => CLASS_PATH."/apis/ssh_agent.php",
    "dpkg"                 => CLASS_PATH."/apis/dpkg.php",
    "ipkg"                 => CLASS_PATH."/apis/ipkg.php",
    "yksauthdrupal"        => CLASS_PATH."/apis/drupal/AuthDrupal.php",


    "xsl_cache"            => CLASS_PATH."/xsl/generator.php",


    "imgs"                 => CLASS_PATH."/imgs/imgs.php",
    "ocr"                  => CLASS_PATH."/imgs/ocr.php",
    "png"                  => CLASS_PATH."/apis/png/png.php",
    "ffmpeg_helper"        => CLASS_PATH."/apis/ffmpeg/helper.php",

    "cli"                  => CLASS_PATH."/cli/cli.php",
    "cli_tail"             => CLASS_PATH."/cli/tail.php",
    "pclzip"               => CLASS_PATH."/exts/pclzip.php",
    "interactive_runner"   => CLASS_PATH."/exts/cli/interactive_runner.php",
    "namedpipewrapper"     => CLASS_PATH."/exts/cli/namedpipewrapper.php",
    "stdlogdispatch"       => CLASS_PATH."/exts/cli/stdlogdispatch.php",
    "win32_service"        => CLASS_PATH."/exts/cli/win32_service.php",
    "win32_cli"            => CLASS_PATH."/exts/cli/win32_cli.php",

    "charset_map"          => CLASS_PATH."/stds/encodings/cp.php",


    "dom"                  => CLASS_PATH."/exts/selectors/dom.php",
    "xml_to_xlsx"          => CLASS_PATH."/exts/xlsx/xml_to_xlsx.php",
    "xlsx_style"           => CLASS_PATH."/exts/xlsx/xlsx_style.php",
    "yks_runner"           => CLTOOLS_PATH."/yks_runner.php",
    "install_runner"       => CLTOOLS_PATH."/install_runner.php",

    "wordfieldcomplex"  => CLASS_PATH."/exts/docx/WordFieldComplex.php",
    "wordfieldsimple"   => CLASS_PATH."/exts/docx/WordFieldSimple.php",
    "wordfield"         => CLASS_PATH."/exts/docx/WordField.php",
    "wordfieldfactory"  => CLASS_PATH."/exts/docx/WordFieldFactory.php",
    "wordns"            => CLASS_PATH."/exts/docx/WordNS.php",
    "wordtemplate"      => CLASS_PATH."/exts/docx/WordTemplate.php",
    "wordtemplateimg"   => CLASS_PATH."/exts/docx/WordTemplateImg.php",
    "wordtemplatetable" => CLASS_PATH."/exts/docx/WordTemplateTable.php",
    "torrent"              => CLASS_PATH."/exts/torrents/torrent.php",
    "bencode"              => CLASS_PATH."/exts/torrents/bencode.php",
    "torrents"             => CLASS_PATH."/exts/torrents/torrents.php",

    "cache"                => CLASS_PATH."/cache/cache.php",
));

if(SQL_DRIVER == "pgsql")
    classes::register_class_path("sql", CLASS_PATH."/sql/pgsql.php");

classes::register_alias("sql",     "_sql_".SQL_DRIVER);
classes::register_alias("storage", "_storage_".STORAGE_DRIVER);
