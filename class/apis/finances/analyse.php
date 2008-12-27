<?

class finances {
  static function analyse(){
		//Analyse des données adsense

	sql::query("SELECT * FROM adsense_income");
	$liste=sql::brute_fetch("ad_date");
	foreach($liste as $ad){
	  $data=array(
		'trans_type'=>'adsense',
		'trans_val'=>$ad['ad_money']/100,
		'trans_date'=>$ad['ad_date']
	  ); sql::query("REPLACE INTO transactions_list SET ".sql::format($data));
	}

		//Analyse des factures dedibox et freebox

	sql::query("SELECT * FROM transactions_bills");
	$liste=sql::brute_fetch("bill_id");
	foreach($liste as $bill){
	  $data=array(
		'trans_type'=>$bill['bill_type'],
		'trans_val'=>$bill['bill_val'],
		'trans_date'=>$bill['bill_date']
	  ); sql::query("REPLACE INTO transactions_list SET ".sql::format($data));
	}

  }

}
