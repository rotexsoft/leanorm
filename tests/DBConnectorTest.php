<?php
use \LeanOrm\DBConnector;

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

    public function testThatGetQueryLogWorksAsExpected() {
        
        $log_entry_keys_to_check = [
            DBConnector::LOG_ENTRY_SQL_KEY,
            DBConnector::LOG_ENTRY_BIND_PARAMS_KEY,
            DBConnector::LOG_ENTRY_DATE_EXECUTED_KEY,
            DBConnector::LOG_ENTRY_EXEC_TIME_KEY,
            DBConnector::LOG_ENTRY_CALL_STACK_KEY,
            DBConnector::LOG_ENTRY_CALLING_OBJECT_HASH,
        ];
        $model = new \LeanOrm\Model(
            static::$dsn, 
            static::$username ?? "", 
            static::$password ?? "", 
            [], 'author_id', 'authors'
        );
        
        \LeanOrm\DBConnector::clearQueryLog(null); // empty the whole log first
        self::assertEquals([], DBConnector::getQueryLog(null));
        
        $db_connector = $model->getDbConnector();
        $db_connector->runQuery('Select * from posts');
        // logging is disabled by default
        self::assertEquals([], DBConnector::getQueryLog(null));

        // now enable logging
        $db_connector->enableQueryLogging();
        $query1 = 'Select * from posts';
        $db_connector->runQuery($query1);
        $all_log_entries = DBConnector::getQueryLog(null);

        // Only one entry for the connection name of $db_connector 
        // should be in the log query array at this point
        self::assertCount(1, $all_log_entries);
        // Connection name of $db_connector should be the root key
        // in the log query array at this point
        self::assertArrayHasKey($db_connector->getConnectionName(), $all_log_entries);
        
        // $all_log_entries[$db_connector->getConnectionName()] should be an array
        // with one item whose key is $db_connector::class
        self::assertCount(1, $all_log_entries[$db_connector->getConnectionName()]);
        self::assertArrayHasKey($db_connector::class, $all_log_entries[$db_connector->getConnectionName()]);
        
        // $all_log_entries[$db_connector->getConnectionName()][$db_connector::class]
        // should contain all query log entires for queries executed via $db_connector
        $log_entries_for_db_connector =
            $all_log_entries[$db_connector->getConnectionName()][$db_connector::class];
         self::assertCount(1, $log_entries_for_db_connector);
        
        foreach($log_entries_for_db_connector as $log_entry) {
            
            self::assertArrayHasAllKeys($log_entry, $log_entry_keys_to_check);
        }
        
        ////////////////////////////////////////////////////////////////////////
        // Execute a second query
        ////////////////////////////////////////////////////////////////////////
        $query2_params = (static::$driverName === 'pgsql')
                            ? [1, 5, 10, null, 0, 1]
                            : [1, 5, 10, null, true, false];
        $db_connector->runQuery(
            'Select * from authors where author_id in (?, ?, ?, ?, ?, ?) ', 
            $query2_params
        );
        
        // Get latest content of the query log after second query above
        $all_log_entries_after_second_query = DBConnector::getQueryLog(null);
        
        // Only one entry for the connection name of $db_connector 
        // should still be in the log query array at this point
        self::assertCount(1, $all_log_entries_after_second_query);
        // Connection name of $db_connector should be the root key
        // in the log query array at this point
        self::assertArrayHasKey($db_connector->getConnectionName(), $all_log_entries_after_second_query);
        
        // $all_log_entries_after_second_query[$db_connector->getConnectionName()] should be an array
        // with one item whose key is $db_connector::class.
        self::assertCount(1, $all_log_entries_after_second_query[$db_connector->getConnectionName()]);
        self::assertArrayHasKey($db_connector::class, $all_log_entries_after_second_query[$db_connector->getConnectionName()]);
        
        // $all_log_entries_after_second_query[$db_connector->getConnectionName()][$db_connector::class]
        // should contain all query log entires for queries executed via $db_connector
        $log_entries_for_db_connector =
            $all_log_entries_after_second_query[$db_connector->getConnectionName()][$db_connector::class];
         self::assertCount(2, $log_entries_for_db_connector);
        
        foreach($log_entries_for_db_connector as $log_entry) {
            
            self::assertArrayHasAllKeys($log_entry, $log_entry_keys_to_check);
            
            if($log_entry[DBConnector::LOG_ENTRY_SQL_KEY] === $query1) {

                // log entry for first query
                self::assertEquals([], $log_entry[DBConnector::LOG_ENTRY_BIND_PARAMS_KEY]);

            } else {

                // log entry for second query
                self::assertEquals($query2_params, $log_entry[DBConnector::LOG_ENTRY_BIND_PARAMS_KEY]);
            }
        }
        
        ////////////////////////////////////////////////////////////////////////
        // Call with only conection name
        ////////////////////////////////////////////////////////////////////////
        $log_entries_for_non_existent_connection_name = DBConnector::getQueryLog('non-existent-connection');
        self::assertEquals([], $log_entries_for_non_existent_connection_name);
        
        $log_entries_for_existent_connection_name = DBConnector::getQueryLog($db_connector->getConnectionName());
        self::assertEquals(
            $all_log_entries_after_second_query,
            $log_entries_for_existent_connection_name
        );
        
        ////////////////////////////////////////////////////////////////////////
        // Call with conection name and object
        ////////////////////////////////////////////////////////////////////////
        $log_entries_for_non_existent_connection_name_and_object = 
            DBConnector::getQueryLog('non-existent-connection', new \DateTime());
        self::assertEquals([], $log_entries_for_non_existent_connection_name_and_object);
        
        $log_entries_for_existent_connection_name_and_non_existent_object = 
            DBConnector::getQueryLog($db_connector->getConnectionName(), new \DateTime());
        self::assertEquals([], $log_entries_for_existent_connection_name_and_non_existent_object);
        
        $log_entries_for_non_existent_connection_name_and_existent_object = 
            DBConnector::getQueryLog('non-existent-connection', $db_connector);
        self::assertEquals([], $log_entries_for_non_existent_connection_name_and_existent_object);
        
        $log_entries_for_existent_connection_name_and_object = 
            DBConnector::getQueryLog($db_connector->getConnectionName(), $db_connector);
        self::assertEquals(
            $all_log_entries_after_second_query[$db_connector->getConnectionName()],
            $log_entries_for_existent_connection_name_and_object[$db_connector->getConnectionName()]
        );
        
        ////////////////////////////////////////////////////////////////////////
        // Create a second connection and execute 1 query with that connection
        ////////////////////////////////////////////////////////////////////////
        $sqliteFile = __DIR__.DIRECTORY_SEPARATOR .'DbFiles'.DIRECTORY_SEPARATOR .'blog2.sqlite';
        //create sqlite file & create $dsn connection for it
        $dsn = "sqlite:" . $sqliteFile;
        
        $pdo = new \PDO($dsn);
        $sql = <<<EOF
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                full_name VARCHAR,
                description TEXT
            );
