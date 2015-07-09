<?php
namespace IdiormGDAO;

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

    // Reference to previously used PDOStatement object to enable low-level access, if needed
    protected static $_last_statement = null;

    // --------------------------- //
    // --- INSTANCE PROPERTIES --- //
    // --------------------------- //

    // Key name of the connections in self::$_db used by this instance
    protected $_connection_name;

////////////////////////////////////////////////////////////////////////////////        
//////////// ------------------------------------ //////////////////////////////
//////////// --- END CLASS PROPERTIES TO KEEP --- //////////////////////////////
////////////////////////////////////////////////////////////////////////////////

    // The raw query parameters
    protected $_raw_parameters = array();

    // ---------------------- //
    // --- STATIC METHODS --- //
    // ---------------------- //

    /**
     * Pass configuration settings to the class in the form of
     * key/value pairs. As a shortcut, if the second argument
     * is omitted and the key is a string, the setting is
     * assumed to be the DSN string used by PDO to connect
     * to the database (often, this will be the only configuration
     * required to use Idiorm). If you have more than one setting
     * you wish to configure, another shortcut is to pass an array
     * of settings (and omit the second argument).
     * @param string $key
     * @param mixed $value
     * @param string $connection_name Which connection to use
     */
    public static function configure($key, $value = null, $connection_name = self::DEFAULT_CONNECTION) {
        
        self::_setup_db_config($connection_name); //ensures at least default config is set

        if (is_array($key)) {
            
            // Shortcut: If only one array argument is passed,
            // assume it's an array of configuration settings
            foreach ($key as $conf_key => $conf_value) {
                
                self::configure($conf_key, $conf_value, $connection_name);
            }
        } else {
            
            if (is_null($value)) {
                
                // Shortcut: If only one string argument is passed, 
                // assume it's a connection string
                $value = $key;
                $key = 'connection_string';
            }
            
            self::$_config[$connection_name][$key] = $value;
        }
    }

    /**
     * Retrieve configuration options by key, or as whole array.
     * @param string $key
     * @param string $connection_name Which connection to use
     */
    public static function get_config($key = null, $connection_name = self::DEFAULT_CONNECTION) {
        
        if ($key) {
            
            return self::$_config[$connection_name][$key];
            
        } else {
            
            return self::$_config[$connection_name];
        }
    }

    /**
     * Delete all configs in _config array.
     */
    public static function reset_config() {
        
        self::$_config = array();
    }

    /**
     * Despite its slightly odd name, this is actually the factory
     * method used to acquire instances of the class. It is named
     * this way for the sake of a readable interface, ie
     * DBConnector::for_table('table_name')->find_one()-> etc. As such,
     * this will normally be the first method called in a chain.
     * @param string $table_name
     * @param string $connection_name Which connection to use
     * @return DBConnector
     */
    //rename to factory
    public static function factory($connection_name = self::DEFAULT_CONNECTION) {
        
        self::_setup_db($connection_name);
        return new self($connection_name);
    }

    /**
     * Set up the database connection used by the class
     * @param string $connection_name Which connection to use
     */
    protected static function _setup_db($connection_name = self::DEFAULT_CONNECTION) {

        if (!array_key_exists($connection_name, self::$_db) ||
            !is_object(self::$_db[$connection_name])) {

            self::_setup_db_config($connection_name);

            $db = new \PDO(
                self::$_config[$connection_name]['connection_string'],
                self::$_config[$connection_name]['username'],
                self::$_config[$connection_name]['password'],
                self::$_config[$connection_name]['driver_options']
            );

            $db->setAttribute(\PDO::ATTR_ERRMODE, self::$_config[$connection_name]['error_mode']);
            self::set_db($db, $connection_name);
        }
    }

   /**
    * Ensures configuration (multiple connections) is at least set to default.
    * @param string $connection_name Which connection to use
    */
    protected static function _setup_db_config($connection_name) {
        
        if (!array_key_exists($connection_name, self::$_config)) {
            
            self::$_config[$connection_name] = self::$_default_config;
        }
    }

    /**
     * Set the PDO object used by Idiorm to communicate with the database.
     * This is public in case the DBConnector should use a ready-instantiated
     * PDO object as its database connection. Accepts an optional string key
     * to identify the connection if multiple connections are used.
     * @param PDO $db
     * @param string $connection_name Which connection to use
     */
    public static function set_db($db, $connection_name = self::DEFAULT_CONNECTION) {
        
        self::_setup_db_config($connection_name);
        self::$_db[$connection_name] = $db;
    }

    /**
     * Delete all registered PDO objects in _db array.
     */
    public static function reset_db() {
        
        self::$_db = array();
    }

    /**
     * Returns the PDO instance used by the the DBConnector to communicate with
     * the database. This can be called if any low-level DB access is
     * required outside the class. If multiple connections are used,
     * accepts an optional key name for the connection.
     * @param string $connection_name Which connection to use
     * @return PDO
     */
    public static function get_db($connection_name = self::DEFAULT_CONNECTION) {
        
        self::_setup_db($connection_name); // required in case this is called before Idiorm is instantiated
        return self::$_db[$connection_name];
    }

    /**
     * Executes a raw query as a wrapper for PDOStatement::execute.
     * Useful for queries that can't be accomplished through Idiorm,
     * particularly those using engine-specific features.
     * @example raw_execute('SELECT `name`, AVG(`order`) FROM `customer` GROUP BY `name` HAVING AVG(`order`) > 10')
     * @example raw_execute('INSERT OR REPLACE INTO `widget` (`id`, `name`) SELECT `id`, `name` FROM `other_table`')
     * @param string $query The raw SQL query
     * @param array  $parameters Optional bound parameters
     * @param string $connection_name Which connection to use
     * @return bool Success
     */
    public static function raw_execute($query, $parameters = array(), $connection_name = self::DEFAULT_CONNECTION) {
        
        self::_setup_db($connection_name);
        return self::_execute($query, $parameters, $connection_name);
    }

    /**
     * Returns the PDOStatement instance last used by any connection wrapped by the DBConnector.
     * Useful for access to PDOStatement::rowCount() or error information
     * @return PDOStatement
     */
    public static function get_last_statement() {
        
        return self::$_last_statement;
    }

   /**
    * Internal helper method for executing statments. Logs queries, and
    * stores statement object in ::_last_statment, accessible publicly
    * through ::get_last_statement()
    * @param string $query
    * @param array $parameters An array of parameters to be bound in to the query
    * @param string $connection_name Which connection to use
    * @return bool Response of PDOStatement::execute()
    */
    protected static function _execute($query, $parameters = array(), $connection_name = self::DEFAULT_CONNECTION) {
        
        $statement = self::get_db($connection_name)->prepare($query);
        self::$_last_statement = $statement;
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

            $statement->bindParam(is_int($key) ? ++$key : $key, $param, $type);
        }

        $q = $statement->execute();
        self::_log_query($query, $parameters, $connection_name, (microtime(true)-$time));

        return $q;
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
    protected static function _log_query($query, $parameters, $connection_name, $query_time) {
        
        // If logging is not enabled, do nothing
        if (!self::$_config[$connection_name]['logging']) {
            
            return false;
        }

        if (!isset(self::$_query_log[$connection_name])) {
            
            self::$_query_log[$connection_name] = array();
        }

        // Strip out any non-integer indexes from the parameters
        foreach($parameters as $key => $value) {
            
            if (!is_int($key)) {
                
                unset($parameters[$key]);
            }
        }

        if (count($parameters) > 0) {
            
            // Escape the parameters
            $parameters = array_map(array(self::get_db($connection_name), 'quote'), $parameters);

            // Avoid %format collision for vsprintf
            $query = str_replace("%", "%%", $query);

            // Replace placeholders in the query for vsprintf
            if(false !== strpos($query, "'") || false !== strpos($query, '"')) {
                
                $query = StringHelper::str_replace_outside_quotes("?", "%s", $query);
                
            } else {
                
                $query = str_replace("?", "%s", $query);
            }

            // Replace the question marks in the query with the parameters
            $bound_query = vsprintf($query, $parameters);
            
        } else {
            
            $bound_query = $query;
        }

        self::$_last_query = $bound_query;
        self::$_query_log[$connection_name][] = $bound_query;

        if(is_callable(self::$_config[$connection_name]['logger'])){
            
            $logger = self::$_config[$connection_name]['logger'];
            $logger($bound_query, $query_time);
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
    public static function get_last_query($connection_name = null) {
        
        if ($connection_name === null) {
            
            return self::$_last_query;
        }
        if (!isset(self::$_query_log[$connection_name])) {
            
            return '';
        }

        return end(self::$_query_log[$connection_name]);
    }

    /**
     * Get an array containing all the queries run on a
     * specified connection up to now.
     * Only works if the 'logging' config option is
     * set to true. Otherwise, returned array will be empty.
     * @param string $connection_name Which connection to use
     */
    public static function get_query_log($connection_name = self::DEFAULT_CONNECTION) {
        
        if (isset(self::$_query_log[$connection_name])) {
            
            return self::$_query_log[$connection_name];
        }
        
        return array();
    }

    /**
     * Get a list of the available connection names
     * @return array
     */
    public static function get_connection_names() {
        
        return array_keys(self::$_db);
    }

    // ------------------------ //
    // --- INSTANCE METHODS --- //
    // ------------------------ //

    /**
     * "Private" constructor; shouldn't be called directly.
     * Use the DBConnector::for_table factory method instead.
     */
    protected function __construct($connection_name = self::DEFAULT_CONNECTION) {

        $this->_connection_name = $connection_name;
        self::_setup_db_config($connection_name);
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
    public function find_one($query, $parameters) {

        $rows = $this->_run($query, $parameters);

        if (empty($rows)) {
            
            return false;
        }

        return $rows[0];
    }

    /**
     * Tell the DBConnector that you are expecting multiple results
     * from your query, and execute it. Will return an array,
     * or an empty array if no rows were returned.
     * @return array
     */
    public function find_array($query, $parameters) {
        
        return $this->_run($query, $parameters); 
    }

    /**
     * Perform a raw query. The query can contain placeholders in
     * either named or question mark style. If placeholders are
     * used, the parameters should be an array of values which will
     * be bound to the placeholders in the query. If this method
     * is called, all other query building methods will be ignored.
     */
    public function raw_query($query, $parameters = array()) {

        return $this->_run($query, $parameters);
    }

    /**
     * Execute the SELECT query that has been built up by chaining methods
     * on this class. Return an array of rows as associative arrays.
     */
    protected function _run($query, $values, $pdo_fetch_type=\PDO::FETCH_ASSOC) {

        self::_execute($query, $values, $this->_connection_name);
        $statement = self::get_last_statement();

        $rows = array();

        while ($row = $statement->fetch($pdo_fetch_type)) {

            $rows[] = $row;
        }

        return $rows;
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
    public static function str_replace_outside_quotes($search, $replace, $subject) {
        
        return self::value($subject)->replace_outside_quotes($search, $replace);
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
    public function replace_outside_quotes($search, $replace) {
        
        $this->search = $search;
        $this->replace = $replace;
        return $this->_str_replace_outside_quotes();
    }

    /**
     * Validate an input string and perform a replace on all ocurrences
     * of $this->search with $this->replace
     * @author Jeff Roberson <ridgerunner@fluxbb.org>
     * @link http://stackoverflow.com/a/13370709/461813 StackOverflow answer
     * @return string
     */
    protected function _str_replace_outside_quotes(){
        
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
        
        return preg_replace_callback($re_parse, array($this, '_str_replace_outside_quotes_cb'), $this->subject);
    }

    /**
     * Process each matching chunk from preg_replace_callback replacing
     * each occurrence of $this->search with $this->replace
     * @author Jeff Roberson <ridgerunner@fluxbb.org>
     * @link http://stackoverflow.com/a/13370709/461813 StackOverflow answer
     * @param array $matches
     * @return string
     */
    protected function _str_replace_outside_quotes_cb($matches) {
        
        // Return quoted string chunks (in group $1) unaltered.
        if ($matches[1]) {
            
            return $matches[1];
        }
        
        $search_str = '/'. preg_quote($this->search, '/') .'/';
        
        // Process only unquoted chunks (in group $2).
        return preg_replace( $search_str, $this->replace, $matches[2]);
    }
}

/**
 * A placeholder for exceptions eminating from the StringHelper class
 */
class StringHelperException extends \Exception {}