<?
/** Client side 
  <drupal auth_wsdl="http://www.example.com/?class=WebServiceAuthApi&amp;wsdl" drupal_id="drupal_profile_id">
    <roles_map>
      <role access_zone="zone:my_drupal_role1" rid="9"/>
      <role access_zone="zone:my_drupal_role2" rid="5"/>
    </roles_map>
  </drupal>
**/

class YksAuthDrupal {

  static function login($user_login, $user_pswd) {
        //config map
    $drupal_config = yks::$get->config->drupal;

    $roles_map = array();
    foreach($drupal_config->roles_map->iterate('role') as $role)
        $roles_map[$role['access_zone']] = $role['rid'];
    $ws = new  SerializedSoapClient($drupal_config['auth_wsdl']);

        //exyks login
    $user_infos = $ws->login($user_login, $user_pswd);
    if(!$user_infos)
        throw new Exception("Invalid login/pswd");

    $access = $ws->getAccesses();
    $roles  = array_values(array_intersect_key($roles_map, $access));

    $uid = $user_infos[$drupal_config['drupal_id']];

        //creation d'un drupal id & rattachement au profil existant
    if($roles && !$uid) {
        $data = array(
            'name'     => $user_infos['user_name'],
            'created'  => _NOW,
            'status'   => 1,
            'timezone' => 7200,
        ); $uid = sql::insert("users", $data, true);
        $ws->update($drupal_config['drupal_id'], $uid);
    }

    if(!$uid) 
        throw new Exception("Invalid drupal id");


    $verif_user = compact('uid');

        //update drupal's profile
    $data = array(
        'name' => $user_infos['user_name'],
        'mail' => $user_infos['user_mail'],
        'pass' => null,
    ); sql::update("users", $data, $verif_user);

        //update drupal's roles
    sql::delete("users_roles",  $verif_user);
    foreach($roles as $rid)
        sql::insert("users_roles", compact('uid', 'rid'));

    return array($uid);
  }
}