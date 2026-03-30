<?php
declare(strict_types=1);

namespace LeanOrm;

use \Psr\Log\LoggerInterface;

/**
 * 
 * This class creates and manages one or more pdo connection(s) to one or more 
 * database servers. IT IS A STRIPPED DOWN VERSION OF IDIORM.
 * 
 * It also provides convenience methods that aggregate common db operations.
 * E.g. preparing statements, binding parameters to a statement and then 
 *      executing the statement in one single method. 
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2026, Rotexsoft
 * 
 * BSD Licensed.
 *
 * Copyright (c) 2010, Jamie Matthews
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @psalm-suppress ClassMustBeFinal
 */
class DBConnector {

    // ----------------------- //
    // --- CLASS CONSTANTS --- //
    // ----------------------- //
    /**
     * @var string
     */
    final public const DEFAULT_CONNECTION = 'default';

    /**
     * @var int
     */
    final public const NANO_SECOND_TO_SECOND_DIVISOR = 1_000_000_000;
    
    final public const CONFIG_KEY_USERNAME = 'username';
    
    final public const CONFIG_KEY_PASSWORD = 'password';
    
    final public const CONFIG_KEY_ERR_MODE = 'error_mode';
    
    final public const CONFIG_KEY_DRIVER_OPTS = 'driver_options';
    
    final public const CONFIG_KEY_CONNECTION_STR = 'connection_string';
    
    final public const LOG_ENTRY_SQL_KEY = 'sql';
    final public const LOG_ENTRY_BIND_PARAMS_KEY = 'bind_params';
    final public const LOG_ENTRY_DATE_EXECUTED_KEY = 'date_executed';
    final public const LOG_ENTRY_CALL_STACK_KEY = 'call_stack';
    final public const LOG_ENTRY_CALLING_OBJECT_HASH = 'calling_object';
    final public const LOG_ENTRY_EXEC_TIME_KEY = 'query_execution_time_in_seconds';
    

////////////////////////////////////////////////////////////////////////////////        
//////////// -------------------------------- //////////////////////////////////
//////////// --- CLASS PROPERTIES TO KEEP --- //////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
    // Class configuration
    protected static array $default_config = [
        self::CONFIG_KEY_CONNECTION_STR => 'sqlite::memory:',
        self::CONFIG_KEY_ERR_MODE => \PDO::ERRMODE_EXCEPTION,
        self::CONFIG_KEY_USERNAME => null,
        self::CONFIG_KEY_PASSWORD => null,
        self::CONFIG_KEY_DRIVER_OPTS => null,
    ];

    // Map of configuration settings
    protected static array $config = [];

    // Map of database connections, instances of the PDO class
    /**
     * @var array<string, \PDO>
     */
    protected static array $db = [];
    
    /**
     * An array containing a log of all queries executed by all instances of this class
     * 
     * @var array
     */
    protected static $query_log = [];
    
    protected bool $can_log_queries = false;
    
    protected null|LoggerInterface $logger = null;
    
    //////////// ------------------------------------ //////////////////////////////
    //////////// --- END CLASS PROPERTIES TO KEEP --- //////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    
    // ---------------------- //
    // --- STATIC METHODS --- //
    // ---------------------- //
    
