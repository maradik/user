<?php
    
    namespace Maradik\User;
     
    /**
     * Управление текущим пользователем
     */
    class UserCurrent 
    {
        const SESSION_COOKIE = "ATUID";
        const SESSID_LENGTH = 32;              
        const SESSION_LIVETIME = 2592000; // 2592000сек = месяц          
                        
        const ERROR_NONE = 0;                        
        const ERROR_GENERAL = 1;
        const ERROR_USER_ALREADY_EXIST = 2;
        const ERROR_DB = 3;      
        const ERROR_VALIDATE_FIELD = 4;    
                        
        /**
         * @var UserData $userData
         */
        protected $userData;
        
        /**
         * @var UserRepository $db
         */
        protected $db;
        
        /**
         * @var string $encryptSalt
         */        
        protected $encryptSalt;
        
        /**
         * @var int $livetime Время жизни сессии в секундах
         */          
        protected $livetime;   
        
        /**
         * @var int $errorCode Код ошибки
         */    
        protected $errorCode;            
        
        /**
         * @var string $errorInfo Текст ошибки
         */
        protected $errorInfo;   
        
        /**
         * @param UserRepository $repository Репозиторий БД
         * @param string $encryptSalt Соль для шифрования
         * @param int $livetime время жизни сессии в секундах
         */
        public function __construct(
            UserRepository $repository, 
            $encryptSalt    = "", 
            $livetime       = UserCurrent::SESSION_LIVETIME
        ) {                            
            $this->db = $repository;
            $this->encryptSalt = $encryptSalt;
            $this->livetime = $livetime;      
            $this->setError(UserCurrent::ERROR_NONE);                      
        }
                       
        /**
         * @param boolean $_resetSession Начать новую сессию
         */                
        public function init($_resetSession = false) 
        {
            $sessionId = !empty($_COOKIE[UserCurrent::SESSION_COOKIE]) ? $_COOKIE[UserCurrent::SESSION_COOKIE] : '';
            $this->setError(UserCurrent::ERROR_NONE);
            $this->userData = null;                  
                                         
            if (!$_resetSession && !empty($sessionId)) {            
                $userData = $this->db->getBySession($sessionId);           
                if (!empty($userData->id) &&  $userData->loginDate >= time() - UserCurrent::SESSION_LIVETIME) {
                    $this->userData = $userData;                                        
                }                    
            }    
            
            if (empty($this->userData)) {                
                $this->userData = new UserData();                            
                $this->userData->session = !$_resetSession && $sessionId ? $sessionId : $this->generateSessionId();                                     
            }      
            $this->setSessionCookie();                                                                                                                                           
        }
                
        /**
         * Аутентификация (Вход пользователя под именем)        
         * 
         * @param string $login Имя пользователя
         * @param string $password Пароль в открытом виде
         * @return boolean Если вход успешно прошел - возвращает true.
         */                
        public function login($login, $password) 
        {
            $this->setError(UserCurrent::ERROR_NONE);
            $userData = $this->db->getByLogin($login);
            
            if (!empty($userData->id) && $userData->password == $this->encryptPassword($password)) {
                $this->userData = $userData;      
                if (empty($this->userData->session)) {
                    $this->userData->session = $this->generateSessionId();
                }
                $this->userData->loginDate = time();      
                $this->db->update($this->userData);     
                $this->setSessionCookie();                           
                return true;
            }
            
            return false;
        }
        
        /**
         * Завершение сессии
         */
        public function logout() 
        {
            $this->setError(UserCurrent::ERROR_NONE);
            
            if ($this->isRegisteredUser()) {
                $this->userData->session = "";
                $this->db->update($this->userData); 
            }           
            
            $this->init(true);            
        }
        
        /**
         * Регистрация пользователя
         * 
         * @return boolean Если регистрация успешно прошла - возвращает true.
         */
        public function register(UserData $userData) {
            $this->setError(UserCurrent::ERROR_NONE);
            
            $validateResult = $userData->validate('login', 'password', 'email');
            if ($validateResult === true) {            
                $userData->password = $this->encryptPassword($userData->password);
                $userData->session  = $this->userData->session;
                $userData->createDate = time();
                $userData->loginDate = $userData->createDate;  
                if ($userData->role == UserRoles::GUEST) {
                    $userData->role = UserRoles::USER;    
                }
                if (empty($this->db->getByLogin($userData->login)->id) &&
                    empty($this->db->getByEmail($userData->email)->id)) {
                    if ($this->db->insert($userData)) {
                        $this->userData = $this->db->getByLogin($userData->login);
                        $this->setSessionCookie();
                        return true;
                    } else {
                        $this->setError(UserCurrent::ERROR_DB);
                    }
                } else {
                    $this->setError(UserCurrent::ERROR_USER_ALREADY_EXIST);
                }      
            } else {
                $this->setError(UserCurrent::ERROR_VALIDATE_FIELD, implode("\n", $validateResult));
            }          
            return false;                
        }
        
        /**
         * @return UserData
         */
        public function data() 
        {
            return clone $this->userData;
        }       
        
        /**
         * @return boolean Осуществил ли пользователь вход под своим именем
         */
        public function isRegisteredUser() 
        {
            return !empty($this->userData->id);
        }           
        
        /**
         * @return boolean Является ли пользователь администратором
         */
        public function isAdmin() 
        {
            return $this->userData->role == UserRoles::ADMIN;
        }  
        
        /**
         * @param int $errorCode Код ошибки
         * @param string $errorInfo Текст ошибки
         */
        protected function setError($errorCode, $errorInfo = '') 
        {
            $this->errorCode = $errorCode;
            $this->errorInfo = $errorInfo;
            
            if (empty($this->errorInfo) && $this->errorCode != UserCurrent::ERROR_NONE) {
                switch ($this->errorCode) {
                    case (UserCurrent::ERROR_GENERAL) :
                        $this->errorInfo = "Неопознанная ошибка!";
                        break;
                    case (UserCurrent::ERROR_USER_ALREADY_EXIST) :
                        $this->errorInfo = "Пользователь с таким именем или почтой уже существует!";
                        break;
                    case (UserCurrent::ERROR_DB) :
                        $this->errorInfo = "Ошибка базы данных!";
                        break;
                }                
            }
        }         
        
        /**
         * @return int Код последней ошибки
         */
        public function errorCode() 
        {
            return (int) $this->errorCode;
        }    
        
        /**
         * @return string Описание последней ошибки
         */        
        public function errorInfo() 
        {
            return $this->errorInfo;
        }
        
        protected function setSessionCookie()
        {
            setcookie(UserCurrent::SESSION_COOKIE, $this->userData->session, time() + $this->livetime, "/");
        }
        
        /**
         * Генерация идентификатора сессии
         * 
         * @return string Идентификатор сессии
         */
        protected function generateSessionId() 
        {
            return base64_encode(openssl_random_pseudo_bytes(UserCurrent::SESSID_LENGTH/3*2));
        }     
        
        /**
         * Хэш пароля 
         * 
         * @param string $password Пароль в открытом виде
         * @return string Хэш пароля
         */         
        protected function encryptPassword($password) 
        {
            return md5(md5(trim($password).$this->encryptSalt));
        }         
    }    
