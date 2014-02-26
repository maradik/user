<?php
namespace Antools {
    
    /**
     * Контейнер для данных пользователя
     */
    class UserData {
        const ROLE_USER = 0;
        const ROLE_MODERATOR = 1;
        const ROLE_ADMIN = 2;
        
        public $id;    
        public $login;
        public $email;
        public $session;
        public $password;
        public $role;
        public $createDate;
        public $loginDate;      
        
        function __construct() {
            $this->id = 0;
            $this->role = 0;
        }            
        
        static function make($id, $login, $email, $session, $password, $role, $createDate, $loginDate) {
            $userData = new UserData();
            $userData->id           = $id;    
            $userData->login        = $login;
            $userData->email        = $email;
            $userData->session      = $session;
            $userData->password     = $password;
            $userData->role         = $role;
            $userData->createDate   = $createDate;
            $userData->loginDate    = $loginDate;       
            
            return $userData;        
        }                              
    }

}
?>