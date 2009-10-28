<?php


class degrad {
  function __construct($file){
	$this->file=$file;
	$this->img=imagecreatefromfile($this->file);
	$this->w=imagesx($this->img)-1;
  }
  function colorat($pc){return imagecolorat($this->img, floor($pc * $this->w),0); }
  function __destruct(){ imagedestroy($this->img);  }
}

