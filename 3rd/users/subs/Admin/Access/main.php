<?php

sql::select("ks_access_zones",true,"*","ORDER BY access_zone_parent, access_zone");
$access_zones = sql::brute_fetch("access_zone");
