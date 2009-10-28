<?php

/*  "Yks config" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2007 
*/

class config  {
  static public $config;
  static public $config_file;

  static function get($var,$key=false){ 
    $tmp=self::$config->$var;
    return $key?($tmp[$key]?$tmp[$key]:$tmp->$key):$tmp;
  }
  static function retrieve($key){
    $tmp = self::$config->$key; $file = (string) $tmp['file'];
    if(!$tmp) return simplexml_load_string("<$key/>");
    return $file && file_exists($file)?simplexml_load_file($file):$tmp;
  }

  static function load($config_file){
    $config = simplexml_load_file($config_file);
    self::$config = &$config; self::$config_file = $config_file;
    $domain=parse_url($config->site['url']);
    if(!$domain['host']){
        $domain['host']=$_SERVER['SERVER_NAME'];
        $config->site['url']="http://{$domain['host']}";

        if(!$config->site['code'])
            $config->site['code']=join('.',array_slice(explode(".",$domain['host']),0,-2));
        if(!((string)$config->site['code'])) $config->site['code'] = "site";
    }

    define('DEBUG',(bool)strpos(" {$config->site['debug']}",$_SERVER['REMOTE_ADDR']));
    define('SQL_DRIVER',(string)$config->sql['driver']?$config->sql['driver']:'mysqli');
    define('SITE_CODE',strtr($config->site['code'],'.','_'));
    define('SITE_URL',(string)$config->site['url']);
    define('SITE_BASE',ucfirst(SITE_CODE));
    define('SITE_DOMAIN',$domain['host']);
    define('FLAG_DOMAIN',substr(md5(SITE_DOMAIN.SITE_CODE),0,5));
    define('FLAG_APC',FLAG_DOMAIN);
    define('FLAG_LOG',(string)$config->flags['log']);
    define('FLAG_FILE',(string)$config->flags['file'].FLAG_DOMAIN);
    define('FLAG_SESS',(string)$config->flags['sess'].FLAG_DOMAIN);
    define('FLAG_UPLOAD',(string)$config->flags['upload'].FLAG_DOMAIN);
    define('USERS_ROOT',(int)$config->users['root']);
    define('BASE_CC',(string)$config->lang['country_code']);
    define('ERROR_PAGE','/'.SITE_BASE.'/error');
    define('ERROR_404',"Location: /?".ERROR_PAGE.'//404');
    define('SESSION_NAME', crpt($_SERVER['REMOTE_ADDR'],FLAG_SESS,10));

    define('CACHE_REL', 'cache/'.FLAG_DOMAIN);
    define('CACHE_URL', SITE_URL.'/'.CACHE_REL);
    define('CACHE_PATH', PUBLIC_PATH.'/'.CACHE_REL);

    define('ROOT_PATH', paths_merge(PUBLIC_PATH,$config->site['root_path'],".."));
    define('TMP_PATH', ROOT_PATH."/tmp");
    define('LIBRARIES_PATH', paths_merge(YKS_PATH, $config->site['libraries_path'], ".."));

    return $config;
  }
}
