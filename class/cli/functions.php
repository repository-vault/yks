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
