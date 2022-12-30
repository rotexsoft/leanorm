<?php
/**
 * Description of DBConnectorTest
 *
 * @author rotimi
 */
class DBConnectorTest extends \PHPUnit\Framework\TestCase {
    
    use CommonPropertiesAndMethodsTrait;
    
    public function testThat_initDbConfigWithDefaultValsWorksAsExpected() {
        
        self::assertCount(1, DbConnectorSubclass::getConfig()); // config set for the PDO object for LeanOrm CommonPropertiesAndMethodsTrait
        DbConnectorSubclass::initDbConfigWithDefaultValsPublic('another_connection');
        $config = DbConnectorSubclass::getConfig();
        $defaultConfig = DbConnectorSubclass::getDefaultConfig();
        
        self::assertCount(2, $config);
        self::assertArrayHasKey('another_connection', $config);
        self::assertEquals($defaultConfig, $config['another_connection']);
        
        // Repeat
        DbConnectorSubclass::initDbConfigWithDefaultValsPublic('another_connection');
        $config = DbConnectorSubclass::getConfig();
        $defaultConfig = DbConnectorSubclass::getDefaultConfig();
        
        self::assertCount(2, $config);
        self::assertArrayHasKey('another_connection', $config);
        self::assertEquals($defaultConfig, $config['another_connection']);
    }
    
    public function testThat_setupDbWorksAsExpected() {
        
        self::assertCount(1, DbConnectorSubclass::getDbObj()); // contains only the PDO object for LeanOrm set in CommonPropertiesAndMethodsTrait
        DbConnectorSubclass::setupDbPublic('another_connection');
        self::assertCount(2, DbConnectorSubclass::getDbObj());
        
        foreach (DbConnectorSubclass::getDbObj() as $dbObj) {
            
            self::assertInstanceOf(\PDO::class, $dbObj);
        } // foreach (DbConnectorSubclass::getDbObj() as $dbObj)
        
        // Repeat
        DbConnectorSubclass::setupDbPublic('another_connection');
        self::assertCount(2, DbConnectorSubclass::getDbObj());
        
        foreach (DbConnectorSubclass::getDbObj() as $dbObj) {
            
            self::assertInstanceOf(\PDO::class, $dbObj);
        } // foreach (DbConnectorSubclass::getDbObj() as $dbObj)
    }
    
    public function testThat_executeWorksAsExpected() {
        
        self::assertTrue(
            DbConnectorSubclass::executePublic('Select * from posts', [], false, static::$dsn)
        );
        
        $execResult = DbConnectorSubclass::executePublic(
            'Select * from authors where author_id in (?, ?, ?, ?, ?, ?) and name like ? ', 
            (static::$driverName === 'pgsql')
                ? [1, 5, 10, null, 0, 1, 'user_1%']
                : [1, 5, 10, null, true, false, 'user_1%']
            , 
            true, static::$dsn
        );
        
        self::assertIsArray($execResult);
        self::assertArrayHasAllKeys($execResult, ['query_result', 'pdo_statement', 'exec_time_in_seconds']);
        self::assertIsBool($execResult['query_result']);
        self::assertInstanceOf(\PDOStatement::class, $execResult['pdo_statement']);
        self::assertIsFloat($execResult['exec_time_in_seconds']);
        
        // lets loop through the query results & assert the rows we are expecting
        $records = $execResult['pdo_statement']->fetchAll(\PDO::FETCH_ASSOC);
        
        self::assertCount(2, $records);
        
        foreach($records as $record) {
            
            self::assertArrayHasAllKeys($record, ["author_id", "name", "m_timestamp", "date_created"]);
        }
        
        self::assertEquals(
            (static::$driverName === 'pgsql')
                ? [1, 10] // postgres driver correctly returns ints, why mysql & sqlite return ints in a string
                : ['1', '10'], 
            array_column($records, "author_id")
        );
        
        self::assertEquals(
            ['user_1', 'user_10'], 
            array_column($records, "name")
        );
    }
    
    public function testThatConfigureWorksAsExpected() {
        
        $connectionName = 'connection-1';
        
        $expectedConfig = [
            'connection_string' => 'sqlite::memory:',
            'error_mode' => \PDO::ERRMODE_EXCEPTION,
            'username' => null,
            'password' => null,
            'driver_options' => [],
        ];
        DbConnectorSubclass::configure('driver_options', [], $connectionName);
        self::assertEquals($expectedConfig, DbConnectorSubclass::getConfig()[$connectionName]);
        
        $expectedConfig2 = [
            'connection_string' => 'sqlite::memory:',
            'error_mode' => \PDO::ERRMODE_EXCEPTION,
            'username' => 'root',
            'password' => 'root',
            'driver_options' => ['a-val'],
        ];
        DbConnectorSubclass::configure($expectedConfig2, null, $connectionName);
        self::assertEquals($expectedConfig2, DbConnectorSubclass::getConfig()[$connectionName]);
        
        $expectedConfig3 = [
            'connection_string' => static::$dsn,
            'error_mode' => \PDO::ERRMODE_EXCEPTION,
            'username' => 'root',
            'password' => 'root',
            'driver_options' => ['a-val'],
        ];
        DbConnectorSubclass::configure(static::$dsn, null, $connectionName);
        self::assertEquals($expectedConfig3, DbConnectorSubclass::getConfig()[$connectionName]);
    }
    
