<?php
use Atlas\Pdo\Connection;

/**
 * This class creates & populates with data the db tables needed for testing this package
 */
class TestSchemaCreatorAndSeeder {
    
    protected ?Connection $connection;
    protected SchemaCreatorAndSeederInterface $sqlExecutor;

    public function __construct(Connection $conn=null) {
        
        $this->connection = $conn;
        
        if($this->connection === null) {
            
            $this->connection = Connection::new('sqlite::memory:');
        }
        
        $db = $this->connection->getPdo();
        
        if ( str_contains ($db->getAttribute(\PDO::ATTR_DRIVER_NAME), 'mysql') ) {
            
            // mysql
            $this->sqlExecutor = new MysqlSchemaCreatorAndSeeder($this->connection);
            
        } elseif ( str_contains ($db->getAttribute(\PDO::ATTR_DRIVER_NAME), 'sqlsrv') ) {
            
            //sql server
            $this->sqlExecutor = new SqlSvrSchemaCreatorAndSeeder($this->connection);
            
        } elseif ( str_contains ($db->getAttribute(\PDO::ATTR_DRIVER_NAME), 'pgsql') ) {
            
            //pgsql
            $this->sqlExecutor = new PostgreSQLSchemaCreatorAndSeeder($this->connection);
            
        } else {
            
            //sqlite
            $this->sqlExecutor = new SqliteSchemaCreatorAndSeeder($this->connection);
        }
    }
    
    public function createTables(): bool {
        
        return $this->sqlExecutor->createTables();
    }

    public function populateTables(): bool {
        
        return $this->sqlExecutor->populateTables();
    }
}
