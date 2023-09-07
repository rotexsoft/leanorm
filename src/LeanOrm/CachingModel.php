<?php
declare(strict_types=1);
namespace LeanOrm;

/**
 * A sub-class of \LeanOrm\Model that caches the results of some method
 * calls based on args supplied
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2023, Rotexsoft
 * 
 * @psalm-suppress UnusedClass
 */
class CachingModel extends Model {

    protected static array $cachedFetchedTableListFromDB = [];

    protected static array $cachedFetchedTableColsFromDB = [];

    /**
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    protected function fetchTableListFromDB(): array {
        
        if(array_key_exists($this->db_connector->getConnectionName(), static::$cachedFetchedTableListFromDB)) {

            return static::$cachedFetchedTableListFromDB[$this->db_connector->getConnectionName()];
        }

        static::$cachedFetchedTableListFromDB[$this->db_connector->getConnectionName()] = parent::fetchTableListFromDB();

        return static::$cachedFetchedTableListFromDB[$this->db_connector->getConnectionName()];
    }

    /**
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArrayAssignment
     */
    protected function fetchTableColsFromDB(string $table_name): array {
        
        if(!array_key_exists($this->db_connector->getConnectionName(), static::$cachedFetchedTableColsFromDB)) {

            static::$cachedFetchedTableColsFromDB[$this->db_connector->getConnectionName()] = [];
        }

        if(array_key_exists($table_name, static::$cachedFetchedTableColsFromDB[$this->db_connector->getConnectionName()])) {

            return static::$cachedFetchedTableColsFromDB[$this->db_connector->getConnectionName()][$table_name];
        }

        static::$cachedFetchedTableColsFromDB[$this->db_connector->getConnectionName()][$table_name] = parent::fetchTableColsFromDB($table_name);

        return static::$cachedFetchedTableColsFromDB[$this->db_connector->getConnectionName()][$table_name];
    }
}
