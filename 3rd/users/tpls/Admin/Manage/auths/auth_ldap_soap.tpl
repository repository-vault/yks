<box>
<field title="Endpoint Configuration">
<select name="auth_ldap_soap_endpoint_name">
  &select.choose;
  <?=dsp::dd($endpoints_list, $user_infos['auth_ldap_soap_endpoint_name'])?>
</select>
</field>

</box>