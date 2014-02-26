<?php
    require_once '../usermodule.php';
    
    use \Antools\UserRepository as UserRepository;
    
    $ur = new UserRepository(new PDO("mysql:host=localhost;dbname=voprosnik;", "root", ""), "usertable", "test");
        
?>