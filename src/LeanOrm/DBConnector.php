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
 * @copyright (c) 2022, Rotexsoft
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
    const DEFAULT_CONNECTION = 'default';

////////////////////////////////////////////////////////////////////////////////        
//////////// -------------------------------- //////////////////////////////////
//////////// --- CLASS PROPERTIES TO KEEP --- //////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
    // Class configuration
    protected static $_default_config = [
        'connection_string' => 'sqlite::memory:',
        'error_mode' => \PDO::ERRMODE_EXCEPTION,
        'username' => null,
        'password' => null,
        'driver_options' => null,
    ];

    // Map of configuration settings
    protected static $_config = [];

    // Map of database connections, instances of the PDO class
    protected static $_db = [];

    // --------------------------- //
    // --- INSTANCE PROPERTIES --- //
    // --------------------------- //

    // Key name of the connections in static::$_db used by this instance
    protected $_connection_name;

////////////////////////////////////////////////////////////////////////////////        
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
     * @param string $key
     * @param mixed $value
     * @param string $connection_name Which connection to use
     */
    public static function configure($key, $value = null, $connection_name = self::DEFAULT_CONNECTION) {
        
        static::_initDbConfigWithDefaultVals($connection_name); //ensures at least default config is set

        if (is_array($key)) {
            
            // Shortcut: If only one array argument is passed,
            // assume it's an array of configuration settings
            foreach ($key as $conf_key => $conf_value) {
                
                static::configure($conf_key, $conf_value, $connection_name);
            }
        } else {
            
            if (is_null($value)) {
                
                // Shortcut: If only one string argument is passed, 
                // assume it's a connection string
                $value = $key;
                $key = 'connection_string';
            }
            
            static::$_config[$connection_name][$key] = $value;
        }
    }

    /**
     * Retrieve configuration options by key, or as whole array.
     * @param string $key
     * @param string $conn_name Which connection to use or null for all connections
     */
    public static function getConfig($key = null, $conn_name = self::DEFAULT_CONNECTION) {
        
        if( $key && is_null($conn_name) ) {
            
            //get key's value for each connection
            $conn_names = array_keys(static::$_config);
            $val_of_key_4_each_conn_name = array_column(static::$_config, $key);
            
            return array_combine($conn_names, $val_of_key_4_each_conn_name);
            
        } else if ( $key && !is_null($conn_name) && strlen($conn_name) > 0 ) {
            
            //get key's value for the specified connection
            return static::$_config[$conn_name][$key];
            
        } else if( !$key && is_null($conn_name) ) {
            
            //get all config values for all connections
            return static::$_config;
            
        } else {
            
            //get all config values for the specified connection
            return static::$_config[$conn_name];
        }
    }
    
    /**
     * 
     * DBConnector::resetAllStaticPropertiesExceptDefaultConfig() returns the values of all properties
     * DBConnector::resetAllStaticPropertiesExceptDefaultConfig('_db') will return only the value of DBConnector::$_db
     * 
     * @param string $prop_name name of the property (eg. 'db' or '_db') whose value is to be retrieved
     * 
     * @return mixed the value of the property specified or an array of all properties if $prop_name is empty or not a name of any of the properties.
     */
    public static function getAllStaticPropertiesExceptDefaultConfig($prop_name='') {
        
        switch ($prop_name) {
            
            case '_config':
            case 'config':
                
                // Map of configuration settings
                return static::$_config;
            
            case '_db':
            case 'db':
                
                // Map of database connections, instances of the PDO class
                return static::$_db;
            
            default:
                ///////////////////////////
                // Return all properties //
                ///////////////////////////
                
                // Map of configuration settings
                return [
                    '$_config' => static::$_config,

                    // Map of database connections, instances of the PDO class
                    '$_db' => static::$_db,
                ];
        }
    }
    
    /**
     * 
     * DBConnector::resetAllStaticPropertiesExceptDefaultConfig() resets all properties
     * DBConnector::resetAllStaticPropertiesExceptDefaultConfig('_db') will reset only DBConnector::$_db
     * 
     * @param string $prop_name name of the property (eg. 'db' or '_db') whose value is to be reset. 
     * 
     */
    public static function resetAllStaticPropertiesExceptDefaultConfig($prop_name='') {
        
        switch ($prop_name) {
            
            case '_config':
            case 'config':
                
                // Map of configuration settings
                static::$_config = [];
                break;
            
            case '_db':
            case 'db':
                
                // Map of database connections, instances of the PDO class
                static::$_db = [];
                break;
            
            default:
                //////////////////////////
                // Reset all properties //
                //////////////////////////
                
                // Map of configuration settings
                static::$_config = [];
                
                // Map of database connections, instances of the PDO class
                static::$_db = [];
                break;
        }
    }

    /**
     * This is the factory method used to acquire instances of the class.
     * 
     * @param string $connection_name Which connection to use
     * @return DBConnector
     */
    //rename to factory
    public static function create($connection_name = self::DEFAULT_CONNECTION) {
        
        static::_setupDb($connection_name);
        return new self($connection_name);
    }

    /**
     * Set up the database connection used by the class
     * @param string $connection_name Which connection to use
     */
    protected static function _setupDb($connection_name = self::DEFAULT_CONNECTION) {

        if (!array_key_exists($connection_name, static::$_db) ||
            !is_object(static::$_db[$connection_name])) {

            static::_initDbConfigWithDefaultVals($connection_name);

            $db = new \PDO(
                static::$_config[$connection_name]['connection_string'],
                static::$_config[$connection_name]['username'],
                static::$_config[$connection_name]['password'],
                static::$_config[$connection_name]['driver_options']
            );

            $db->setAttribute(\PDO::ATTR_ERRMODE, static::$_config[$connection_name]['error_mode']);
            static::setDb($db, $connection_name);
        }
    }

   /**
    * Ensures configuration (multiple connections) is at least set to default.
    * @param string $connection_name Which connection to use
    */
    protected static function _initDbConfigWithDefaultVals($connection_name) {
        
        if (!array_key_exists($connection_name, static::$_config)) {
            
            static::$_config[$connection_name] = static::$_default_config;
        }
    }

    /**
     * Set the PDO object used by DBConnector to communicate with the database.
     * This is public in case the DBConnector should use a ready-instantiated
     * PDO object as its database connection. Accepts an optional string key
     * to identify the connection if multiple connections are used.
     * @param PDO $db
     * @param string $connection_name Which connection to use
     */
    public static function setDb($db, $connection_name = self::DEFAULT_CONNECTION) {
        
        static::_initDbConfigWithDefaultVals($connection_name);
        static::$_db[$connection_name] = $db;
    }

    /**
     * Returns the PDO instance used by the the DBConnector to communicate with
     * the database. This can be called if any low-level DB access is
     * required outside the class. If multiple connections are used,
     * accepts an optional key name for the connection.
     * @param string $connection_name Which connection to use
     * @return PDO
     */
    public static function getDb($connection_name = self::DEFAULT_CONNECTION) {
        
        static::_setupDb($connection_name); // required in case this is called before DBConnector is instantiated
        return static::$_db[$connection_name];
    }

    /**
     * Executes a raw query as a wrapper for PDOStatement::execute.
     * 
     * @param string $query The raw SQL query
     * @param array  $parameters Optional bound parameters
     * @param bool $return_pdo_statement true to add the \PDOStatement object used by this function to an array of results to be returned or false to return only the Response of \PDOStatement::execute()
     * 
     * @return bool|array bool Response of \PDOStatement::execute() if $return_pdo_statement === false or array(bool Response of \PDOStatement::execute(), \PDOStatement the PDOStatement object)
     */
    public function executeQuery( $query, $parameters=[], $return_pdo_statement=false ) {
        
        return static::_execute($query, $parameters, $return_pdo_statement, $this->_connection_name);
    }

   /**
    * Internal helper method for executing statments. Logs queries, and
    * stores statement object in ::_last_statment, accessible publicly
    * through ::get_last_statement()
    * @param string $query
    * @param array $parameters An array of parameters to be bound in to the query
    * @param bool $return_pdo_statement true to add the \PDOStatement object used by this function to an array of results to be returned or false to return only the Response of \PDOStatement::execute()
    * @param string $connection_name Which connection to use
    * 
    * @return bool|array bool Response of \PDOStatement::execute() if $return_pdo_statement === false or array(bool Response of \PDOStatement::execute(), \PDOStatement the PDOStatement object)
    */
    protected static function _execute($query, $parameters = [], $return_pdo_statement=false, $connection_name = self::DEFAULT_CONNECTION) {
        
        $statement = static::getDb($connection_name)->prepare($query);
        $time = microtime(true);

        foreach ($parameters as $key => &$param) {
            
            if (is_null($param)) {
                
                $type = \PDO::PARAM_NULL;
                
            } else if (is_bool($param)) {
                
                $type = \PDO::PARAM_BOOL;
                
            } else if (is_int($param)) {
                
                $type = \PDO::PARAM_INT;
                
            } else {

                $type = \PDO::PARAM_STR;
            }

            $statement->bindParam((is_int($key) ? ++$key : $key), $param, $type);
        }

        $result = $statement->execute();

        if( $return_pdo_statement ) {
            
            $exec_result = $result;
            $result = [$exec_result, $statement];
        }

        return $result;
    }
    
    /**
     * Get a list of the available connection names
     * @return array
     */
    public static function getConnectionNames() {
        
        return array_keys(static::$_db);
    }

    // ------------------------ //
    // --- INSTANCE METHODS --- //
    // ------------------------ //

    /**
     * "Private" constructor; shouldn't be called directly.
     * Use the DBConnector::create factory method instead.
     */
    protected function __construct($connection_name = self::DEFAULT_CONNECTION) {

        $this->_connection_name = $connection_name;
        static::_initDbConfigWithDefaultVals($connection_name);
    }

    /**
     * Get connection name for current instance of this class.
     * @return array
     */
    public function getConnectionName() {
        
        return $this->_connection_name;
    }
    
    /**
     * Tell the DBConnector that you are expecting a single result
     * back from your query, and execute it. Will return
     * a single instance of the DBConnector class, or false if no
     * rows were returned.
     * As a shortcut, you may supply an ID as a parameter
     * to this method. This will perform a primary key
     * lookup on the table.
     */
    public function dbFetchOne( $select_query,  $parameters = [] ) {

       $bool_and_statement = static::_execute($select_query, $parameters, true, $this->_connection_name);
        
        $statement = array_pop($bool_and_statement);
            
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Perform a raw query. The query can contain placeholders in
     * either named or question mark style. If placeholders are
     * used, the parameters should be an array of values which will
     * be bound to the placeholders in the query.
     */
    public function dbFetchAll($select_query, $parameters = []) {

        $bool_and_statement = static::_execute($select_query, $parameters, true, $this->_connection_name);
        
        $statement = array_pop($bool_and_statement);
            
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Perform a raw query. The query can contain placeholders in
     * either named or question mark style. If placeholders are
     * used, the parameters should be an array of values which will
     * be bound to the placeholders in the query.
     */
    public function dbFetchCol($select_query, $parameters = []) {

        $bool_and_statement = static::_execute($select_query, $parameters, true, $this->_connection_name);
        
        $statement = array_pop($bool_and_statement);
            
        return $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * Perform a raw query. The query can contain placeholders in
     * either named or question mark style. If placeholders are
     * used, the parameters should be an array of values which will
     * be bound to the placeholders in the query.
     */
    public function dbFetchPairs($select_query, $parameters = []) {

        $bool_and_statement = static::_execute($select_query, $parameters, true, $this->_connection_name);
        
        $statement = array_pop($bool_and_statement);
        
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
    public function dbFetchValue($select_query, $parameters = []) {

        $bool_and_statement = static::_execute($select_query, $parameters, true, $this->_connection_name);
        
        $statement = array_pop($bool_and_statement);
            
        return $statement->fetchColumn(0);
    }
}
