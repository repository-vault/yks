<box theme="&pref.dft;" caption="Liste des commandes">

<table class='table'>
<tr class='line_head'>
    <th key='order_id'>Order #</th>
    <th key='order_start'>Date</th>
    <th key='order_status'>Status</th>
    <th key='order_value'>Montant</th>
    <th key='user_id'>Client</th>
</tr>

<?php
foreach($orders_list as $order_id=>$order_infos){
    $client_user_infos = $clients_list[ $order_infos['user_id'] ];
    $order_infos['order_end'] = dsp::date($order_infos['order_end'],'<span title=\'$d/$m/$Y $H:$i:$s\'>$d/$m/$Y</span>');

echo <<<¶
<tr class='line_pair'>
        <td><a  href='/?$href_fold/Manage//$order_id'>$order_id</a></td>
        <td>{$order_infos['order_end']}</td>
        <td>&order_status.{$order_infos['order_status']};</td>
        <td>{$order_infos['order_value']}</td>
        <td>{$client_user_infos['user_name']}</td>
</tr>
¶;

}if(!$orders_list)echo "<tfail>Aucune commande</tfail>";

?>
</table>
<?=$current_liste->navigation_show()?>

<domready>
this.getElements('.line_head th').addClass('click').addEvent('click', function(){
    var data = {ks_action:'order_set',order_by:this.get('key')};
    data={url:href_fold+'/filters',data:data};
    Jsx.action(data,this);
});
</domready>
</box>