    /**
     * @param string $connection_name The name of a connection 
     *                                (registered via \LeanOrm\DBConnector::configure($key, $value, $connection_name) 
     *                                 or \LeanOrm\DBConnector::create($connection_name)) 
     *                                whose log entries are to be cleared. 
     *                                Null means clear log for all connections.
     *
     * @param null|object $object_to_match an object that triggered calling of methods of this class that executed queries in this class.
     *                                     Only queries associated with $object_to_match will be cleared from the queries logged for
     *                                     $connection_name. If $object_to_match is null then all logged queries for $connection_name
     *                                     will be cleared
     * @return void
     */
    public static function clearQueryLog(
        null|string $connection_name = null,
        null|object $object_to_match = null
    ): void {

        if($connection_name === null) {
            
            // clear all log entires across all connections
            static::$query_log = [];
            
        } else {

            if($object_to_match === null) {

                // clear all log entires across for specified connection
                static::$query_log[$connection_name] = [];

            } else { // $object_to_match !== null

                $object_class_name = $object_to_match::class;

                if(
                    isset(static::$query_log[$connection_name])
                    && \is_array(static::$query_log[$connection_name])
                    && \array_key_exists($object_class_name, static::$query_log[$connection_name])
                ) {

                    $object_hash = \spl_object_hash($object_to_match);

                    foreach (static::$query_log[$connection_name][$object_class_name] as $curr_key => $curr_entry) {

                        if($object_hash === $curr_entry[static::LOG_ENTRY_CALLING_OBJECT_HASH]) {

                            // clear all log entires across for specified connection and only for the specified object
                            unset(static::$query_log[$connection_name][$object_class_name][$curr_key]);

                        } // if($object_to_match === $curr_entry[static::LOG_ENTRY_CALLING_OBJECT_KEY])
                    } // foreach (static::$query_log[$connection_name][$object_class_name] as $curr_key => $curr_entry)

                } // if(isset(static::$query_log[$connection_name]) &&  \is_array(static::$query_log[$connection_name]) ...
            } // if($object_to_match === null) {} else {}
        } // if($connection_name === null){ ... } else { ... }
    }
    
