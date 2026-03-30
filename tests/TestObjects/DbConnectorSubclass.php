<?php
/**
 * Description of DbConnectorSubclass
 *
 * @author rotimi
 */
class DbConnectorSubclass extends \LeanOrm\DBConnector {
    
    public static function oldExecutePublic($query, array $parameters = [], bool $return_pdo_stmt_and_exec_time = false, string $connection_name = self::DEFAULT_CONNECTION) {
        
        return parent::_execute($query, $parameters, $return_pdo_stmt_and_exec_time, $connection_name);
    }
    
    public function executePublic(
        string $query,
        array $parameters = [],
        string $connection_name = self::DEFAULT_CONNECTION,
        ?object $calling_object = null
    ): \LeanOrm\DBExceuteQueryResult {
        
        return $this->execute($query, $parameters, $connection_name, $calling_object);
    }
    
    public static function initDbConfigWithDefaultValsPublic(string $connection_name): void {
        
        parent::_initDbConfigWithDefaultVals($connection_name);
    }
    
    public static function setupDbPublic(string $connection_name = self::DEFAULT_CONNECTION): void {
        
        parent::_setupDb($connection_name);
    }
    
    public static function getConfig() {
        
        return static::$config;
    }
    
    public static function getDefaultConfig() {
        
        return static::$default_config;
    }
    
    public static function getDbObj() {
        
        return static::$db;
    }
}
