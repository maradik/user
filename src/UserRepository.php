<?php

    namespace Maradik\User;
    
    /**
     * Обработка сущности UserData в БД
     */
    class UserRepository 
    {        
        const FIELD_ID = "id";
        const FIELD_SESSION = "session";
        const FIELD_LOGIN = "login";
        const FIELD_EMAIL = "email";                  
        
        const ERROR_TEXT_DB = "Операция с БД вызвала ошибку!";
               
        /**
         * @var \PDO $db
         */
        protected $db;
        
        /**
         * @var string $tablePrefix
         */        
        protected $tablePrefix;
        
        /**
         * @var string $userTable
         */         
        protected $userTable;        
            
        /**
         * @param \PDO $pdo Объект для взаимодействия с БД
         * @param string $userTable Наименование таблицы для хранения пользователей
         * @param string $tablePrefix Префикс таблицы
         * @param boolean $skipInitTables Пропустить инициализацию таблиц в случае их отсутствия. Если таблицы уже существуют в БД, рекомендуется передавать значение true.
         */
        public function __construct(\PDO $pdo, $userTable = "user", $tablePrefix = "", $skipInitTables = false) 
        {
            if (empty($pdo) || !($pdo instanceof \PDO)) {
                throw new BadMethodCallException('Invalid parameter $pdo', 1);
            }    
            
            $this->db = $pdo;
            $this->tablePrefix = $tablePrefix;
            $this->userTable   = $userTable;     
            
            if (!$skipInitTables) {
                $this->initTables();
            }
        }   
        
        /**
         * Инициализация таблиц БД для хранения данных.
         */
        protected function initTables() 
        {            
            $res = $this->db->query("select 'x' from {$this->userTableName()} limit 1");
            
            if ($res === false) {           
                $sql = "CREATE TABLE `{$this->userTableName()}` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `login` varchar(20) NOT NULL,
                          `password` varchar(20) NOT NULL,
                          `email` varchar(255) NOT NULL,
                          `session` varchar(32) NOT NULL,
                          `role` int(11) NOT NULL DEFAULT '0',
                          `createdate` int(11) NOT NULL,
                          `logindate` int(11) DEFAULT NULL,
                          PRIMARY KEY (`id`),
                          UNIQUE KEY `login` (`login`),
                          UNIQUE KEY `session` (`session`),
                          UNIQUE KEY `email` (`email`)
                        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32;";              
                     
                $res = $this->db->query($sql);   
                
                if ($res === false) {
                    throw new \Exception("Ошибка при попытке инициализации таблиц!");                    
                }
            }                
        }        
        
        /**
         * Поиск пользователя в БД по ключевому полю.  
         *
         * @param string $keyValue Значение для поиска пользователя
         * @param string $keyField Поле, по которому осуществляется поиск
         * @return UserData Данные одного пользователя 
         */
        protected function get($keyField, $keyValue) 
        {
            try { 
                $q = $this->db->prepare("select * from `{$this->userTableName()}` where {$keyField}=? limit 1");
                $res = $q->execute(array($keyValue)); 
                if ($res)                 
                    $row = $q->fetch(\PDO::FETCH_ASSOC);                       
                return !$res || $row === false ? new UserData() : $this->rowToUserData($row);                                    
            } catch (\Exception $err) {
                throw new \Exception(ERROR_TEXT_DB, 0, $err);                
            }
            return null;
        }   
        
        /**
         * Поиск пользователя в БД по id
         *
         * @param string $id Значение идентификатора Id для поиска пользователя         
         * @return UserData Данные одного пользователя 
         */
        public function getById($id) 
        {
            return $this->get(UserRepository::FIELD_ID, $id);
        }     
        
        /**
         * Поиск пользователя в БД по login
         *
         * @param string $login Имя пользователя login для поиска пользователя         
         * @return UserData Данные одного пользователя 
         */
        public function getByLogin($login) 
        {
            return $this->get(UserRepository::FIELD_LOGIN, $login);
        }                     
        
        /**
         * Поиск пользователя в БД по email
         *
         * @param string $email Значение email для поиска пользователя         
         * @return UserData Данные одного пользователя 
         */
        public function getByEmail($email) 
        {
            return $this->get(UserRepository::FIELD_EMAIL, $email);
        }          
        
        /**
         * Поиск пользователя в БД по session
         *
         * @param string $session Значение session для поиска пользователя         
         * @return UserData Данные одного пользователя 
         */
        public function getBySession($session) 
        {
            return $this->get(UserRepository::FIELD_SESSION, $session);
        }          
        
        /**
         * @param UserData $userData Данные пользователя
         * @return boolean Результат запроса к БД
         */
        public function update(UserData $userData) 
        {
            if (empty($userData->id)) {
                return false; 
            }                
            
            try {
                $q = $this->db->prepare(
                    "update `{$this->userTableName()}` 
                     set `login` = ?, `email` = ?, `session` = ?,  
                         `password` = ?, `role` = ?, `createdate` = ?, `logindate` = ? 
                     where `id` = ?"
                );
                $res = $q->execute(array(
                    $userData->login, 
                    $userData->email, 
                    $userData->session, 
                    $userData->password, 
                    $userData->role, 
                    $userData->createDate, 
                    $userData->loginDate, 
                    $userData->id
                ));
                
                return $res;                
            } catch (\Exception $err) {
                throw new \Exception(ERROR_TEXT_DB, 0, $err);  
            }
            
            return false;               
        }   
        
        /**
         * @param UserData $userData Данные пользователя
         * @return boolean Результат запроса к БД
         */        
        public function insert(UserData $userData) 
        {
            try {
                $q = $this->db->prepare(
                    "insert into `{$this->userTableName()}` 
                     (`login`, `email`, `session`, `password`, `role`, `createdate`, `logindate`)  
                     values (?, ?, ?, ?, ?, ?, ?)"
                );
                $res = $q->execute(array(
                    $userData->login, 
                    $userData->email, 
                    $userData->session, 
                    $userData->password, 
                    $userData->role, 
                    $userData->createDate, 
                    $userData->loginDate
                ));         
                       
                return $res;                
            } catch (\Exception $err) {
                throw new \Exception(ERROR_TEXT_DB, 0, $err);              
            }
            
            return false;            
        }
        
        /**
         * @param int $id Идентификатор пользователя
         * @return boolean Результат запроса к БД
         */        
        public function delete($id) 
        {
            try {
                $q = $this->db->prepare("delete from `{$this->userTableName()}` where `id`=?");
                $res = $q->execute(array($userData->id));                
                return $res;                
            } catch (\Exception $err) {
                throw new \Exception(ERROR_TEXT_DB, 0, $err);              
            }
            
            return false;            
        }        
        
        /**
         * @return string Полное имя таблицы для хранения данных пользователей.
         */
        protected function userTableName() 
        {            
            return empty($this->tablePrefix) ? $this->userTable : "{$this->tablePrefix}_{$this->userTable}";
        }
        
        /**
         * Преобразует строку данных $data в объект UserData и возвращает его  
         *        
         * @param Array $data         
         * @return UserData
         */
        protected function rowToUserData(Array $data) 
        {
            $data = array_change_key_case($data);
            $userData = new UserData(
                $data['id'],
                $data['login'],
                $data['email'],
                $data['session'],
                $data['password'],
                $data['role'],
                $data['createdate'],
                $data['logindate']   
            );                             
        } 
    }

