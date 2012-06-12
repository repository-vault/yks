<?
  $all_actions = array();
  $all_actions[] = "<button confirm='this.alt' name='ks_action[fails_delete]'>Delete</button>";
?>

<box theme="&pref.dft;" caption="Liste des auths failed" style="width:100%">
  <ks_form id="files_manage">
    <?=$pages;?>
    <span style="float: right"> Pour la sélection : <?=join(" - ",$all_actions);?></span><br />
    <table style="width:100%" id="results_list" class="table">
      <tr class="line_head"><?=$headers?></tr>
      <?
      foreach($list_failed_auth as $failed) {    
        $actions = array();
        $cols    = array();
        
        $actions[] = "<a onclick=\"Jsx.action({ks_action:'trash', failed_ip:'{$failed['failed_ip']}', failed_login:'{$failed['failed_login']}'},this, 'Supprimer')\">Supprimer</a>";
        
        $cols = array();
            
        $cols["count"] = "<td>".$failed['count']."</td>";
        $cols["failed_ip"] = "<td>".$failed['failed_ip']."</td>";
        $cols["failed_login"] = "<td>".$failed['failed_login']."</td>";
        $cols["checkbox"] = "<td><input type='checkbox' name='failed_list[{$failed['failed_ip']},{$failed['failed_login']}]'/></td>";
        
        $cols["actions"]  = "<td>".join(" - ",$actions)."</td>";
        echo $headers->feed_tr($cols);
      }
      ?>
    </table>
    <?=$pages;?>
  </ks_form>
  
  <domready>
    $('failed_list').addEvent('click',function(){
      this.getParent('table').getElements('input[name^=failed_list]').set("checked",this.checked)
    });
  </domready>
</box>