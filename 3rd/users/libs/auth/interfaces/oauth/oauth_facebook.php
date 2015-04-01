<?php


class oAuth_Facebook extends oAuth {
  protected $ENDPOINT = "https://graph.facebook.com";
  protected $app_token;

  function __construct($client_id, $client_secret) {
    parent::__construct($client_id, $client_secret);
    $this->app_token = $this->token_request(array(
        'grant_type' => 'client_credentials',
    ));
  }

  function token_check($input_token){
    return $this->call("/debug_token", array(
        'input_token'  => $input_token,
        'access_token' => $this->app_token,
    ));
  }

  function token_request($data){
    $data = array_merge($data, $this->credentials);
    $token = self::rest_call("{$this->ENDPOINT}/oauth/access_token", $data);
    return $token['access_token'];
  }

  function token_extend($fb_exchange_token){    
    $data = array_merge(array(
        'grant_type'        =>  'fb_exchange_token',
    ), $this->credentials, compact('fb_exchange_token'));
    $token = self::rest_call("{$this->ENDPOINT}/oauth/access_token", $data);
    return $token['access_token'];
  }

  function call($path, $data, $method = "GET"){
    return self::json_call($this->ENDPOINT.$path, $data, $method);
  }

}