EOF;
        // Execute the query
        $pdo->exec($sql);
        
        $model2 = new \LeanOrm\Model(
            $dsn, static::$username ?? "", static::$password ?? "", [],
            'id', 'users'
        );
        
        $query1_conn2 = 'Select * from users';
        $db_connector2 = $model2->getDbConnector();
        $db_connector2->runQuery($query1_conn2);
        // logging is disabled by default for $db_connector2
        self::assertEquals([], DBConnector::getQueryLog($db_connector2->getConnectionName()));
        self::assertArrayHasKey($db_connector->getConnectionName(), DBConnector::getQueryLog(null));
        
        // enable query logging on $db_connector2
        $db_connector2->enableQueryLogging();
        $db_connector2->runQuery($query1_conn2);
        
        // The query log for all connections should now
        // contain second connection
        self::assertArrayHasAllKeys(
            DBConnector::getQueryLog(null), 
            [
                $db_connector->getConnectionName(),
                $db_connector2->getConnectionName()
            ]
        );
        
        DBConnector::getQueryLog($db_connector2->getConnectionName(), $db_connector2);
        
        self::assertEquals(
            DBConnector::getQueryLog(null)[$db_connector2->getConnectionName()],
            DBConnector::getQueryLog($db_connector2->getConnectionName())[$db_connector2->getConnectionName()]
        );
        
        self::assertEquals(
            DBConnector::getQueryLog(null)[$db_connector2->getConnectionName()],
            DBConnector::getQueryLog($db_connector2->getConnectionName(), $db_connector2)[$db_connector2->getConnectionName()]
        );

        $query_log_for_second_connection = DBConnector::getQueryLog($db_connector2->getConnectionName(), $db_connector2);
        self::assertCount(1, $query_log_for_second_connection[$db_connector2->getConnectionName()]);
        self::assertArrayHasKey($db_connector2::class, $query_log_for_second_connection[$db_connector2->getConnectionName()]);
        self::assertCount(1, $query_log_for_second_connection[$db_connector2->getConnectionName()][$db_connector2::class]);
        $first_and_only_log_entry =
            \array_first($query_log_for_second_connection[$db_connector2->getConnectionName()][$db_connector2::class]);
        
        self::assertArrayHasAllKeys($first_and_only_log_entry, $log_entry_keys_to_check);
        self::assertEquals($query1_conn2, $first_and_only_log_entry[DBConnector::LOG_ENTRY_SQL_KEY]);
        
        // cleanup 
        \unlink($sqliteFile);
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
