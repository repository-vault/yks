<?php

if(isset($_ENV['php_include_path']))
    set_include_path($_ENV['php_include_path']);


include "yks/class/yks/loader.php";
include "yks/class/functions.php";