    /**
     * The array returned if log is not empty based on supplied arguments to
     * this method looks like this (NOTE: an empty array is returned if log
     * is empty based on supplied arguments to this method):
     * 
     *   array{
     *           $a_connection_name => array {
     *
     *                   $calling_object::class => array {
     *
     *                           0 => array {
     *                                   DBConnector::LOG_ENTRY_SQL_KEY => $sql_query1_string,
     *                                   DBConnector::LOG_ENTRY_BIND_PARAMS_KEY => $bind_parameters_array_for_sql_query1,
     *                                   DBConnector::LOG_ENTRY_DATE_EXECUTED_KEY => \date('Y-m-d H:i:s'),
     *                                   DBConnector::LOG_ENTRY_EXEC_TIME_KEY => $total_execution_time_in_seconds_for_sql_query1,
     *                                   DBConnector::LOG_ENTRY_CALL_STACK_KEY => \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT),
     *                                   DBConnector::LOG_ENTRY_CALLING_OBJECT_HASH => \spl_object_hash($an_instance_of_calling_object),
     *                           },
     *                           ...,
     *                           ...,
     *                           (N-1) => array {
     *                                   DBConnector::LOG_ENTRY_SQL_KEY => $sql_queryN_string,
     *                                   DBConnector::LOG_ENTRY_BIND_PARAMS_KEY => $bind_parameters_array_for_sql_queryN,
     *                                   DBConnector::LOG_ENTRY_DATE_EXECUTED_KEY => \date('Y-m-d H:i:s'),
     *                                   DBConnector::LOG_ENTRY_EXEC_TIME_KEY => $total_execution_time_in_seconds_for_sql_queryN,
     *                                   DBConnector::LOG_ENTRY_CALL_STACK_KEY => \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT),
     *                                   DBConnector::LOG_ENTRY_CALLING_OBJECT_HASH => \spl_object_hash($an_instance_of_calling_object),
     *                           },
     *                   },
     *                   ...,
     *                   ...,
     *                   $last_calling_object::class => array {
     *
     *                           0 => array {
     *                                   DBConnector::LOG_ENTRY_SQL_KEY => $sql_query1_string,
     *                                   DBConnector::LOG_ENTRY_BIND_PARAMS_KEY => $bind_parameters_array_for_sql_query1,
     *                                   DBConnector::LOG_ENTRY_DATE_EXECUTED_KEY => \date('Y-m-d H:i:s'),
     *                                   DBConnector::LOG_ENTRY_EXEC_TIME_KEY => $total_execution_time_in_seconds_for_sql_query1,
     *                                   DBConnector::LOG_ENTRY_CALL_STACK_KEY => \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT),
     *                                   DBConnector::LOG_ENTRY_CALLING_OBJECT_HASH => \spl_object_hash($an_instance_of_last_calling_object),
     *                           },
     *                           ...,
     *                           ...,
     *                           (N-1) => array {
     *                                   DBConnector::LOG_ENTRY_SQL_KEY => $sql_queryN_string,
     *                                   DBConnector::LOG_ENTRY_BIND_PARAMS_KEY => $total_execution_time_in_seconds_for_sql_queryN,
     *                                   DBConnector::LOG_ENTRY_DATE_EXECUTED_KEY => \date('Y-m-d H:i:s'),
     *                                   DBConnector::LOG_ENTRY_EXEC_TIME_KEY => $total_execution_time_in_seconds_for_sql_queryN,
     *                                   DBConnector::LOG_ENTRY_CALL_STACK_KEY => \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT),
     *                                   DBConnector::LOG_ENTRY_CALLING_OBJECT_HASH => \spl_object_hash($instance_of_last_calling_object),
     *                           },
     *                   },
     *           },
     *           ...,
     *           ...,
     *           $another_connection_name => array {
     *                   ....,
     *                   ....,
     *           }
     *   }
     * 
     * 
     * @param string $connection_name The name of a connection 
     *                                (registered via \LeanOrm\DBConnector::configure($key, $value, $connection_name) 
     *                                 or \LeanOrm\DBConnector::create($connection_name)) 
     *                                whose log entries are to be retrieved. 
     *                                Null means retrieve log entries for all connections.
     *
     * @param null|object $object_to_match an object that triggered calling of methods of this class that executed queries in this class.
     *                                     Only queries associated with $object_to_match will be returned from the queries logged for
     *                                     $connection_name. If $object_to_match is null then all logged queries for $connection_name
     *                                     will be returned
     * @return void
     */
    public static function getQueryLog(
        null|string $connection_name = null,
        null|object $object_to_match = null
    ): array {
        
        $log_entries = [];

        if($connection_name === null) {
            
            $log_entries = static::$query_log;
            
        } else {
            
            if($object_to_match === null) {

                if(isset(static::$query_log[$connection_name])) {

                    $log_entries[$connection_name] = static::$query_log[$connection_name];
                }

            } else { // $object_to_match !== null

                $object_class_name = $object_to_match::class;

                if(
                    isset(static::$query_log[$connection_name])
                    && \is_array(static::$query_log[$connection_name])
                    && \array_key_exists($object_class_name, static::$query_log[$connection_name])
                ) {
                    $object_hash = \spl_object_hash($object_to_match);

                    foreach (static::$query_log[$connection_name][$object_class_name] as $curr_key => $curr_entry) {

                        if($object_hash === $curr_entry[static::LOG_ENTRY_CALLING_OBJECT_HASH]) {

                            if(!isset($log_entries[$connection_name])) {

                                $log_entries[$connection_name] = [$object_class_name=>[]];
                            }

                            $log_entries[$connection_name][$object_class_name][$curr_key] = $curr_entry;

                        } // if($object_to_match === $curr_entry[static::LOG_ENTRY_CALLING_OBJECT_KEY])
                    } // foreach (static::$query_log[$connection_name][$object_class_name] as $curr_key => $curr_entry)

                } // if(isset(static::$query_log[$connection_name]) &&  \is_array(static::$query_log[$connection_name]) ...
            } // if($object_to_match === null) {} else {}
        } // if($connection_name === null) { ... } else { ... }
        
        return $log_entries;
    }
    
