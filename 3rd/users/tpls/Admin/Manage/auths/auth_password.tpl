<box>
<field title="&user_profile.user_login;" type="user_login" value="<?=$user_infos['user_login']?>"/>
<field title="&user_profile.user_pswd;" type="<?=FLAG_LOG?"user_pswd":"user_login"?>" name="user_pswd" value="<?=$user_infos['user_pswd']?>"/>

<field title="Random login"><div class='user_icon icon_user_reload float_right' onclick="$N('user_pswd').value = ($N('user_mail').value.split('@')[1]+'123456789').replace(/\./g, '').split('').shuffle().slice(0,8).join('');   $N('user_login').value=$N('user_mail').value.split('@')[0]; return false;">&#160;</div>
</field>

</box>