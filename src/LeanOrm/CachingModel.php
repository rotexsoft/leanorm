<?php
declare(strict_types=1);
namespace LeanOrm;

/**
 * A sub-class of \LeanOrm\Model that caches the results of some method
 * calls based on args supplied
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2022, Rotexsoft
 */
class CachingModel extends Model {

    protected static $cachedFetchedTableListFromDB = [];
    protected static $cachedFetchedTableColsFromDB = [];
    
    protected function fetchTableListFromDB(): array {
                
        if(array_key_exists($this->db_connector->getConnectionName(), static::$cachedFetchedTableListFromDB)) {
            
            return static::$cachedFetchedTableListFromDB[$this->db_connector->getConnectionName()];
        }
        
        static::$cachedFetchedTableListFromDB[$this->db_connector->getConnectionName()] = parent::fetchTableListFromDB();
        
        return static::$cachedFetchedTableListFromDB[$this->db_connector->getConnectionName()];
    }
    
    protected function fetchTableColsFromDB(string $table_name): array {
        
        if(array_key_exists($table_name, static::$cachedFetchedTableColsFromDB)) {
            
            return static::$cachedFetchedTableColsFromDB[$table_name];
        }
        
        static::$cachedFetchedTableColsFromDB[$table_name] = parent::fetchTableColsFromDB($table_name);
        
        return static::$cachedFetchedTableColsFromDB[$table_name];
    }
}
