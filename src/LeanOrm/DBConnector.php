<?php
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
 * @copyright (c) 2015, Rotimi Adegbamigbe
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
    protected static $_default_config = array(
        'connection_string' => 'sqlite::memory:',
        'error_mode' => \PDO::ERRMODE_EXCEPTION,
        'username' => null,
        'password' => null,
        'driver_options' => null,
        'logging' => false,
        'logger' => null,
    );

    // Map of configuration settings
    protected static $_config = array();

    // Map of database connections, instances of the PDO class
    protected static $_db = array();

    // Last query run, only populated if logging is enabled
    protected static $_last_query;

    // Log of all queries run, mapped by connection key, only populated if logging is enabled
    protected static $_query_log = array();

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
            
            case '_last_query':
            case 'last_query':
                
                // Last query run, only populated if logging is enabled
                return static::$_last_query;
            
            case '_query_log':
            case 'query_log':
                
                // Log of all queries run, mapped by connection key, only populated if logging is enabled
                return static::$_query_log;
            
            default:
                ///////////////////////////
                // Return all properties //
                ///////////////////////////
                
                // Map of configuration settings
                return array (
                    '$_config' => static::$_config,

                    // Map of database connections, instances of the PDO class
                    '$_db' => static::$_db,

                    // Last query run, only populated if logging is enabled
                    '$_last_query' => static::$_last_query,

                    // Log of all queries run, mapped by connection key, only populated if logging is enabled
                    '$_query_log' => static::$_query_log,
                );
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
                static::$_config = array();
                break;
            
            case '_db':
            case 'db':
                
                // Map of database connections, instances of the PDO class
                static::$_db = array();
                break;
            
            case '_last_query':
            case 'last_query':
                
                // Last query run, only populated if logging is enabled
                static::$_last_query = '';
                break;
            
            case '_query_log':
            case 'query_log':
                
                // Log of all queries run, mapped by connection key, only populated if logging is enabled
                static::$_query_log = array();
                break;
            
            default:
                //////////////////////////
                // Reset all properties //
                //////////////////////////
                
                // Map of configuration settings
                static::$_config = array();
                
                // Map of database connections, instances of the PDO class
                static::$_db = array();
                
                // Last query run, only populated if logging is enabled
                static::$_last_query = '';
                
                // Log of all queries run, mapped by connection key, only populated if logging is enabled
                static::$_query_log = array();
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
     * @param string $connection_name Which connection to use
     * 
     * @return bool|array bool Response of \PDOStatement::execute() if $return_pdo_statement === false or array(bool Response of \PDOStatement::execute(), \PDOStatement the PDOStatement object)
     */
    public function executeQuery(
        $query, $parameters = array(), $return_pdo_statement=false, $connection_name = self::DEFAULT_CONNECTION
    ) {   
        return static::_execute($query, $parameters, $return_pdo_statement, $connection_name);
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
    protected static function _execute($query, $parameters = array(), $return_pdo_statement=false, $connection_name = self::DEFAULT_CONNECTION) {
        
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
            $result = array($exec_result, $statement);
        }
        
        if ( static::$_config[$connection_name]['logging'] ) {
            
            // Logging is enabled, log da query
            static::_logQuery($query, $parameters, $connection_name, (microtime(true)-$time));
        }

        return $result;
    }

    /**
     * Add a query to the internal query log. Only works if the
     * 'logging' config option is set to true.
     *
     * This works by manually binding the parameters to the query - the
     * query isn't executed like this (PDO normally passes the query and
     * parameters to the database which takes care of the binding) but
     * doing it this way makes the logged queries more readable.
     * @param string $query
     * @param array $parameters An array of parameters to be bound in to the query
     * @param string $connection_name Which connection to use
     * @param float $query_time Query time
     * @return bool
     */
    protected static function _logQuery( $query, $parameters, $connection_name, $query_time ) {
        
        // If logging is not enabled, do nothing
        if ( !static::$_config[$connection_name]['logging'] ) {
            
            return false;
        }
                
        if ( !isset( static::$_query_log[$connection_name] ) ) {
            
            static::$_query_log[$connection_name] = array();
        }
        
        static::$_last_query = array('unbound'=>$query, 'parameters'=>$parameters);
        
        $parameters_with_non_int_keys = array();//holds named parameters

        // Strip out any non-integer indexes from the parameters
        foreach($parameters as $key => $value) {
            
            if ( !is_int($key) ) {
                
                $parameters_with_non_int_keys[$key] = $value;
                unset($parameters[$key]);
            }
        }

        if ( count($parameters) > 0 && count($parameters_with_non_int_keys) <= 0 ) {
            
            //Deal with only question mark place holders
            
            // Escape the parameters
            $parameters = 
                array_map(array(static::getDb($connection_name), 'quote'), $parameters);

            // Avoid %format collision for vsprintf
            $query = str_replace("%", "%%", $query);

            // Replace placeholders in the query for vsprintf
            if( false !== strpos($query, "'") || false !== strpos($query, '"') ) {
                
                $query = StringHelper::strReplaceOutsideQuotes("?", "%s", $query);
                
            } else {
                
                $query = str_replace("?", "%s", $query);
            }

            // Replace the question marks in the query with the parameters
            $bound_query = vsprintf($query, $parameters);
            
        } else if( count($parameters_with_non_int_keys) > 0 && count($parameters) <= 0 ){
            
            //Deal with only named place holders
            
            // Escape the parameters
            $parameters_with_non_int_keys = 
                array_map(array(static::getDb($connection_name), 'quote'), $parameters_with_non_int_keys);

            // Avoid %format collision for vsprintf
            $query = str_replace("%", "%%", $query);
            
            $re_indexed_parameters_with_non_int_keys_for_vsprintf = array();
            
            foreach($parameters_with_non_int_keys as $key=>$value) {
                              
                $new_index = strpos($query, ":{$key}");
                
                if( $new_index !== false ){
                    
                    $re_indexed_parameters_with_non_int_keys_for_vsprintf[$new_index] = $value;
                }
                
                // Replace placeholders in the query for vsprintf
                if( false !== strpos($query, "'") || false !== strpos($query, '"') ) {

                    $query = StringHelper::strReplaceOutsideQuotes(":{$key}", "%s", $query);

                } else {

                    $query = str_replace(":{$key}", "%s", $query);
                }
            }            

            // Replace the named placeholders in the query with the parameters
            $bound_query = vsprintf($query, $re_indexed_parameters_with_non_int_keys_for_vsprintf);
            
        }else {
            //Either no parameters to bind to query (which is good) or there are 
            //mixed types of parameters (ie. named and question marked in the 
            //same query which will eventually lead to a PDO Exception).
            $bound_query = $query;
        }

        static::$_last_query['bound'] = $bound_query;
        static::$_query_log[$connection_name][] = static::$_last_query;

        if( is_callable( static::$_config[$connection_name]['logger'] ) ) {
            
            $logger = static::$_config[$connection_name]['logger'];
            $logger(
                    "Bound Query:". PHP_EOL. static::$_last_query['bound'], 
                    "Unbound Query:".PHP_EOL.static::$_last_query['unbound'], 
                    "Query Parameters:".PHP_EOL. var_export(static::$_last_query['parameters'], true), 
                    $query_time
                );
        }

        return true;
    }

    /**
     * Get the last query executed. Only works if the
     * 'logging' config option is set to true. Otherwise
     * this will return null. Returns last query from all connections if
     * no connection_name is specified
     * @param null|string $connection_name Which connection to use
     * @return string
     */
    public static function getLastQuery($connection_name = null) {
        
        if ($connection_name === null) {
            
            return static::$_last_query;
        }
        if (!isset(static::$_query_log[$connection_name])) {
            
            return '';
        }

        return end(static::$_query_log[$connection_name]);
    }

    /**
     * Get an array containing all the queries run on a
     * specified connection up to now.
     * Only works if the 'logging' config option is
     * set to true. Otherwise, returned array will be empty.
     * @param string $connection_name Which connection to use. Set it to null to get query logs for all connections.
     */
    public static function getQueryLog($connection_name = self::DEFAULT_CONNECTION) {
        
        if ( isset( static::$_query_log[$connection_name] ) ) {
            
            return static::$_query_log[$connection_name];
            
        } else if ( is_null($connection_name) ) {
            
            return static::$_query_log;
        }
        
        return array();
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
    public function dbFetchOne( $select_query,  $parameters = array() ) {

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
    public function dbFetchAll($select_query, $parameters = array()) {

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
    public function dbFetchCol($select_query, $parameters = array()) {

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
    public function dbFetchPairs($select_query, $parameters = array()) {

        $bool_and_statement = static::_execute($select_query, $parameters, true, $this->_connection_name);
        
        $statement = array_pop($bool_and_statement);
        
        $data = array();
        
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
    public function dbFetchValue($select_query, $parameters = array()) {

        $bool_and_statement = static::_execute($select_query, $parameters, true, $this->_connection_name);
        
        $statement = array_pop($bool_and_statement);
            
        return $statement->fetchColumn(0);
    }
}

/**
 * A class to handle str_replace operations that involve quoted strings
 * @example StringHelper::str_replace_outside_quotes('?', '%s', 'columnA = "Hello?" AND columnB = ?');
 * @example StringHelper::value('columnA = "Hello?" AND columnB = ?')->replace_outside_quotes('?', '%s');
 * @author Jeff Roberson <ridgerunner@fluxbb.org>
 * @author Simon Holywell <treffynnon@php.net>
 * @link http://stackoverflow.com/a/13370709/461813 StackOverflow answer
 */
class StringHelper {
    
    protected $subject;
    protected $search;
    protected $replace;

    /**
     * Get an easy to use instance of the class
     * @param string $subject
     * @return \self
     */
    public static function value($subject) {
        
        return new self($subject);
    }

    /**
     * Shortcut method: Replace all occurrences of the search string with the replacement
     * string where they appear outside quotes.
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function strReplaceOutsideQuotes($search, $replace, $subject) {
        
        return static::value($subject)->replaceOutsideQuotes($search, $replace);
    }

    /**
     * Set the base string object
     * @param string $subject
     */
    public function __construct($subject) {
        
        $this->subject = (string) $subject;
    }

    /**
     * Replace all occurrences of the search string with the replacement
     * string where they appear outside quotes
     * @param string $search
     * @param string $replace
     * @return string
     */
    public function replaceOutsideQuotes($search, $replace) {
        
        $this->search = $search;
        $this->replace = $replace;
        return $this->_strReplaceOutsideQuotes();
    }

    /**
     * Validate an input string and perform a replace on all ocurrences
     * of $this->search with $this->replace
     * @author Jeff Roberson <ridgerunner@fluxbb.org>
     * @link http://stackoverflow.com/a/13370709/461813 StackOverflow answer
     * @return string
     */
    protected function _strReplaceOutsideQuotes(){
        
        $re_valid = '/
            # Validate string having embedded quoted substrings.
            ^                           # Anchor to start of string.
            (?:                         # Zero or more string chunks.
              "[^"\\\\]*(?:\\\\.[^"\\\\]*)*"  # Either a double quoted chunk,
            | \'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'  # or a single quoted chunk,
            | [^\'"\\\\]+               # or an unquoted chunk (no escapes).
            )*                          # Zero or more string chunks.
            \z                          # Anchor to end of string.
            /sx';
        if (!preg_match($re_valid, $this->subject)) {
            throw new StringHelperException("Subject string is not valid in the replace_outside_quotes context.");
        }
        $re_parse = '/
            # Match one chunk of a valid string having embedded quoted substrings.
              (                         # Either $1: Quoted chunk.
                "[^"\\\\]*(?:\\\\.[^"\\\\]*)*"  # Either a double quoted chunk,
              | \'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'  # or a single quoted chunk.
              )                         # End $1: Quoted chunk.
            | ([^\'"\\\\]+)             # or $2: an unquoted chunk (no escapes).
            /sx';
        
        return preg_replace_callback($re_parse, array($this, '_strReplaceOutsideQuotesCb'), $this->subject);
    }

    /**
     * Process each matching chunk from preg_replace_callback replacing
     * each occurrence of $this->search with $this->replace
     * @author Jeff Roberson <ridgerunner@fluxbb.org>
     * @link http://stackoverflow.com/a/13370709/461813 StackOverflow answer
     * @param array $matches
     * @return string
     */
    protected function _strReplaceOutsideQuotesCb($matches) {
        
        // Return quoted string chunks (in group $1) unaltered.
        if ($matches[1]) {
            
            return $matches[1];
        }
        
        $search_str = '/'. preg_quote($this->search, '/') .'/';
        
        // Process only unquoted chunks (in group $2).
        return preg_replace( $search_str, $this->replace, $matches[2]);
    }
}
