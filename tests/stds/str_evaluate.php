<?php

$Y = 2012;
$m = 06;
$a = new stdClassSerializable("error");
$a->b = 43;
$a->bb = 43;
$a->c = $a;

$str = preg_replace(VAR_MASK, VAR_REPL, '$Y-$m $a->b $a->c->b $a-->n $a->->n $a->c->bb');