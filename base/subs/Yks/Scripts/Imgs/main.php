<?php

include "$class_path/dom/dom.php";
include "$class_path/imgs/functions.php";
include "$class_path/imgs/imgs.php";

$themes_config = config::retrieve("themes");
if(!$themes_config) die("Unable to load theme config");
