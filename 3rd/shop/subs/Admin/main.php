<?

auth::verif("shop", "admin", 403);
auth::verif("shop", "action", true);


tpls::$nav[$href_fold]['children']["$href_fold/Products"]=array(
    'href'=>"/?$href_fold/Products",
    'title'=>'Produits',
    'target'=>'_top',
);


if($href == "$href_fold/") reloc("/?$href_fold/Orders");

tpls::css_add("path://skin.shop/main.css");
