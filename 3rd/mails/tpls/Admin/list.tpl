<box id="mails_list" theme="&pref.dft;" caption="Liste des mails">

<table class="table" style="width:100%">
<tr class='line_head'>
    <th>#</th>
    <th>Nom</th>
    <th>Titre</th>
    <th>Type</th>
    <th style="width:60px"> </th>
</tr>

<?

if($mails_list) foreach($mails_list as $mail_id=>$mail_infos){
  $actions ="";
  $actions .= "<a ext='/?$href_fold/Manage//$mail_id' class='std'>
        <img src='&COMMONS_URL;/css/Yks/icons/edit_24'/>
      </a>";
  $actions .= "<img onclick=\"Jsx.action({ks_action:'mail_delete',mail_id:$mail_id},this,this.title)\" title='Supprimer' src='&COMMONS_URL;/css/Yks/icons/trash_24'/>";


  echo "<tr class='line_pair'>
    <td>$mail_id</td>
    <td>{$mail_infos['mail_name']}</td>
    <td>".truncate($mail_infos['mail_title'],20)."</td>
    <td>{$mail_infos['content-type']}</td>
    <td>$actions</td>
  </tr>";


}else echo "<tfail>Aucun mail n'a été configuré</tfail>";

?>
</table>

<a class='ext' href="<?="/?$href_fold/Manage"?>">Ajouter un mail</a>

</box>