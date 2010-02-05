<box>
<input type='hidden' name='&field_name;' id='&field_name;' value="&user_id;"/>
<input style="width:100%" type="text" id="&field_name;_tmp" value="&user_path;"/>
<domready>

$('&field_name;_tmp').addEvent('keypress',function(event){
  if(event.code==9){ event.stop();
    Jsx.action({'ks_action':'check_names','users_txt':this.value},this);
  }
});
</domready>

</box>