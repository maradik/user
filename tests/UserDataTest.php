<?php
    require_once __DIR__."/../src/User.php";    
    
    use \Maradik\User\UserData;
    use \Maradik\User\UserRoles;    
    use \Maradik\User\UserCurrent;
    use \Maradik\User\UserRepository;
        
    
    class UserDataTest extends PHPUnit_Framework_TestCase {                                   
            
        function testInitialFields() {
            $userData = new UserData();
            $this->assertEquals(0, $userData->id);
            $this->assertEquals(0, $userData->role);
        } 
                      
        function propProvider() {
            return array(
                    array(
                        array(
                            'id' => 10,
                            'login' => 'testlogin',
                            'email' => 'test@email.ru',
                            'session' => '12345678901234567890132456789012',
                            'password' => 'somepasswd',
                            'role' => UserRoles::MODERATOR,
                            'createDate' => time(),
                            'loginDate' => time() + 1
                    )                
                )
            );
        }                      
                      
        /**
         * @dataProvider propProvider
         */                   
        function testInitialFieldsDefined(array $params) {

            $userData = new UserData(
                $params['id'],
                $params['login'],
                $params['email'],
                $params['session'],
                $params['password'],
                $params['role'],
                $params['createDate'],
                $params['loginDate']
            );                      
                                         
            foreach ($params as $key => $val) {                
                $this->assertEquals($val, $userData->$key);                                       
            }                     
        }  
        
        /**
         * @dataProvider propProvider
         */            
        function testGetSetProperties(array $params) {
           
            $userData = new UserData();
            foreach ($params as $key => $val) {
                $userData->$key = $val;   
                $this->assertEquals($val, $userData->$key);                                       
            }             
                        
        } 
    }          
   
?>