<box>
<field title="Endpoint Configuration">
<select name="auth_oauth_endpoint_name">
  &select.choose;
  <?=dsp::dd($endpoints_list, $user_infos['auth_oauth_endpoint_name'])?>
</select>
</field>

<field title="oAuth user #" type="auth_oauth_user_id" value="<?=$user_infos['auth_oauth_user_id']?>"/>
<field title="oAuth access token" type="oauth_token" value="<?=$user_infos['oauth_token']?>"/>


</box>