    public function testThatCreateWorksAsExpected() {
        
        $dbConnector = DbConnectorSubclass::create();
        self::assertEquals(DbConnectorSubclass::DEFAULT_CONNECTION, $dbConnector->getConnectionName());
        
        $connectionName = 'connection-1b';
        $dbConnector2 = DbConnectorSubclass::create($connectionName);
        self::assertEquals($connectionName, $dbConnector2->getConnectionName());
    }
    
    public function testThatSetDbWorksAsExpected() {
        
        $pdo = new \PDO('sqlite::memory:');
        DbConnectorSubclass::setDb($pdo);
        self::assertSame($pdo, DbConnectorSubclass::getDb());
        
        $connectionName = 'connection-1';
        $pdo2 = new \PDO('sqlite::memory:');
        DbConnectorSubclass::setDb($pdo2, $connectionName);
        self::assertSame($pdo2, DbConnectorSubclass::getDb($connectionName));
        
        self::assertSame($pdo, DbConnectorSubclass::getDb());
        self::assertSame($pdo2, DbConnectorSubclass::getDb($connectionName));
    }
    
    public function testThatGetDbWorksAsExpected() {
        
        self::assertSame(
            DbConnectorSubclass::getDb(),
            DbConnectorSubclass::getDb()
        );
        
        $pdo = new \PDO('sqlite::memory:');
        DbConnectorSubclass::setDb($pdo);
        self::assertSame($pdo, DbConnectorSubclass::getDb());
        
        $connectionName = 'connection-1';
        $pdo2 = new \PDO('sqlite::memory:');
        DbConnectorSubclass::setDb($pdo2, $connectionName);
        self::assertSame($pdo2, DbConnectorSubclass::getDb($connectionName));
        
        self::assertSame($pdo, DbConnectorSubclass::getDb());
        self::assertSame($pdo2, DbConnectorSubclass::getDb($connectionName));
    }
    
    public function testThatExecuteQueryWorksAsExpected() {
        
        $model = new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {
            
                public function getDbConnector(): \LeanOrm\DBConnector {
                    
                    return $this->db_connector;
                }
            };
        
        $dbConnector = $model->getDbConnector();
            
        self::assertTrue(
            $dbConnector->executeQuery('Select * from posts', [], false, static::$dsn)
        );
        
        $execResult = $dbConnector->executeQuery(
            'Select * from authors where author_id in (?, ?, ?, ?, ?, ?) and name like ? ', 
            (static::$driverName === 'pgsql')
                ? [1, 5, 10, null, 0, 1, 'user_1%']
                : [1, 5, 10, null, true, false, 'user_1%']
            , 
            true, static::$dsn
        );
        
        self::assertIsArray($execResult);
        self::assertArrayHasAllKeys($execResult, ['query_result', 'pdo_statement', 'exec_time_in_seconds']);
        self::assertIsBool($execResult['query_result']);
        self::assertInstanceOf(\PDOStatement::class, $execResult['pdo_statement']);
        self::assertIsFloat($execResult['exec_time_in_seconds']);
        
        // lets loop through the query results & assert the rows we are expecting
        $records = $execResult['pdo_statement']->fetchAll(\PDO::FETCH_ASSOC);
        
        self::assertCount(2, $records);
        
        foreach($records as $record) {
            
            self::assertArrayHasAllKeys($record, ["author_id", "name", "m_timestamp", "date_created"]);
        }
        
        self::assertEquals(
            (static::$driverName === 'pgsql')
                ? [1, 10] // postgres driver correctly returns ints, why mysql & sqlite return ints in a string
                : ['1', '10'], 
            array_column($records, "author_id")
        );
        
        self::assertEquals(
            ['user_1', 'user_10'], 
            array_column($records, "name")
        );
    }
    
    public function testThatGetConnectionNameWorksAsExpected() {
        
        $dbConnector = DbConnectorSubclass::create();
        self::assertEquals(DbConnectorSubclass::DEFAULT_CONNECTION, $dbConnector->getConnectionName());
        
        $connectionName = 'connection-1';
        $dbConnector2 = DbConnectorSubclass::create($connectionName);
        self::assertEquals($connectionName, $dbConnector2->getConnectionName());
    }
    
