<box id="user_tanslation_language">
  <table id="results_list" class="table" width="100%">
  <tr class="line_head"><?=$headers?></tr>
  <?php
    foreach($lang_infos as $lang){
      $do = json_encode(array('ks_action'=>'delete', 'id'=>$lang['lang_key']));

      $cols = array();
      $cols["lang_key"] = "<td>".$lang['lang_key']."</td>";
      $cols["lang"]     = "<td>".$lang['country_name']." - ".strtoupper($lang['lang_code'])."</td>";
      $cols["domain"]   = "<td>".$locale_domains_list[$lang['locale_domain_id']]['locale_domain_name']."</td>";
      $cols["action"]   = "<td><span onclick='Jsx.action(\$H($do), this, \"Supprimer\")'>[Delete]</span></td>";
      echo $headers->feed_tr($cols);
    }
  ?>
  </table>
</box>