<?

  include_once "$class_path/stds/files.php";
  include_once "$class_path/exts/pclzip.php";


$query_locale_value="
	SELECT	category_item.item_key as item_key,
		COALESCE(trad.value,trad_fallback.value,trad_us.value) as value
	FROM ( 
		SELECT item_key, lang_key
		FROM `ivs_languages`,`trad_items_list`
		%s
	) as category_item
	LEFT JOIN `trad_values` as trad using(item_key, lang_key)
	LEFT JOIN `trad_values` as trad_fallback
		ON trad_fallback.lang_key='%s'
		AND trad_fallback.item_key=category_item.item_key
	LEFT JOIN `trad_values` as trad_us
		ON trad_us.lang_key='$lang_root'
		AND trad_us.item_key=category_item.item_key
	ORDER BY trad.item_key DESC, trad.lang_key
";

//	WHERE trad_us.value NOTNULL