    /**
     * Pass configuration settings to the class in the form of
     * key/value pairs. As a shortcut, if the second argument
     * is omitted and the key is a string, the setting is
     * assumed to be the DSN string used by PDO to connect
     * to the database (often, this will be the only configuration
     * required to use DBConnector). If you have more than one setting
     * you wish to configure, another shortcut is to pass an array
     * of settings (and omit the second argument).
     * 
     * @param array<int|string, mixed>|string $key_or_settings
     * @param mixed $value value we are setting
     * @param string $connection_name Which connection to use
     * 
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAssignment
     * @psalm-suppress PossiblyInvalidArgument
     */
    public static function configure(array|string $key_or_settings, mixed $value = null, string $connection_name = self::DEFAULT_CONNECTION): void {

        static::_initDbConfigWithDefaultVals($connection_name); //ensures at least default config is set

        if (is_array($key_or_settings)) {

            // Shortcut: If only one array argument is passed,
            // assume it's an array of configuration settings
            foreach ($key_or_settings as $conf_key => $conf_value) {

                static::configure($conf_key, $conf_value, $connection_name);
            }
        } else {

            if (is_null($value)) {

                // Shortcut: If only one string argument is passed, 
                // assume it's a connection string
                $value = $key_or_settings;
                $key_or_settings = static::CONFIG_KEY_CONNECTION_STR;
            }

            static::$config[$connection_name][$key_or_settings] = $value;
        }
    }

    /**
     * This is the factory method used to acquire instances of the class.
     * 
     * @param string $connection_name Which connection to use
     */
    //rename to factory
    public static function create(string $connection_name = self::DEFAULT_CONNECTION): static {

        static::_setupDb($connection_name);
        return new static($connection_name);
    }

    /**
     * Set up the database connection used by the class
     * @param string $connection_name Which connection to use
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArgument
     */
    protected static function _setupDb(string $connection_name = self::DEFAULT_CONNECTION): void {

        if (
            !array_key_exists($connection_name, static::$db) ||
            !(static::$db[$connection_name] instanceof \PDO)
        ) {

            static::_initDbConfigWithDefaultVals($connection_name);

            $db = new \PDO(
                static::$config[$connection_name][static::CONFIG_KEY_CONNECTION_STR],
                static::$config[$connection_name][static::CONFIG_KEY_USERNAME],
                static::$config[$connection_name][static::CONFIG_KEY_PASSWORD],
                static::$config[$connection_name][static::CONFIG_KEY_DRIVER_OPTS]
            );

            $db->setAttribute(\PDO::ATTR_ERRMODE, static::$config[$connection_name][static::CONFIG_KEY_ERR_MODE]);
            static::setDb($db, $connection_name);
        }
    }

   /**
    * Ensures configuration (multiple connections) is at least set to default.
    * @param string $connection_name Which connection to use
    */
    protected static function _initDbConfigWithDefaultVals(string $connection_name): void {

        if (!array_key_exists($connection_name, static::$config)) {

            static::$config[$connection_name] = static::$default_config;
        }
    }

    /**
     * Set the PDO object used by DBConnector to communicate with the database.
     * This is public in case the DBConnector should use a ready-instantiated
     * PDO object as its database connection. Accepts an optional string key
     * to identify the connection if multiple connections are used.
     * @param string $connection_name Which connection to use
     */
    public static function setDb(\PDO $db, string $connection_name = self::DEFAULT_CONNECTION): void {

        static::_initDbConfigWithDefaultVals($connection_name);
        static::$db[$connection_name] = $db;
    }

    /**
     * Returns the PDO instance used by the the DBConnector to communicate with
     * the database. This can be called if any low-level DB access is
     * required outside the class. If multiple connections are used,
     * accepts an optional key name for the connection.
     * @param string $connection_name Which connection to use
     */
    public static function getDb(string $connection_name = self::DEFAULT_CONNECTION): \PDO {

        static::_setupDb($connection_name); // required in case this is called before DBConnector is instantiated
        return static::$db[$connection_name];
    }

