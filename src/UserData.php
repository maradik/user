<?php
    namespace Maradik\User; 
    use Respect\Validation\Validator;
    
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
         * @param int $createDate timestamp
         * @param int $loginDate timestamp
         */                        
        public function __construct(
            $id         = 0, 
            $login      = "", 
            $email      = "", 
            $session    = "", 
            $password   = "", 
            $role       = UserRoles::USER, 
            $createDate = 0, 
            $loginDate  = 0
        ) {
            $this->id           = (int) $id;    
            $this->login        = $login;
            $this->email        = $email;
            $this->session      = $session;
            $this->password     = $password;
            $this->role         = $role;
            $this->createDate   = (int) $createDate;
            $this->loginDate    = (int) $loginDate;               
        }        
        
        /**
         * Проверка валидности данных в поле объекта. Принимает произвольное число аргументов (названия полей).
         *
         * @param string|string[]|null $fields Если не задано - проверяются все поля.
         * @return boolean|string[] Возвращает true в случае успеха, иначе - массив ошибок
         */
        public function validate($fields = null) 
        {
            $allFields = array_keys(get_object_vars($this));
            $args = is_array($fields) ? $fields : func_get_args();
                
            if (!empty($args) && sizeof(array_diff($args, $allFields))) {
                throw new \InvalidArgumentException('Некорректные аргументы в методе ' . __METHOD__);
            }              

            $fieldNames = empty($args)
                ? $allFields
                : array_intersect($args, $allFields);   
                    
            unset($allFields);
            unset($args);
            
            $v = array();
            
            foreach ($fieldNames as $fieldName) {
                switch ($fieldName) {
                    case 'id':
                        $v[] = Validator::attribute(
                            $fieldName, 
                            Validator::int()->min(0, true)
                        )->setName($fieldName)
                        ->setTemplate('ID должно быть целым числом не меньше 0.');
                        break;                
                    case 'login':
                        $v[] = Validator::attribute(
                            $fieldName, 
                            Validator::alnum()->noWhitespace()->notEmpty()->length(1,20)
                        )->setName($fieldName)
                        ->setTemplate('Имя должно состоять из букв и цифр длиной не более 20 симв.');
                        break;
                    case 'email':
                        $v[] = Validator::attribute(
                            $fieldName, 
                            Validator::email()->notEmpty()
                        )->setName($fieldName)
                        ->setTemplate('Некорректный формат e-mail.');
                        break;     
                    case 'password':
                        $v[] = Validator::attribute(
                            $fieldName, 
                            Validator::notEmpty()->length(6,32)
                        )->setName($fieldName)
                        ->setTemplate('Пароль должен быть длиной от 6 до 32 символов.');
                        break;     
                    case 'role':
                        $v[] = Validator::attribute(
                            $fieldName, 
                            Validator::int()->in(array(UserRoles::USER, UserRoles::MODERATOR, UserRoles::ADMIN))
                        )->setName($fieldName)
                        ->setTemplate('Недопустимое значение в поле Роль.');
                        break;    
                    case 'createDate':
                        $v[] = Validator::attribute($fieldName, Validator::int()->min(0, true))
                            ->setName($fieldName)
                            ->setTemplate('Недопустимое значение в поле Дата создания.');
                        break;   
                    case 'loginDate':
                        $v[] = Validator::attribute($fieldName, Validator::int()->min(0, true))
                            ->setName($fieldName)
                            ->setTemplate('Недопустимое значение в поле Дата входа.');
                        break; 
                }                
            }
            
            try {
                Validator::allOf($v)->assert($this);
            } catch(\Respect\Validation\Exceptions\ValidationException $e) {
                return array_filter($e->findMessages($fieldNames), function($item) { return !empty($item); });
                //return $e->findMessages(array_values(array_map(function($item) { return $item->getName(); }, $v)));
            }
            
            return true;
        }                                                    
    }
