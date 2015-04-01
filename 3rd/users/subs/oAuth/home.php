<?php

$fb_config = yks::$get->config->users->oauth_facebook;
$challenge_mode   = isset($_GET['challenge']);
$continue         = $_GET['continue'];

$challenge_url    = SITE_URL."/oauth?challenge";
if($continue) $challenge_url .= "&continue=$continue";


$endpoint = auth_oauth::$endpoints_list['facebook'];


if(!$challenge_mode) {  
    $data = array(
        'display'      => 'popup',
        'client_id'    => $endpoint->credentials['client_id'],
        'redirect_uri' => $challenge_url,
        //'scope'        => "email,publish_actions",
    ); $redirect = $endpoint::forge_url("https://www.facebook.com/dialog/oauth", $data);

    header("Location:$redirect");
    die;
} else {
    $code = $_GET['code'];

    $access_token = $endpoint->token_request(array(
        'redirect_uri'  => $challenge_url,
        'code'          => $code,
    ));

    if(!$access_token)die("Invalid token");

    $check = $endpoint->token_check($access_token);


    $me = $endpoint->call("/me", array(
      'access_token' => $access_token,
      'fields'       => 'id,name,picture,email,first_name,last_name', //,permissions,friends
    ));

    $success = $me['id'];
    if(!$success)
        abort(403);

    $verif_user = array('auth_oauth_user_id' => $me['id'], 'auth_oauth_endpoint_name' => 'facebook');
    $user_id = sql::value("ks_auth_oauth", $verif_user, "user_id");
    if(!$user_id) {
        //create user
        $user_id = user_gest::create(array(
            'parent_id' => $fb_config['incoming'],
            'user_name' => $me['name'],
            'auth_type' => 'auth_oauth',
        ));
    }

        //extend access token expiracy (not necessary ?)
    $access_token = $endpoint->token_extend($access_token);

    sql::replace("ks_auth_oauth", array( 'oauth_token' => $access_token), $verif_user);

    sess::connect();
    auth_oauth::reload($user_id, $endpoint->sign($me['id']),  $continue);
}

die;