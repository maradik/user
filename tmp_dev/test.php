<?php
    namespace Maradik\User;
    
    require_once '../vendor/autoload.php';
    
    $userData = new UserData();
    //$userData->id = 1;
    //$userData->login = "kasdasd";
    $userData->password= "adasd@#%$.3423";
    $userData->email= "email@email.ru";    
    print_r($userData->validate('login'));
    
    var_dump($userData);
?>