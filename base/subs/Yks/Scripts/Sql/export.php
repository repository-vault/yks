<?

$table_name = "ks_shop_products_list";


$table_sync = new table_sync($table_name);
    $table_sync->fill();

$queries  = $table_sync->asSQL();
print_r($queries);die;

