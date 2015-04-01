<?php

auth::verif("shop", "admin", 403);
auth::verif("shop", "action", true);


$vals[$href_fold]['children']["$href_fold/Products"] = array(
    'href'=>"/?$href_fold/Products",
    'title'=>'Produits',
    'target'=>'_top',
);
$vals[$href_fold]['children']["$href_fold/Meta list"]=array(
    'href'=>"/?$href_fold/Meta",
    'title'=>'Meta',
    'target'=>'_top',
);
tpls::$nav->stack($vals);


if($href == "$href_fold/") reloc("/?$href_fold/Orders");

tpls::css_add("path://skin.shop/main.css");
