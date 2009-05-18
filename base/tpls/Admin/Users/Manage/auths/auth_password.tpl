<box>
<field title="&user_profile.user_login;" type="user_login" value="<?=$user_infos['user_login']?>"/>
<field title="&user_profile.user_pswd;" type="<?=FLAG_LOG?"user_pswd":"user_login"?>" name="user_pswd" value="<?=$user_infos['user_pswd']?>"/>
</box>