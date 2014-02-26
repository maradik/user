<?php
    require_once __DIR__."/../src/User.php";        
    
    use \Antools\UserData;
    use \Antools\UserCurrent;
    use \Antools\UserRepository;
        
    class UserTest extends PHPUnit_Framework_TestCase {
        protected function someUserData() {
            $i = 10;    
            $userData = new UserData(); 
            $userData->id = $i;
            $userData->email = "some{$i}@email.com";
            $userData->login = "somelogin{$i}";
            $userData->password = "encryptPassword{$i}";
            $userData->session = "sessionid{$i}";
            $userData->createDate = time() - 60*60;
            $userData->loginDate = time() - 60*60;      
            $userData->role = 0;                                         
            return $userData;
        }
               
        function testIsRegisteredUser() { //TODO переделать тест, слишком сложен                                   
            $repository = $this->getMock('\Antools\UserRepository', array('get'), array(), '', false);
            $repository->expects($this->any())->method('get')->will($this->returnValue($this->someUserData()));                                                                  
                  
            unset($_COOKIE[UserCurrent::SESSION_COOKIE]);                                                                                           
            $user = new UserCurrent($repository);
            $user->init(false);    
            $this->assertFalse($user->isRegisteredUser());       
            
            $_COOKIE[UserCurrent::SESSION_COOKIE] = "sessionid";   
            $user->init(true);    
            $this->assertTrue($user->isRegisteredUser());                  
        }
        
        function testIsAdminTrue() { //TODO переделать тест, слишком сложен    
            $userData = $this->someUserData();       
            $userData->role = \Antools\UserData::ROLE_ADMIN;                                      
            $repository = $this->getMock('\Antools\UserRepository', array('get'), array(), '', false);
            $repository->expects($this->any())->method('get')->will($this->returnValue($userData));                                                                                     
            
            $_COOKIE[UserCurrent::SESSION_COOKIE] = "sessionid";
            $user = new UserCurrent($repository);   
            $user->init(true);    
            $this->assertTrue($user->isAdmin());                           
        }                
        
        function testIsAdminFalse() { //TODO переделать тест, слишком сложен    
            $userData = $this->someUserData();     
            $userData->role = \Antools\UserData::ROLE_USER;                                      
            $repository = $this->getMock('\Antools\UserRepository', array('get'), array(), '', false);
            $repository->expects($this->any())->method('get')->will($this->returnValue($userData));                                                                  
                  
            unset($_COOKIE[UserCurrent::SESSION_COOKIE]);                                                                                           
            $user = new UserCurrent($repository);
            $user->init(false);    
            $this->assertFalse($user->isAdmin());       
            
            $_COOKIE[UserCurrent::SESSION_COOKIE] = "sessionid";   
            $user->init(true);    
            $this->assertFalse($user->isAdmin());                          
        }        
               
        function testInitWithRestoreSession() {
            $ssid = 10;
            $userData = $this->someUserData();
            $repository = $this->getMock('\Antools\UserRepository', array('get'), array(), '', false);
            $repository->expects($this->once())
                ->method('get')
                ->will($this->returnValue($this->someUserData()));
                                             
            $_COOKIE[UserCurrent::SESSION_COOKIE] = $this->someUserData()->session;                                                                                                
            $user = new UserCurrent($repository);
            $this->assertFalse($user->isRegisteredUser());  
            
            $user->init(true);                                        
            
            $this->assertEquals($userData, $user->data());         
            $this->assertTrue($user->isRegisteredUser());              
        } 
        
        function testInitWithoutRestoreSession() {
            $repository = $this->getMock('\Antools\UserRepository', array('get'), array(), '', false);
            $repository->expects($this->never())
                ->method('get');
                                             
            $_COOKIE[UserCurrent::SESSION_COOKIE] = "sessonid9";                                                                                                
            $user = new UserCurrent($repository);
            $user->init(false);     
            $userData = $user->data();
            $this->assertEquals(0, $userData->id);              
            $this->assertFalse($user->isRegisteredUser());  
            
            $user->init(false);     
            $userData2 = $user->data();            
            
            $this->assertNotEquals($userData2->session, $userData->session);                
            $this->assertEquals(UserCurrent::SESSID_LENGTH, strlen($userData->session));
            $this->assertEquals(UserCurrent::SESSID_LENGTH, strlen($userData2->session));                      
            $userData->session = null;
            $this->assertEquals(new UserData(), $userData);    
        }         

        /**
         * @depends testInitWithoutRestoreSession
         */
        function testLoginSuccess() {
            $encSalt = "123456";
            $chLogin = $this->someUserData()->login;
            $chPassword = $this->someUserData()->password;
            $userData = $this->someUserData();
            $userData->password = md5(md5(trim($encSalt.$userData->password)));
            
            $repository = $this->getMock('\Antools\UserRepository', array('get', 'update'), array(), '', false);
            $repository->expects($this->once())
                ->method('get')
                ->will($this->returnValue($userData));    
            $repository->expects($this->once())
                ->method('update')
                ->will($this->returnValue(true)); 
            
            $user = new UserCurrent($repository, $encSalt);
            $user->init(false);
            $this->assertTrue($user->login($chLogin, $chPassword));
            $this->assertTrue($user->isRegisteredUser());                                                                 
        }
        
        /**
         * @depends testInitWithoutRestoreSession
         */
        function testLoginFailed() {
            $chLogin = $this->someUserData()->login;
            $chPassword = "123456789";
            $userData = $this->someUserData();
            $userData->password = md5(md5(trim($userData->password)));
            
            $repository = $this->getMock('\Antools\UserRepository', array('get', 'update'), array(), '', false);
            $repository->expects($this->once())
                ->method('get')
                ->will($this->returnValue($userData));    
            $repository->expects($this->never())
                ->method('update')
                ->will($this->returnValue(true)); 
            
            $user = new UserCurrent($repository);
            $user->init(false);
            $this->assertFalse($user->login($chLogin, $chPassword));        
            $this->assertFalse($user->isRegisteredUser());                      
        }
        
        /**
         * @depends testInitWithoutRestoreSession
         */
        function testLogoutAnonymous() {                        
            $repository = $this->getMock('\Antools\UserRepository', array('update'), array(), '', false); 
            $repository->expects($this->never())
                ->method('update')
                ->will($this->returnValue(true));             
            $user = new UserCurrent($repository);
            $user->init(false);
            $userData = $user->data();
            
            $user->logout();
            $this->assertNotEquals($userData->session, $user->data()->session);
            $this->assertFalse($user->isRegisteredUser());           
        } 
        
        /**
         * @depends testInitWithRestoreSession
         */
        function testLogoutRegistered() {
            $_COOKIE[UserCurrent::SESSION_COOKIE] = $this->someUserData()->session;
                                    
            $repository = $this->getMock('\Antools\UserRepository', array('get', 'update'), array(), '', false); 
            $repository->expects($this->atLeastOnce())
                ->method('get')
                ->will($this->returnValue($this->someUserData()));    
            $repository->expects($this->once())
                ->method('update')
                ->will($this->returnValue(true));   
            $user = new UserCurrent($repository);
            $user->init(true); 
            $userData = $user->data();           
            $this->assertNotEquals(0, $user->data()->id);            
            
            $user->logout();
            $this->assertEquals(0, $user->data()->id);
            $this->assertNotEquals($userData->session, $user->data()->session);
            $this->assertFalse($user->isRegisteredUser());            
        }       
        
        /**
         * @depends testInitWithoutRestoreSession
         */        
        function testRegisterSuccess() {
            $userDataInitial = $this->someUserData();
            $userDataRegExpected = clone $userDataInitial; 
            $userDataRegExpected->password = md5(md5(trim($userDataInitial->password)));
            $userDataRegExpected->createDate = time();
            $userDataRegExpected->loginDate = time();  
            $userDataRegActual = null;  
            $repGetCounter = 0;      
                        
            $repository = $this->getMock('\Antools\UserRepository', array('get', 'insert'), array(), '', false); 
            $repository->expects($this->once())
                ->method('insert')
                ->will($this->returnCallback(function($ud) use (&$userDataRegActual) { $userDataRegActual = $ud; return true; }));   
            $repository->expects($this->exactly(3))
                ->method('get')
                ->will($this->returnValue($repGetCounter++ < 2 ? new UserData() : $userDataRegActual));                                    
            $user = new UserCurrent($repository);    
            $user->init(false);     
            $userDataRegExpected->session = $user->data()->session;
            
            $this->assertTrue($user->register($userDataInitial));                         
                        
            $this->assertEquals($userDataRegExpected->login, $userDataRegActual->login);
            $this->assertEquals($userDataRegExpected->password, $userDataRegActual->password);
            $this->assertEquals($userDataRegExpected->email, $userDataRegActual->email);
            $this->assertEquals($userDataRegExpected->session, $userDataRegActual->session);
            $this->assertEquals($userDataRegExpected->role, $userDataRegActual->role);
            $this->assertGreaterThanOrEqual($userDataRegExpected->createDate, $userDataRegActual->createDate);
            $this->assertGreaterThanOrEqual($userDataRegExpected->loginDate, $userDataRegActual->loginDate);
            
            $this->assertEquals(\Antools\UserCurrent::ERROR_NONE, $user->errorCode());                                         
        }
        
        function testRegisterFailed_UserAlreadyExist() {
            $userDataInitial = $this->someUserData();
                       
            $repository = $this->getMock('\Antools\UserRepository', array('get', 'insert'), array(), '', false); 
            $repository->expects($this->never())
                ->method('insert');                   
            $repository->expects($this->once())
                ->method('get')
                ->will($this->returnValue($userDataInitial));                                    
            $user = new UserCurrent($repository);    
            $user->init(false);                                    
                        
            $this->assertFalse($user->register($userDataInitial));
            $this->assertFalse($user->isRegisteredUser());            
            $this->assertEquals(\Antools\UserCurrent::ERROR_USER_ALREADY_EXIST, $user->errorCode());                                                     
        }      
        
        function testRegisterFailed_ErrorDB() {
            $userDataInitial = $this->someUserData();
                       
            $repository = $this->getMock('\Antools\UserRepository', array('get', 'insert'), array(), '', false); 
            $repository->expects($this->once())
                ->method('insert')
                ->will($this->returnValue(false));                   
            $repository->expects($this->any())
                ->method('get')
                ->will($this->returnValue(new UserData()));                                    
            $user = new UserCurrent($repository);    
            $user->init(false);                             
                               
            $this->assertFalse($user->register($userDataInitial));
            $this->assertFalse($user->isRegisteredUser());
            $this->assertEquals(\Antools\UserCurrent::ERROR_DB, $user->errorCode());                                                     
        }               
        
        //TODO выделить в отдельные тесты проверку количества вызовов update, insert, get
        //TODO убедиться в установленных куках
    }      
        
?>