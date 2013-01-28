<?

$zks_deletion_id = (int) $sub0;
tpls::export(compact('zks_deletion_id'));

$deletion = sql::row("zks_deletion_history", compact("zks_deletion_id"));

