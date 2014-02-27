<?php
    require_once __DIR__.'/../src/User.php';
    
    use \Antools\UserRepository;
    
    $ur = new UserRepository(new PDO("mysql:host=localhost;dbname=voprosnik;", "root", ""), "usertable2", "test2");
    $ud = $ur->get(1, UserRepository::GET_BY_ID);   
?>