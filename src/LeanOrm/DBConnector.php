<?php
declare(strict_types=1);
namespace LeanOrm;

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
 * @copyright (c) 2024, Rotexsoft
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

////////////////////////////////////////////////////////////////////////////////        
//////////// -------------------------------- //////////////////////////////////
//////////// --- CLASS PROPERTIES TO KEEP --- //////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
    // Class configuration
    protected static array $default_config = [
        'connection_string' => 'sqlite::memory:',
        'error_mode' => \PDO::ERRMODE_EXCEPTION,
        'username' => null,
        'password' => null,
        'driver_options' => null,
    ];

    // Map of configuration settings
    protected static array $config = [];

    // Map of database connections, instances of the PDO class
    /**
     * @var array<string, \PDO>
     */
    protected static array $db = [];
    
    //////////// ------------------------------------ //////////////////////////////
    //////////// --- END CLASS PROPERTIES TO KEEP --- //////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    
    // ---------------------- //
    // --- STATIC METHODS --- //
    // ---------------------- //
    
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
                $key_or_settings = 'connection_string';
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
    public static function create(string $connection_name = self::DEFAULT_CONNECTION): \LeanOrm\DBConnector {

        static::_setupDb($connection_name);
        return new self($connection_name);
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
                static::$config[$connection_name]['connection_string'],
                static::$config[$connection_name]['username'],
                static::$config[$connection_name]['password'],
                static::$config[$connection_name]['driver_options']
            );

            $db->setAttribute(\PDO::ATTR_ERRMODE, static::$config[$connection_name]['error_mode']);
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
     * Executes a raw query as a wrapper for PDOStatement::execute.
     * 
     * @param string $query The raw SQL query
     * @param array  $parameters Optional bound parameters
     * @param bool $return_pdo_stmt_and_exec_time true to add the \PDOStatement object used by this function & time in seconds it took the query to execute to an array of results to be returned or false to return only the Response of \PDOStatement::execute()
     * 
     * @return bool|array{query_result: mixed, pdo_statement: \PDOStatement, exec_time_in_seconds: float} bool Response of \PDOStatement::execute() if $return_pdo_statement === false or array(bool Response of \PDOStatement::execute(), \PDOStatement the PDOStatement object)
     */
    public function executeQuery(string $query, array $parameters=[], bool $return_pdo_stmt_and_exec_time=false): bool|array {

        return static::_execute($query, $parameters, $return_pdo_stmt_and_exec_time, $this->connection_name);
    }

   /**
    * Internal helper method for executing statments. Logs queries, and
    * stores statement object in ::_last_statment, accessible publicly
    * through ::get_last_statement()
    * @param array $parameters An array of parameters to be bound in to the query
    * @param bool $return_pdo_stmt_and_exec_time true to add the \PDOStatement object used by this function & time in seconds it took the query to execute to an array of results to be returned or false to return only the Response of \PDOStatement::execute()
    * @param string $connection_name Which connection to use
    * 
    * @return bool|array{query_result: mixed, pdo_statement: \PDOStatement, exec_time_in_seconds: float} Response of \PDOStatement::execute() if $return_pdo_statement === false or array(bool Response of \PDOStatement::execute(), \PDOStatement the PDOStatement object)
    * 
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
        $total_execution_time_in_seconds = (($end_time - $start_time) / self::NANO_SECOND_TO_SECOND_DIVISOR);

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

    /**
     * Tell the DBConnector that you are expecting a single result
     * back from your query, and execute it. Will return
     * a single instance of the DBConnector class, or false if no
     * rows were returned.
     * As a shortcut, you may supply an ID as a parameter
     * to this method. This will perform a primary key
     * lookup on the table.
     * 
     * @return mixed result of the query or false on failure or if there are no rows
     */
    public function dbFetchOne(string $select_query, array $parameters = [] ) {

        $result = static::_execute($select_query, $parameters, true, $this->connection_name);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $statement = $result['pdo_statement'];

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Perform a raw query. The query can contain placeholders in
     * either named or question mark style. If placeholders are
     * used, the parameters should be an array of values which will
     * be bound to the placeholders in the query.
     * 
     * @return mixed[]
     */
    public function dbFetchAll(string $select_query, array $parameters = []): array {

        $result = static::_execute($select_query, $parameters, true, $this->connection_name);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $statement = $result['pdo_statement'];

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Perform a raw query. The query can contain placeholders in
     * either named or question mark style. If placeholders are
     * used, the parameters should be an array of values which will
     * be bound to the placeholders in the query.
     * 
     * @return mixed[]
     */
    public function dbFetchCol(string $select_query, array $parameters = []): array {

        $result = static::_execute($select_query, $parameters, true, $this->connection_name);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $statement = $result['pdo_statement'];

        return $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * Perform a raw query. The query can contain placeholders in
     * either named or question mark style. If placeholders are
     * used, the parameters should be an array of values which will
     * be bound to the placeholders in the query.
     * 
     * @return array<int|string, mixed>
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress MixedArrayAccess
     */
    public function dbFetchPairs(string $select_query, array $parameters = []): array {

        $result = static::_execute($select_query, $parameters, true, $this->connection_name);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $statement = $result['pdo_statement'];
        $data = [];
        
        while ($row = $statement->fetch(\PDO::FETCH_NUM)) {
            
            $data[$row[0]] = $row[1];
        }

        return $data;
    }

    /**
     * Perform a raw query. The query can contain placeholders in
     * either named or question mark style. If placeholders are
     * used, the parameters should be an array of values which will
     * be bound to the placeholders in the query.
     */
    public function dbFetchValue(string $select_query, array $parameters = []): mixed {

        $result = static::_execute($select_query, $parameters, true, $this->connection_name);
        /** @psalm-suppress PossiblyInvalidArrayAccess */
        $statement = $result['pdo_statement'];

        return $statement->fetchColumn(0);
    }
}
