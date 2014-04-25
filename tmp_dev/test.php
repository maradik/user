<?php
    require_once __DIR__.'/../src/UserData.php';
    require_once __DIR__.'/../vendor/autoload.php';
    
    use \Maradik\User\UserData;
    
    $userData = new UserData();
    //$userData->id = 1;
    $userData->login = "kasdasd";
    $userData->password= "adasd@#%$.3423";
    $userData->email= "email@email.ru";    
    print_r($userData->validate());
    
    var_dump($userData);
?>