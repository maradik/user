<?php
    require_once __DIR__.'/../src/User.php';
    
    use \Antools\UserRepository as UserRepository;
    
    $ur = new UserRepository(new PDO("mysql:host=localhost;dbname=voprosnik;", "root", ""), "usertable2", "test2");
        
?>