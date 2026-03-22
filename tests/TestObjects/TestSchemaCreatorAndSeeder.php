<?php
use Atlas\Pdo\Connection;

/**
 * This class creates & populates with data the db tables needed for testing this package
 */
class TestSchemaCreatorAndSeeder {
    
    protected ?Connection $connection;
    protected SchemaCreatorAndSeederInterface $sqlExecutor;

    public function __construct(?Connection $conn=null) {
        
        $this->connection = $conn;
        
        if($this->connection === null) {
            
            $this->connection = Connection::new('sqlite::memory:');
        }
        
        $db = $this->connection->getPdo();
        $pdoDriverName = strtolower((string)$db->getAttribute(\PDO::ATTR_DRIVER_NAME));
        
        if ( str_contains ($pdoDriverName, 'mysql') ) {
            
            // mysql
            $this->sqlExecutor = new MysqlSchemaCreatorAndSeeder($this->connection);
            
        } elseif ( str_contains ($pdoDriverName, 'sqlsrv') ) {
            
            //sql server
            $this->sqlExecutor = new SqlSvrSchemaCreatorAndSeeder($this->connection);
            
        } elseif ( str_contains ($pdoDriverName, 'pgsql') ) {
            
            //pgsql
            $this->sqlExecutor = new PostgreSQLSchemaCreatorAndSeeder($this->connection);
            
        } else {
            
            //sqlite
            $this->sqlExecutor = new SqliteSchemaCreatorAndSeeder($this->connection);
        }
    }
    
    public function createSchema(): bool {
        
        return $this->sqlExecutor->createSchema();
    }
    
    public function createTables(): bool {
        
        return $this->sqlExecutor->createTables();
    }

    public function populateTables(): bool {
        
        return $this->sqlExecutor->populateTables();
    }
}
