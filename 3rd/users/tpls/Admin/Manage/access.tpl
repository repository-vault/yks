<box theme="&pref.dft;" style="width:400px" options="modal,fly,close,reload" id="user_infos" caption="Gestion des droits">

Vous êtes ici : <?=$parent_path?><br/>

<label for="show_defined">Show all defined</label> <input type="checkbox" id="show_defined" name="defined" />

<ks_form ks_action="access_save" submit="Enregistrer">

<table class='table' style='width:750px' id='access_zones_list'>
    <thead>
  <tr class="line_head">
  <th style='width:200px'>Zone</th>
    <?php
    foreach(vals(yks::$get->types_xml->access_lvl) as $access_lvl)
        echo "<th>&access_lvl.$access_lvl;</th>";
    ?>
  <th>Notes</th>
  </tr>
    </thead>
<?php
$root_zone = false;

foreach($access_zones as $access_zone=>$zone_infos){

    //root zones are their own parent
    $current_root = $zone_infos['access_zone_parent'] == $access_zone
        ?'root':$zone_infos['access_zone_parent'];

    if($root_zone!=$current_root){
        if($root_zone) echo "<tr><td colspan='4'>---</td></tr></tbody>";
        echo "
            <thead root='$current_root'><tfail>$current_root</tfail></thead>
            <tbody id='access_areas_{$current_root}_contents'>";
        $root_zone = $current_root;
    }

    $tmp="<tr class='line_pair'>
        <td class='center'>$access_zone</td>";
    foreach(vals(yks::$get->types_xml->access_lvl) as $access_lvl){
        $parent_value=isset($access[ $zone_infos['access_zone_path'] ][$access_lvl]);
        $readonly=$parent_value?" disabled='disabled'":'';
        $checked=$parent_value || isset($user_access[ $zone_infos['access_zone_path'] ][$access_lvl]);
        $checked=$checked?" checked='checked'":'';
        $tmp.="<td class='align_center'><input type='checkbox' name='access[{$access_zone}][{$access_lvl}]' $checked $readonly/></td>";
    }
    $tmp.="<td class='center'>{$zone_infos['zone_descr']}</td>
    </tr>";
    echo $tmp;
}

if($root_zone) echo "</tbody>";
?>

</table>
</ks_form>
<domready>
    var css_class = 'on'+(Browser.Engine.trident?"_ie":'');
    $$('#access_zones_list thead').addEvent('click',function(){
        Element.activate(this.getNext(), css_class);
    });
    Element.activate($('access_areas_root_contents'), css_class );

    $('show_defined').addEvent('change', function(){
      if(this.checked){
        $$('#access_zones_list tbody').each(function(group){
          group.addClass(css_class);
          group.getChildren("tr").each(function(tr){
            if(tr.querySelectorAll('input[type=checkbox]:checked').length == 0){
              tr.addClass('hidden');
            }
          });
        });
      }
      else {
        $$('#access_zones_list tbody tr').removeClass('hidden');
        Element.activate($('access_areas_root_contents'), css_class);
      }
    });
</domready>
<hr/>
<ks_form ks_action="access_duplicate" submit="Dupliquer">
  <field title="Duplicate to user">
      <input type="text" id="duplicate_user" name="duplicate_user"/>
    </field>
</ks_form>
<domready src="/?/Yks/Scripts/Js|path://3rd/usage/TextboxList.js">
    <![CDATA[
    var domainLst = new WTextboxList('duplicate_user', {
      max: 25,
      unique:true,
      plugins: {
        autocomplete: {
          minLength: 2,
          queryRemote: true,
          useCache: false,
          remote: {
            url: '?<?=$href_fold?>/autocomplete//duplicate'
          }
        }
      }
    });
   ]]>
  </domready>
</box>

