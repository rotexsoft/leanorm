<?php
use Psr\Log\LogLevel;
use Atlas\Pdo\Connection;
use Aura\SqlQuery\QueryFactory;

/**
 * @author rotimi
 */
trait CommonPropertiesAndMethodsTrait {
    
    /**
     * @var \LeanOrm\Model[]
     */
    protected array $testModelObjects = [];
    
    protected static string $dsn;
    protected static ?string $username = null;
    protected static ?string $password = null;
    protected static string $driverName = '';
    protected static QueryFactory $auraQueryFactory;
    protected static \Psr\Log\LoggerInterface $psrLogger;
    
    public static function setUpBeforeClass(): void {
        
        parent::setUpBeforeClass();
        $dsn = getenv('LEANORM_PDO_DSN');
        $username = getenv('LEANORM_PDO_USERNAME');
        $password = getenv('LEANORM_PDO_PASSWORD');
        $connection = null;
        
        if(is_string($dsn) && strlen($dsn) > 0) {
            
            static::$dsn = $dsn;

            if(
                is_string($username) && strlen($username) > 0
                && is_string($password) && strlen($password) > 0
            ) {
                static::$password = $password;
                static::$username = $username;
                $connection = Connection::new(new \PDO($dsn, $username, $password));

            } else {
                
                $connection = Connection::new(new \PDO($dsn)); 
            }
            
        } else {
            
            $connection = static::createSqliteConnection();
        }
        
        static::$driverName = $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        static::$auraQueryFactory = (new QueryFactory(static::$driverName));
        $schemaCreatorAndSeeder = new TestSchemaCreatorAndSeeder($connection);
        $schemaCreatorAndSeeder->createTables();
        $schemaCreatorAndSeeder->populateTables();
        
        ///////////////////////////////////////////////////////////////////
        // CLEAN UP PDO CONNECTION USED FOR CREATING & POPULATING DB TABLES
        ///////////////////////////////////////////////////////////////////
        // We need to do this for sqlite. If we have
        // multiple PDO connections to a sqlite db,
        // it seems like only the first connection
        // would have write access to the DB, other
        // PDO connections after the first one will
        // only have read access to that sqlite db
        // and will not be able to insert, update
        // and delete data during tests on that
        // sqlite database since the PDO connection
        // injected into the Atlas\Pdo\Connection
        // instance in this method would be the first
        // PDO connection that only has write access
        // to the sqlite DB. 
        // 
        // Other DB engines like mysql & postgresql 
        // don't have this issue because they are 
        // designed to support concurrent writes & 
        // are OK with multiple PDO connections 
        // writing (ie. inserting, updating & 
        // deleting data) to them.
        //
        // We want to close this connection so that
        // moving forward, only \LeanOrm\Model::__construct
        // will be creating pdo connections.
        // In fact only one pdo connection gets created for
        // each unique dsn by \LeanOrm\Model::__construct
        // and that pdo connection gets shared across all
        // instances of LeanORM & its subclasses with 
        // the same dsn. We will only be using one dsn
        // to create all instances of \LeanOrm\Model &
        // its sub-classes in our tests, so sqlite will
        // never be accessed with more than one PDO
        // connection
        unset($schemaCreatorAndSeeder);
        unset($connection);
        $schemaCreatorAndSeeder = null; // This was referencing $connection
        $connection = null;             // This had a pdo object inside it that is no longer
                                        // referenced by any other variable, meaning that 
                                        // $schemaCreatorAndSeeder, $connection & the pdo
                                        // object inside $connection are all ready for 
                                        // garbage collection, since they are no longer
                                        // being referenced by any variable.
        \gc_enable();
        \gc_collect_cycles();
        
        static::$psrLogger = new class extends \Psr\Log\AbstractLogger {
            
            protected $min_level = LogLevel::DEBUG;
            protected $levels = [
                LogLevel::DEBUG,
                LogLevel::INFO,
                LogLevel::NOTICE,
                LogLevel::WARNING,
                LogLevel::ERROR,
                LogLevel::CRITICAL,
                LogLevel::ALERT,
                LogLevel::EMERGENCY
            ];

            public function __construct($min_level = LogLevel::DEBUG)
            {
                $this->min_level = $min_level;
            }

            public function log($level, $message, array $context = array())
            {
                if (!$this->min_level_reached($level)) {
                    return;
                }
                echo $this->format($level, $message, $context);
            }

            /**
             * @param string $level
             * @return boolean
             */
            protected function min_level_reached($level)
            {
                return \array_search($level, $this->levels) >= \array_search($this->min_level, $this->levels);
            }

            /**
             * Interpolates context values into the message placeholders.
             *
             * @author PHP Framework Interoperability Group
             *
             * @param string $message
             * @param array $context
             * @return string
             */
            protected function interpolate($message, array $context)
            {
                if (false === strpos($message, '{')) {
                    return $message;
                }

                $replacements = array();
                foreach ($context as $key => $val) {
                    if (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
                        $replacements["{{$key}}"] = $val;
                    } elseif ($val instanceof \DateTimeInterface) {
                        $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
                    } elseif (\is_object($val)) {
                        $replacements["{{$key}}"] = '[object '.\get_class($val).']';
                    } else {
                        $replacements["{{$key}}"] = '['.\gettype($val).']';
                    }
                }

                return strtr($message, $replacements);
            }

            /**
             * @param string $level
             * @param string $message
             * @param array $context
             * @param string|null $timestamp A Timestamp string in format 'Y-m-d H:i:s', defaults to current time
             * @return string
             */
            protected function format($level, $message, $context, $timestamp = null)
            {
                if ($timestamp === null) $timestamp = date('Y-m-d H:i:s');
                return PHP_EOL . '[' . $timestamp . '] ' . strtoupper($level) . ': ' . $this->interpolate($message, $context) . PHP_EOL;
            }
        };
    }
    