   /**
    * Internal helper method for executing statements.
    * 
    * @param array $parameters An array of parameters to be bound in to the query
    * @param bool $return_pdo_stmt_and_exec_time true to add the \PDOStatement object used by this function & time in seconds it took the query to execute to an array of results to be returned or false to return only the Response of \PDOStatement::execute()
    * @param string $connection_name Which connection to use
    * 
    * @return bool|array{query_result: mixed, pdo_statement: \PDOStatement, exec_time_in_seconds: float} Response of \PDOStatement::execute() if $return_pdo_statement === false or array(bool Response of \PDOStatement::execute(), \PDOStatement the PDOStatement object)
    * @deprecated since 7.0, use execute() instead. Will be removed in 8.0.
    */
    protected static function _execute(string $query, array $parameters = [], bool $return_pdo_stmt_and_exec_time=false, string $connection_name = self::DEFAULT_CONNECTION): bool|array {

        $statement = static::getDb($connection_name)->prepare($query);
        
        /** @psalm-suppress MixedAssignment */
        foreach ($parameters as $key => &$param) {

            if (is_null($param)) {

                $type = \PDO::PARAM_NULL;

            } else if (is_bool($param)) {

                $type = \PDO::PARAM_BOOL;

            } else {

                $type = is_int($param) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            }

            $statement->bindParam((is_int($key) ? ++$key : $key), $param, $type);
        }

        $start_time = \hrtime(true); // start timing
        $result = $statement->execute();
        $end_time = \hrtime(true); // stop timing
        $total_execution_time_in_seconds = (($end_time - $start_time) / static::NANO_SECOND_TO_SECOND_DIVISOR);

        if( $return_pdo_stmt_and_exec_time ) {

            $exec_result = $result;
            $result = ['query_result'=>$exec_result, 'pdo_statement'=>$statement, 'exec_time_in_seconds'=>$total_execution_time_in_seconds];
        }

        return $result;
    }

    // ------------------------ //
    // --- INSTANCE METHODS --- //
    // ------------------------ //

    /**
     * "Private" constructor; shouldn't be called directly.
     * Use the DBConnector::create factory method instead.
     */
    protected function __construct(
        // --------------------------- //
        // --- INSTANCE PROPERTIES --- //
        // --------------------------- //
        // Key name of the connection in static::$db used by this instance
        protected string $connection_name = self::DEFAULT_CONNECTION
    ) {
        static::_initDbConfigWithDefaultVals($connection_name);
    }

    /**
     * Get connection name for current instance of this class.
     */
    public function getConnectionName(): string {

        return $this->connection_name;
    }
    
    public function canLogQueries(): bool {
        
        return $this->can_log_queries;
    }
    
    /** @psalm-suppress PossiblyUnusedMethod */
    public function enableQueryLogging(): static {

        $this->can_log_queries = true;
        return $this;
    }
    
    /** @psalm-suppress PossiblyUnusedMethod */
    public function disableQueryLogging(): static {

        $this->can_log_queries = false;
        return $this;
    }
    

    /** @psalm-suppress PossiblyUnusedMethod */
    public function setLogger(?LoggerInterface $logger): static {
        
        $this->logger = $logger;
        return $this;
    }
    
    public function getLogger(): ?LoggerInterface { return $this->logger; }

    /**
     * Executes a raw query as a wrapper for PDOStatement::execute.
     * 
     * @param string $query The raw SQL query
     * @param array  $parameters Optional bound parameters
     * @param bool $return_pdo_stmt_and_exec_time true to add the \PDOStatement object used by this function & time in seconds it took the query to execute to an array of results to be returned or false to return only the Response of \PDOStatement::execute()
     * 
     * @return bool|array{query_result: mixed, pdo_statement: \PDOStatement, exec_time_in_seconds: float} bool Response of \PDOStatement::execute() if $return_pdo_statement === false or array(bool Response of \PDOStatement::execute(), \PDOStatement the PDOStatement object)
     * @deprecated since 7.0, use runQuery() instead. Will be removed in 8.0.
     */
    public function executeQuery(string $query, array $parameters=[], bool $return_pdo_stmt_and_exec_time=false): bool|array {

        return static::_execute($query, $parameters, $return_pdo_stmt_and_exec_time, $this->connection_name);
    }

