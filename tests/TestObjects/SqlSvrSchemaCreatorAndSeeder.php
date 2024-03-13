<?php
use Atlas\Pdo\Connection;

/**
 * Description of SqlSvrSchemaCreatorAndSeeder
 *
 * @author rotimi
 */
class SqlSvrSchemaCreatorAndSeeder implements SchemaCreatorAndSeederInterface {
    
    protected Connection $connection;

    public function __construct(Connection $conn) {
        
        $this->connection = $conn;
    }

    public function createTables(): bool {
        return true;
    }

    public function populateTables(): bool {
        return true;
    }

    public function createSchema(): bool {
        return true;
    }
}
