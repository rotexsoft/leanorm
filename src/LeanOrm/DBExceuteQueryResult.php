<?php
declare(strict_types=1);

namespace LeanOrm;

/**
 * A Value object representing the return value of DBConnector->executeQuery(...)
 *
 * @author rotimi
 */
class DBExceuteQueryResult {

    public function __construct( 
        public readonly null|\PDOStatement $pdo_statement,
        public readonly bool $pdo_statement_execute_result, // result of PDOStatement->execute()
        public readonly float $query_execution_time_in_seconds,
    ) {}
}