   /**
    * Internal helper method for executing statements.
    * 
    * @param string $query Sql query to execute
    * @param  array $parameters An array of parameters to be bound in to the query
    * @param string $connection_name Which connection to use
    * @param null|object $calling_object object that called a method which triggered
    *                                    the calling of this method. A hash of this 
    *                                    object is added to each query log entry. 
    *                                    If null, it will be set to the instance
    *                                    of this class this being method is being
    *                                    called with
    */
    protected function execute(
        string $query,
        array $parameters = [],
        string $connection_name = self::DEFAULT_CONNECTION,
        ?object $calling_object = null
    ): DBExceuteQueryResult {

        if($calling_object === null) { $calling_object = $this; }
        
        $statement = static::getDb($connection_name)->prepare($query);
        
        /** @psalm-suppress MixedAssignment */
        foreach ($parameters as $key => &$param) {

            if (is_null($param)) {

                $type = \PDO::PARAM_NULL;

            } else if (is_bool($param)) {

                $type = \PDO::PARAM_BOOL;

            } else {

                $type = is_int($param) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            }

            $statement->bindParam((is_int($key) ? ++$key : $key), $param, $type);
        }
        
        $start_time = \hrtime(true); // start timing
        $result = $statement->execute();
        $end_time = \hrtime(true); // stop timing
        $total_execution_time_in_seconds = (($end_time - $start_time) / static::NANO_SECOND_TO_SECOND_DIVISOR);

        if($this->canLogQueries()) {
            
            $call_trace = \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
            
            if(!array_key_exists($connection_name, static::$query_log)) {
                
                static::$query_log[$connection_name] = [];
            }
            
            if(!array_key_exists($calling_object::class, static::$query_log[$connection_name])) {
                
                static::$query_log[$connection_name][$calling_object::class] = [];
            }
            
            $log_entry = [
                static::LOG_ENTRY_SQL_KEY => $query,
                static::LOG_ENTRY_BIND_PARAMS_KEY => $parameters,
                static::LOG_ENTRY_DATE_EXECUTED_KEY => \date('Y-m-d H:i:s'),
                static::LOG_ENTRY_EXEC_TIME_KEY => $total_execution_time_in_seconds,
                static::LOG_ENTRY_CALL_STACK_KEY => $call_trace,
                static::LOG_ENTRY_CALLING_OBJECT_HASH => \spl_object_hash($calling_object),
            ];
            static::$query_log[$connection_name][$calling_object::class][] = $log_entry;
            
            if($this->getLogger() instanceof \Psr\Log\LoggerInterface) {

                $this->getLogger()->info(
                    PHP_EOL . PHP_EOL
                    . '<<<=============================================>>>' . PHP_EOL
                    . 'SQL:' . PHP_EOL . "{$query}" . PHP_EOL . PHP_EOL
                    . 'BIND PARAMS:' . PHP_EOL . var_export($parameters, true)
                    . PHP_EOL . "Call Backtrace: "  . var_export($call_trace, true)
                    . '[[[=============================================]]]' . PHP_EOL
                    . PHP_EOL . PHP_EOL . PHP_EOL
                );
            }
        }
        
        return new DBExceuteQueryResult(
            pdo_statement: ($statement === false) ? null : $statement,
            pdo_statement_execute_result: $result,
            query_execution_time_in_seconds: $total_execution_time_in_seconds
        );
    }
    
    /**
     * Executes a raw query as a wrapper for PDOStatement::execute.
     * 
     * @param string $query  A SQL query that's valid for \PDO->prepare(..) usage
     * @param array  $parameters Optional bound parameters
     * @param null|object $calling_object object that called this method
     */
    public function runQuery(string $query, array $parameters=[], ?object $calling_object=null): DBExceuteQueryResult {

        return $this->execute($query, $parameters, $this->connection_name, $calling_object);
    }
    
