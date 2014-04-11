<box>
  <style>
    .map_title {
      font-size: 14px;
      font-weight: bold;
      margin: 0 0 6px 5px;
    }
  </style>
  <div class='map_title'><?="{$geomap->map_name} (#{$geomap->map_id})"?></div>
  <div><a href='?&href_fold;/list'>Back to map list</a></div>
  <form method="post" action="/?&href;" style="width:100%">
    <input type="hidden" name="ks_action" value="area_add"/>

    <div class="float_left">
      <input type="image" src="/?&href_base;/map"/>
    </div>
    <div class="float_right" style="width:200px">
      <?
        foreach($geomap->users_list as $user){
          //$color    = user_geomaps::user_color($user, true);
          $color    = $geomap->get_user_color($user);
          $color    = colorstool::rgb_to_html($color['r'], $color['g'], $color['b']);
          $selected = $user_id == $user['user_id'] ? "checked='checked'":"";
          echo "<p style='background-color:#$color'><label><input type='radio' $selected name='user_id' value='{$user['user_id']}'/> {$user['user_name']} </label></p>";
        }
      ?>
    </div>
    <clear/>
  </form>
</box>