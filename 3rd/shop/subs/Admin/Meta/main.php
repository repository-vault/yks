<?php

$meta_products_list = products_meta::from_where(sql::true);
ksort($meta_products_list);
