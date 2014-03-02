<?php
    namespace Maradik\User; 
    
    /**
     * Контейнер для данных пользователя
     */
    class UserData 
    {        
        /**
         * @var int $id Идентификатор пользователя         
         */
        public $id;    
        
        /**
         * @var string $login Имя пользователя         
         */        
        public $login;

        /**
         * @var string $email Email         
         */      
        public $email;
        
        /**
         * @var string $session Идентификатор сессии         
         */              
        public $session;
        
        /**
         * @var string $password Пароль         
         */              
        public $password;
        
        /**
         * @var int $role Роль пользователя         
         */              
        public $role;
        
        /**
         * @var int $createDate Дата создания         
         */              
        public $createDate;
        
        /**
         * @var int $loginDate Дата последнего входа         
         */              
        public $loginDate;                
                        
        /**
         * @param int $id
         * @param string $login
         * @param string $email
         * @param string $session
         * @param string $password
         * @param int $role
         * @param int $createDate
         * @param int $loginDate
         */                        
        public function __construct(
            $id         = 0, 
            $login      = "", 
            $email      = "", 
            $session    = "", 
            $password   = "", 
            $role       = 0, 
            $createDate = null, 
            $loginDate  = null
        ) {
            $this->id           = $id;    
            $this->login        = $login;
            $this->email        = $email;
            $this->session      = $session;
            $this->password     = $password;
            $this->role         = $role;
            $this->createDate   = $createDate;
            $this->loginDate    = $loginDate;               
        }                                                            
    }
