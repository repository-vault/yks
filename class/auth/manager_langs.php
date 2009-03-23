<?

$base = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
$accept_hash = md5($base);

unset($_SESSION['langs'][$accept_hash]);
if(!$user_lang = $_SESSION['langs'][$accept_hash]) {
    include "detect_lang.php";
    $langs = vals($types_xml->lang_key);
    $user_lang =  find_best_lang($base, $langs);
    $_SESSION['langs'][$accept_hash] = $user_lang;
}


define('USER_LANG', $user_lang);

$entities=yks::$get->get("entities", USER_LANG);

if($config->dyn_entitities)
    locale_renderer::init();