<?php
include 'yks/cli.php';

class msg {
  function say($msg){
    echo "Say : $msg\r\n";
 }
} $msg = new msg();

$this_contents = file_get_contents("this.txt");
$that_contents = file_get_contents("that.txt");


$func1  = create_function('$that', $that_contents);
$func3  = create_closure('$that', $that_contents);
$func2  = create_closure('', $this_contents);

call_user_func_array($func1, array($msg));
call_user_func_array($func3, array($msg));
call_user_func($func2->bindTo($msg));