    /**
     * Runs a query that is supposed to return a row of data from a database table
     * 
     * @param string $select_query A SQL Select query that's valid for \PDO->prepare(..) usage
     * @param array  $parameters Parameters that can be bound to $select_query via \PDOStatement->bindParam(..)
     * @param null|object $calling_object object that called this method
     * 
     * @return mixed result of the query or false on failure or if there are no rows
     */
    public function dbFetchOne(string $select_query, array $parameters = [], ?object $calling_object=null): mixed {

        $result = $this->execute($select_query, $parameters, $this->connection_name, $calling_object);

        return ($result->pdo_statement === null) ? null : $result->pdo_statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Runs a query that is supposed to return rows of data from a database table
     * 
     * @param string $select_query A SQL Select query that's valid for \PDO->prepare(..) usage
     * @param array  $parameters Parameters that can be bound to $select_query via \PDOStatement->bindParam(..)
     * @param null|object $calling_object object that called this method
     * 
     * @return mixed[]
     */
    public function dbFetchAll(string $select_query, array $parameters = [], ?object $calling_object=null): array {

        $result = $this->execute($select_query, $parameters, $this->connection_name, $calling_object);

        return ($result->pdo_statement === null) 
                    ? [] : $result->pdo_statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Runs a query that is supposed to return an array of values from a column in a database table
     * 
     * @param string $select_query A SQL Select query that's valid for \PDO->prepare(..) usage
     * @param array  $parameters Parameters that can be bound to $select_query via \PDOStatement->bindParam(..)
     * @param null|object $calling_object object that called this method
     * 
     * @return mixed[]
     */
    public function dbFetchCol(string $select_query, array $parameters = [], ?object $calling_object=null): array {

        $result = $this->execute($select_query, $parameters, $this->connection_name, $calling_object);

        return ($result->pdo_statement === null) 
                    ? [] : $result->pdo_statement->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * Runs a query that is supposed to return an associative array of data from a database table
     * where the keys are the values from the first database column in the select query
     * and the values are values from the second database column in the select query.
     * 
     * For example:
     * 
     * $key_value_pairs = $this->dbFetchPairs(
     *  'Select col_1, col_2 from a_table'
     * );
     * 
     * $key_value_pairs2 = $this->dbFetchPairs(
     *   'Select col_1, col_2 from a_table where col_3 > :min_val',
     *   ['min_val' => 3]
     * );
     *  
     * 
     * @param string $select_query A SQL Select query that's valid for \PDO->prepare(..) usage
     * @param array  $parameters Parameters that can be bound to $select_query via \PDOStatement->bindParam(..)
     * @param null|object $calling_object object that called this method
     * 
     * @return array<int|string, mixed>
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress MixedArrayAccess
     */
    public function dbFetchPairs(string $select_query, array $parameters = [], ?object $calling_object=null): array {

        $result = $this->execute($select_query, $parameters, $this->connection_name, $calling_object);
        $data = [];
        
        if($result->pdo_statement !== null) {

            while ($row = $result->pdo_statement->fetch(\PDO::FETCH_NUM)) {

                $data[$row[0]] = $row[1];
            }
        }

        return $data;
    }

    /**
     * Runs a select query and returns the result of the select query
     * 
     * @param string $select_query A SQL Select query that's valid for \PDO->prepare(..) usage
     * @param array  $parameters Parameters that can be bound to $select_query via \PDOStatement->bindParam(..)
     * @param null|object $calling_object object that called this method
     */
    public function dbFetchValue(string $select_query, array $parameters = [], ?object $calling_object=null): mixed {

        $result = $this->execute($select_query, $parameters, $this->connection_name, $calling_object);

        return ($result->pdo_statement === null) 
                    ? null : $result->pdo_statement->fetchColumn(0);
    }
}