    public function testThatDbFetchOneWorksAsExpected() {
        
        $model = new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {
            
                public function getDbConnector(): \LeanOrm\DBConnector {
                    
                    return $this->db_connector;
                }
            };
        $dbConnector = $model->getDbConnector();

        $result = $dbConnector->dbFetchOne('select * from authors');
        self::assertIsArray($result);
        self::assertArrayHasAllKeys($result, ['author_id', 'name', 'm_timestamp', 'date_created']);
        self::assertEquals('1', $result['author_id'].'');
        self::assertEquals('user_1', $result['name'].'');

        $result2 = $dbConnector->dbFetchOne('select * from authors where author_id = ? ', [10]);
        self::assertIsArray($result2);
        self::assertArrayHasAllKeys($result2, ['author_id', 'name', 'm_timestamp', 'date_created']);
        self::assertEquals('10', $result2['author_id'].'');
        self::assertEquals('user_10', $result2['name'].'');
    }
    
    public function testThatDbFetchAllWorksAsExpected() {
        
        $model = new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {
            
                public function getDbConnector(): \LeanOrm\DBConnector {
                    
                    return $this->db_connector;
                }
            };
        $dbConnector = $model->getDbConnector();

        $result = $dbConnector->dbFetchAll('select * from authors order by author_id asc');
        self::assertIsArray($result);
        self::assertCount(10, $result);
        $i = 1;
        foreach($result as $record){
            
            self::assertIsArray($record);
            self::assertArrayHasAllKeys($record, ['author_id', 'name', 'm_timestamp', 'date_created']);
            self::assertEquals("{$i}", $record['author_id'].'');
            self::assertEquals("user_{$i}", $record['name'].'');
            $i++;
        }

        $result2 = $dbConnector->dbFetchAll('select * from authors where author_id >= ? order by author_id asc', [6]);
        self::assertIsArray($result2);
        self::assertCount(5, $result2);
        $i = 6;
        foreach($result2 as $record){
            
            self::assertIsArray($record);
            self::assertArrayHasAllKeys($record, ['author_id', 'name', 'm_timestamp', 'date_created']);
            self::assertEquals("{$i}", $record['author_id'].'');
            self::assertEquals("user_{$i}", $record['name'].'');
            $i++;
        }
    }
    
    public function testThatDbFetchColWorksAsExpected() {
        
        $model = new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {
            
                public function getDbConnector(): \LeanOrm\DBConnector {
                    
                    return $this->db_connector;
                }
            };
        $dbConnector = $model->getDbConnector();

        // fetches the first column in the table, ie. author_id
        $result = $dbConnector->dbFetchCol('select * from authors order by author_id asc');
        self::assertIsArray($result);
        self::assertCount(10, $result);
        $i = 1;
        foreach($result as $author_id){
            
            self::assertEquals("{$i}", $author_id.'');
            $i++;
        }

        // fetches the specified column in the table, ie. name
        $result2 = $dbConnector->dbFetchCol('select name from authors where author_id >= ? order by author_id asc', [6]);
        self::assertIsArray($result2);
        self::assertCount(5, $result2);
        $i = 6;
        foreach($result2 as $name){
            
            self::assertEquals("user_{$i}", $name.'');
            $i++;
        }
    }
    
    public function testThatDbFetchPairsWorksAsExpected() {
        
        $model = new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {
            
                public function getDbConnector(): \LeanOrm\DBConnector {
                    
                    return $this->db_connector;
                }
            };
        $dbConnector = $model->getDbConnector();

        // fetches the first 2 columns in the table, ie. author_id , name
        $result = $dbConnector->dbFetchPairs('select * from authors order by author_id asc');
        self::assertIsArray($result);
        self::assertCount(10, $result);
        $i = 1;
        foreach($result as $author_id => $name){
            
            self::assertEquals("{$i}", $author_id.'');
            self::assertEquals("user_{$i}", $name.'');
            $i++;
        }

        // fetches the specified columns in the table, ie. name
        $result2 = $dbConnector->dbFetchPairs('select name, author_id  from authors where author_id >= ? order by author_id asc', [6]);
        self::assertIsArray($result2);
        self::assertCount(5, $result2);
        $i = 6;
        foreach($result2 as $name => $author_id){
            
            self::assertEquals("{$i}", $author_id.'');
            self::assertEquals("user_{$i}", $name.'');
            $i++;
        }
    }
    
    
    public function testThatDbFetchValueWorksAsExpected() {
        
        $model = new class(
                    static::$dsn, 
                    static::$username ?? "", 
                    static::$password ?? "", 
                    [], 'author_id', 'authors'
                ) extends \LeanOrm\Model {
            
                public function getDbConnector(): \LeanOrm\DBConnector {
                    
                    return $this->db_connector;
                }
            };
        $dbConnector = $model->getDbConnector();

        // fetch first value from the first column of the query result, 
        // in this case author_id is the first column
        $result = $dbConnector->dbFetchValue('select * from authors');
        self::assertEquals('1', $result.'');

        // fetch first value from the specified column (name) of the query result
        $result2 = $dbConnector->dbFetchValue('select name from authors where author_id = ? ', [10]);
        self::assertEquals('user_10', $result2.'');
    }
}
