<?php

namespace Antools {
    
    /**
     * Обработка сущности UserData в БД
     */
    class UserRepository {        
        const GET_BY_ID = "id";
        const GET_BY_SESSION = "session";
        const GET_BY_LOGIN = "login";
        const GET_BY_EMAIL = "email";                  
        
        const ERROR_TEXT_DB = "Операция с БД вызвала ошибку!";
        
        const FILENAME_INITTABLES = '../queries/userrepository_init.sql';
        
        /**
         * @var \PDO
         */
        protected $db;
        protected $tablePrefix;
        protected $userTable;        
            
        /**
         * @param \PDO $pdo Объект для взаимодействия с БД
         * @param String $userTable Наименование таблицы для хранения пользователей
         * @param String $tablePrefix Префикс таблицы
         * @param boolean $skipInitTables Пропустить инициализацию таблиц в случае их отсутствия. Если таблицы уже существуют в БД, рекомендуется передавать значение true.
         */
        function __construct(\PDO $pdo, $userTable = "user", $tablePrefix = "", $skipInitTables = false) {
            if (!($pdo instanceof \PDO))
                throw new Exception('Invalid parameter $pdo', 1);    
            
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
        protected function initTables() {            
            $res = $this->db->query("select 'x' from {$this->userTableName()} limit 1");
            
            if ($res === false) {            
                $sql = file_get_contents(__DIR__.'/'.UserRepository::FILENAME_INITTABLES, true);
                $sql = str_replace('%usertablename%', $this->userTableName(), $sql);
                
                if ($sql !== false && !empty($sql)) {                        
                    $res = $this->db->query($sql);   
                }
                else {
                    $res = false;                    
                } 
                
                if ($res === false) {
                    throw new \Exception("Ошибка при попытке инициализации таблиц!");                    
                }
            }                
        }        
        
        /**
         * @param String $keyValue Значение для поиска пользователя
         * @param String $keyField Поле, по которому осуществляется поиск
         * @return UserData Данные одного пользователя 
         */
        function get($keyValue, $keyField = UserRepository::GET_BY_ID) {
            try { 
                $q = $this->db->prepare("select * from `{$this->userTableName()}` where {$keyField}=? limit 1");
                $res = $q->execute(array($keyValue)); 
                if ($res)                 
                    $row = $q->fetch(\PDO::FETCH_ASSOC);                       
                return !$res || $row === false ? new UserData() : $this->rowToUserData($row);                                    
            }
            catch (\Exception $err) {
                throw new \Exception(ERROR_TEXT_DB, 0, $err);                
            }
            return null;
        }   
        
        /**
         * @param UserData $userData Данные пользователя
         * @return boolean Результат запроса к БД
         */
        function update(UserData $userData) {
            if (empty($userData->id))
                return false; 
            
            try {
                $q = $this->db->prepare("update `{$this->userTableName()}` set `login` = ?, `email` = ?, `session` = ?, `password` = ?, `role` = ?, `createdate` = ?, `logindate` = ? where `id` = ?");
                $res = $q->execute(array($userData->login, $userData->email, $userData->session, $userData->password, $userData->role, $userData->createDate, $userData->loginDate, $userData->id));
                return $res;                
            }
            catch (\Exception $err) {
                throw new \Exception(ERROR_TEXT_DB, 0, $err);  
            }
            
            return false;               
        }   
        
        /**
         * @param UserData $userData Данные пользователя
         * @return boolean Результат запроса к БД
         */        
        function insert(UserData $userData) {
            try {
                $q = $this->db->prepare("insert into `{$this->userTableName()}` (`login`, `email`, `session`, `password`, `role`, `createdate`, `logindate`) values (?, ?, ?, ?, ?, ?, ?)");
                $res = $q->execute(array($userData->login, $userData->email, $userData->session, $userData->password, $userData->role, $userData->createDate, $userData->loginDate));                
                return $res;                
            }
            catch (\Exception $err) {
                throw new \Exception(ERROR_TEXT_DB, 0, $err);              
            }
            
            return false;            
        }
        
        /**
         * @param int $id Идентификатор пользователя
         * @return boolean Результат запроса к БД
         */        
        function delete($id) {
            try {
                $q = $this->db->prepare("delete from `{$this->userTableName()}` where `id`=?");
                $res = $q->execute(array($userData->id));                
                return $res;                
            }
            catch (\Exception $err) {
                throw new \Exception(ERROR_TEXT_DB, 0, $err);              
            }
            
            return false;            
        }        
        
        /**
         * @return string Полное имя таблицы для хранения данных пользователей.
         */
        protected function userTableName() {            
            return empty($this->tablePrefix) ?
                $this->userTable :
                "{$this->tablePrefix}_{$this->userTable}";
        }
        
        /**
         * Преобразует строку данных $data в объект UserData и возвращает его         
         * @param Array $data         
         * @return UserData
         */
        protected function rowToUserData(Array $data) {                                                                      
            $data = array_change_key_case($data, CASE_UPPER);
            $userData = new UserData();             
            foreach ($userData as $key => $val) {
                $upkey = strtoupper($key);
                if (array_key_exists($upkey, $data)) {
                    $userData->$key = $data[$upkey];      
                }                                    
            }  
            return $userData;                             
        } 
    }

}
?>