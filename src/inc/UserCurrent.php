<?php
    
namespace Antools {
     
    /**
     * Управление текущим пользователем
     */
    class UserCurrent {
        const SESSION_COOKIE = "ATUID";
        const SESSID_LENGTH = 32;              
        const SESSION_LIVETIME = 2592000; // 2592000сек = месяц          
                        
        const ERROR_NONE = 0;                        
        const ERROR_GENERAL = 1;
        const ERROR_USER_ALREADY_EXIST = 2;
        const ERROR_DB = 3;                               
                        
        /**
         * @var UserData
         */
        protected $userData;
        /**
         * @var UserRepository
         */
        protected $db;
        protected $encryptSalt;
        protected $livetime;        
        protected $errorCode;               
        
        /**
         * @param UserRepository $repository Репозиторий БД
         * @param String $encryptSalt Соль для шифрования
         * @param int $livetime время жизни сессии в секундах
         */
        function __construct(UserRepository $repository, $encryptSalt = "", $livetime = UserCurrent::SESSION_LIVETIME) {                            
            $this->db = $repository;
            $this->encryptSalt = $encryptSalt;
            $this->livetime = $livetime;      
            $this->errorCode = UserCurrent::ERROR_NONE;                      
        }
        
        function __destruct() {
            setcookie(UserCurrent::SESSION_COOKIE, $this->userData->session, time() + $this->livetime, "/");  
        }
                
        /**
         * @param boolean $_restoreSession Восстановить данные пользователя по идентификатору сессии?
         */                
        function init($_restoreSession = true) {
            $this->errorCode = UserCurrent::ERROR_NONE;
            $this->userData = NULL;                  
                                         
            if ($_restoreSession && !empty($_COOKIE[UserCurrent::SESSION_COOKIE])) {            
                $userData = $this->db->get($_COOKIE[UserCurrent::SESSION_COOKIE], UserRepository::GET_BY_SESSION);
                if (!empty($userData->id) &&  $userData->loginDate >= time() - UserCurrent::SESSION_LIVETIME) {
                    $this->userData = $userData;                                        
                }                    
            }    
            
            if (is_null($this->userData)) {
                $this->userData = new UserData();                            
                $this->userData->session = $this->generateSessionId();                       
            }                                                                                                                                                 
        }
                
        /**
         * Аутентификация         
         * @return boolean Если вход успешно прошел - возвращает TRUE.
         */                
        function login($login, $password) {
            $this->errorCode = UserCurrent::ERROR_NONE;
            $userData = $this->db->get($login, UserRepository::GET_BY_LOGIN);
            
            if (!empty($userData->id) && $userData->password == $this->encryptPassword($password)) {
                $this->userData = $userData;      
                $this->userData->loginDate = time();                              
                $this->db->update($this->userData);                                
                return true;
            }
            
            return false;
        }
        
        /**
         * Завершение сессии
         */
        function logout() {
            $this->errorCode = UserCurrent::ERROR_NONE;
            
            if ($this->isRegisteredUser()) {
                $this->userData->session = "";
                $this->db->update($this->userData); 
            }           
            $this->init(false);            
        }
        
        /**
         * Регистрация пользователя
         * @return boolean Если регистрация успешно прошла - возвращает TRUE.
         */
        function register(UserData $userData) {
            $userData->password = $this->encryptPassword($userData->password);
            $userData->session  = $this->userData->session;
            $userData->createDate = time();
            $userData->loginDate = $userData->createDate;  
            if (empty($this->db->get($userData->login, UserRepository::GET_BY_LOGIN)->id) &&
                empty($this->db->get($userData->email, UserRepository::GET_BY_EMAIL)->id)) {
                    
                if ($this->db->insert($userData)) {
                    $this->userData = $this->db->get($userData->login, UserRepository::GET_BY_LOGIN);
                    $this->errorCode = UserCurrent::ERROR_NONE;
                    return true;
                }
                else {
                    $this->errorCode = UserCurrent::ERROR_DB;
                }
            }    
            else {
                $this->errorCode = UserCurrent::ERROR_USER_ALREADY_EXIST;
            }                
            return false;                
        }
        
        /**
         * @return UserData
         */
        function data() {
            return clone $this->userData;
        }       
        
        /**
         * @return boolean Осуществил ли пользователь вход под своим именем
         */
        function isRegisteredUser() {
            return !empty($this->userData->id);
        }           
        
        /**
         * @return boolean Является ли пользователь администратором
         */
        function isAdmin() {
            return $this->userData->role == UserData::ROLE_ADMIN;
        }  
        
        /**
         * @return int Код последней ошибки
         */
        function errorCode() {
            return (int) $this->errorCode;
        }        
        
        /**
         * @return int Описание последней ошибки
         */        
        function errorInfo() {
            switch ($this->errorCode) {
                case (UserCurrent::ERROR_GENERAL) :
                    return "Неопознанная ошибка!";
                case (UserCurrent::ERROR_USER_ALREADY_EXIST) :
                    return "Пользователь с таким именем или почтой уже существует!";
                case (UserCurrent::ERROR_DB) :
                    return "Ошибка базы данных!";                                            
            }
            return "";
        }
        
        /**
         * Генерация идентификатора сессии
         */
        protected function generateSessionId() {
            return openssl_random_pseudo_bytes(UserCurrent::SESSID_LENGTH);
        }     
        
        /**
         * Хэш пароля 
         */         
        protected function encryptPassword($password) {
            return md5(md5($this->encryptSalt.trim($password)));
        }         
    }    

}
        
?>