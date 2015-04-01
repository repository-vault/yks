<?php

class vector3d {
  public $X, $Y, $Z;
  
  public function __construct($x, $y, $z) {
    $this->X = $x;
    $this->Y = $y;
    $this->Z = $z;
  }
  
  public function dot($vec) {
    return ($this->X*$vec->X) + ($this->Y*$vec->Y) + ($this->Z*$vec->Z);
  }
  
  public function add($vec) {
    return new vector3d($this->X + $vec->X, $this->Y + $vec->Y, $this->Z + $vec->Z);
  }
  
  public function minus($vec) {
    return new vector3d($this->X - $vec->X, $this->Y - $vec->Y, $this->Z - $vec->Z);
  }
  
  public function times($multiplier) {
    return new vector3d($this->X * $multiplier, $this->Y * $multiplier, $this->Z * $multiplier);
  }
  
  public function normalize($to = 1) {
    $invLength = $to / $this->length();
    $this->X *= $invLength;
    $this->Y *= $invLength;
    $this->Z *= $invLength;
  }
  
  public function lengthSquared() {
    return pow($this->X, 2) + pow($this->Y, 2) + pow($this->Z, 2);
  }
  
  public function length() {
    return sqrt($this->lengthSquared());
  }
  
  public function copy() {
    return new vector3d($this->X, $this->Y, $this->Z);
  }
}