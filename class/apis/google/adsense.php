<?

class adsense {
  static function sql_report(){
	$list=glob("/home/tmp/adsense/*");
	foreach($list as $file){
		$str=file_get_contents($file);
		$lines=explode("\n",$str);
		foreach($lines as $line){
			$line=explode("\t",$line); $date=explode('-',$line[0]);
			if(!is_numeric($date[0]))continue;
			$data=array(
				'ad_date'=>mktime(0,0,0,$date[1],$date[2],$date[0]),
				'ad_prints'=>$line[1],
				'ad_clicks'=>$line[2],
				'ad_money'=>preg_replace('#[^0-9]#','',$line[5])
			);
			sql::query("REPLACE INTO adsense_income SET ".sql::format($data));
		}
	}
  }

  static function get_report($host,$adsense_auth,$date_from,$date_to){

	$adsense=new sock_lnk($host);
	rbx::ok("Opening $host");
	if(!$adsense) throw rbx::error("Unable to reach $host");

	$adsense->request("/adsense/loginfooter?hl=fr",array('method'=>'HEAD')); //cookie
        

	$url_form="/accounts/ServiceLoginBox?service=adsense&hl=fr&ltmpl=login&ifr=true";
	$url_form.="&continue=https%3A%2F%2Fwww.google.com%2Fadsense%2Fgaiaauth";

	$adsense->request($url_form);	//champs formulaire
	$adsense->receive("#form action=\"(.*?)\"(.*?)</form>#is",$tmp);
	$action=$tmp[1];$post=array();
	preg_match_all("#<input.*?name=\"(.*?)\".*?>#is",$tmp[2],$inputs);
	foreach($inputs[0] as $k=>$v){
		preg_match("#value=\"(.*?)\"#i",$v,$tmp);
		$posts[$inputs[1][$k]]=$tmp[1];
	}if(!$posts)throw rbx::error("Unable to retrieve login form");
	$posts['Email']=(string)$adsense_auth['login'];
	$posts['Passwd']=(string)$adsense_auth['pswd'];

	$url_auth="/accounts/$action";
	$headers=array('method'=>"POST",'data'=>$posts,'Referer'=>urldecode($host.$url_form));
	$adsense->request($url_auth,$headers);	//authentification - POST
	$adsense->receive("#\"https://www.google.com/accounts/CheckCookie.*?\"#",$tmp);

	$url_valid=explode_url(trim($tmp[0], '"'));
	$headers=array('Referer'=>urldecode($host.$url_auth));
	$get="{$url_valid['path']}?{$url_valid['query']}";

	$adsense->request($get, $headers);	//cookie
        $adsense->receive("-(http://www.google.fr/accounts/SetSID.*?)&#39;-", $tmp);
        $last_redirect = htmlspecialchars_decode($tmp[1]);
        $last_cookies = $adsense->cookies;

        $url = parse_url($last_redirect);
        $sock = new sock_lnk($last_redirect);
        $options = array(
            'options'=>array(
                'follow_to' => "#google#",
                'jar' => array('www.google.com' => $last_cookies)
            )
        );

        $sock->request("{$url['path']}?{$url['query']}", $options );
        $sock->receive();

	$url_csv="/adsense/report/aggregate";
	list($f_d,$f_m,$f_y)=explode('/',$date_from);
	list($t_d,$t_m,$t_y)=explode('/',$date_to);
	$file="$f_d-$f_m-$f_y.csv";
	$data=array(
		"sortColumn"=>'0',
		"reverseSort"=>'false',
		"outputFormat"=>'TSV_EXCEL',
		"storedReportId"=>-1,
		"isOldReport"=>'false',
		"product"=>'afc',
		"dateRange.simpleDate"=>'thismonth',
		"dateRange.dateRangeType"=>'custom',
		"dateRange.customDate.start.day"=>$f_d,
		"dateRange.customDate.start.month"=>$f_m,
		"dateRange.customDate.start.year"=>$f_y,
		"dateRange.customDate.end.day"=>$t_d,
		"dateRange.customDate.end.month"=>$t_m,
		"dateRange.customDate.end.year"=>$t_y,
		"unitPref"=>'page',
		"reportType"=>'property',
		"groupByPref"=>'date',
	);$headers=array("method"=>"POST",'data'=>$data,'Referer'=>"$host$url_csv");

	$sock->request($url_csv,$headers);		//récupération
        $contents = $sock->receive();
        die($contents);

	return $out_file;
 }
}

