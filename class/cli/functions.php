<?

function password_prompt(){
    if(stripos($_SERVER['OS'],'windows')!==false) {
        $pwObj = new Com('ScriptPW.Password');
        $password = $pwObj->getPassword();
    } else {
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
    } return $password;
}

function text_prompt($prompt=false){
    if($prompt) echo "$prompt : ";
    return trim(fread(STDIN, 1024));
}

function load_constants_ini($file) { 
  $data = parse_ini_file ($file);
  foreach($data as $key=>$value){
    if(is_numeric($value)) $value = (int)$value;
    $key =  strtoupper(strtr($key,array('.'=>'_')));
    define($key, $value);
  } 
}

