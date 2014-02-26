<?php
    require_once "../src/User.php";    
    
    use \Antools\UserData;
    use \Antools\UserCurrent;
    use \Antools\UserRepository;
    
    class UserDataTest extends PHPUnit_Framework_TestCase {
        function testInitialFields() {
            $userData = new UserData();
            $this->assertEquals(0, $userData->id);
        } 
        
        function testMake() {
            $userData = new UserData();
            $userData->id           = 10;    
            $userData->login        = "user";
            $userData->email        = "mail@mail.ru";
            $userData->session      = "1233568909";
            $userData->password     = "password";
            $userData->role         = UserData::ROLE_USER;
            $userData->loginDate    = time();             
            $userData->createDate   = $userData->loginDate - 1;                        
                        
            $userData2 = UserData::make(
                $userData->id,
                $userData->login,
                $userData->email,
                $userData->session,
                $userData->password,
                $userData->role,               
                $userData->createDate,
                $userData->loginDate
            );
            
            $this->assertEquals($userData, $userData2);
        }         
    }          
   
?>