    protected static function createSqliteConnection(): Connection {
        
        $sqliteFile = __DIR__.DIRECTORY_SEPARATOR .'DbFiles'.DIRECTORY_SEPARATOR .'blog.sqlite';

        try {
            //create sqlite file & create $dsn connection for it
            $dsn = "sqlite:" . $sqliteFile;
            $connection = Connection::new(new \PDO($dsn));
            static::$dsn = $dsn;

        } catch (\Exception $exc) {
            
            // create an in-memory sqlite db
            $dsn = 'sqlite::memory:';
            $connection = Connection::new(new \PDO($dsn));
            static::$dsn = $dsn;
        }
        
        return $connection;
    }
    
    protected function setUp(): void {
        
        parent::setUp();
        $modelKey = 'authors_with_specialized_collection_and_record';
        
        if(!array_key_exists($modelKey, $this->testModelObjects)) {
            
            $this->testModelObjects[$modelKey] = 
                (
                    new \ModelForTestingPublicAndProtectedMethods(
                        static::$dsn, 
                        static::$username ?? "", 
                        static::$password ?? "", 
                        [], 'author_id', 'authors'
                    )
                )
                ->hasMany(
                    'posts', 
                    'author_id', 
                    'posts', 
                    'author_id', 
                    'post_id', 
                    \ModelForTestingPublicAndProtectedMethods::class, 
                    \RecordForTestingPublicAndProtectedMethods::class, 
                    \CollectionForTestingPublicAndProtectedMethods::class, 
                    null
                )
                ->setCollectionClassName(\CollectionForTestingPublicAndProtectedMethods::class)
                ->setRecordClassName(\RecordForTestingPublicAndProtectedMethods::class)
                ;
        } // if(!array_key_exists($modelKey, $this->testModelObjects))
        
        // re-create tables and repopulate data
        $schemaCreatorAndSeeder = 
            new TestSchemaCreatorAndSeeder(
                Connection::new(
                    $this->testModelObjects[$modelKey]->getPDO()
                )
            );
        $schemaCreatorAndSeeder->createTables();
        $schemaCreatorAndSeeder->populateTables();
        
        
        // cleanup to trigger connection close
        unset($schemaCreatorAndSeeder);
        $schemaCreatorAndSeeder = null; 
        \gc_enable();
        \gc_collect_cycles();
    }
    
    public static function tearDownAfterClass(): void { parent::tearDownAfterClass(); }
    
    protected function tearDown(): void { parent::tearDown(); 

        \gc_enable();
        \gc_collect_cycles();
    
    }
    
    protected function insertDataIntoTable(string $tableName, array $tableData, \PDO $pdo) {
        
        $insertBuilder = static::$auraQueryFactory->newInsert();
        $insertBuilder->into($tableName)->cols($tableData);
        
        // prepare the statement
        $sth = $pdo->prepare($insertBuilder->getStatement());
        
        // execute with bound values
        $sth->execute($insertBuilder->getBindValues());
    }
    
    protected function clearDbTable(\LeanOrm\Model $model):void {
            
        // grab all the ids 
        $ids = $model->fetchCol($model->getSelect()->cols([$model->getPrimaryCol()]));
        
        if(count($ids) > 0) {
            // delete using all the ids, which leads to all rows being deleted
            $model->deleteMatchingDbTableRows([$model->getPrimaryCol()=>$ids]);
        } // if(count($ids) > 0)
    }
    
    protected static function assertArrayHasAllKeys(array $arrayToTest, array $keysToCheck): void {
        
        foreach ($keysToCheck as $key) {
            
            self::assertArrayHasKey($key, $arrayToTest);
        } // foreach ($keysToCheck as $key)
    }
}
