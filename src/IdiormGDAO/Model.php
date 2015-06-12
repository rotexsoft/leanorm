<?php
namespace IdiormGDAO;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlSchema\ColumnFactory;
use GDAO\GDAOModelPrimaryColNameNotSetDuringConstructionException;
use GDAO\GDAOModelTableNameNotSetDuringConstructionException;

/**
 * 
 * Supported PDO drivers: mysql, pgsql, sqlite and sqlsrv
 *
 * @author aadegbam
 */
class Model extends \GDAO\Model
{
    //overriden parent's properties
    /**
     * Name of the collection class for this model. 
     * Must be a descendant of \IdiormGDAO\Model\Collection
     * 
     * @var string 
     */
    protected $_collection_class_name = '\\IdiormGDAO\Model\\Collection';
    
    
    /**
     * Name of the record class for this model. 
     * Must be a descendant of \IdiormGDAO\Model\Record
     * 
     * @var string 
     */
    protected $_record_class_name = '\\IdiormGDAO\\Model\\Record';
    
    /////////////////////////////////////////////////////////////////////////////
    // Properties declared here are specific to \IdiormGDAO\Model and its kids //
    /////////////////////////////////////////////////////////////////////////////
    protected static $_valid_extra_opts_keys_4_idiorm = array(
        'identifier_quote_character', // if this is null, will be autodetected
        'limit_clause_style', //[NOT REALLY NEEDED SINCE QUERIES ARE BEING BUILT
                              //USING AURA SQL QUERY OBJECTS] 
                              //if this is null, will be autodetected
        'logging', //[NOT NEEDED: WILL IMPLEMENT QUERY LOGGING HERE]
        'logger',  //[NOT NEEDED: WILL IMPLEMENT QUERY LOGGING HERE]
        'caching', // [NOT NEED: SHOULD IMPLEMENT CACHING IF NEEDED]
    );
    
    protected static $_where_or_having_ops_2_dbms_ops = array(
        '='         => '=', 
        '>'         => '>', 
        '>='        => '>=', 
        '<'         => '>', 
        '<='        => '<=', 
        'in'        => 'IN', 
        'is-null'   => 'IS NULL', 
        'like'      => 'LIKE', 
        '!='        => '<>', 
        'not-in'    => 'NOT IN',
        'not-like'  => 'NOT LIKE', 
        'not-null'  => 'IS NOT NULL'
    );

    /**
     *
     * Name of the pdo driver currently being used.
     * It must be one of the values returned by 
     * $this->getPDO()->getAvailableDrivers()
     * 
     * @var string  
     */
    protected $_pdo_driver_name = null;


    /**
     * 
     * {@inheritDoc}
     */
    public function __construct(
        $dsn = '', 
        $username = '', 
        $passwd = '', 
        array $pdo_driver_opts = array(),
        array $extra_opts = array()
    ) {
        if(count($extra_opts) > 0) {
            
            //set properties of this class specified in $extra_opts
            foreach($extra_opts as $e_opt_key => $e_opt_val) {
  
                if ( property_exists($this, $e_opt_key) ) {
                    
                    $this->$e_opt_key = $e_opt_val;

                } elseif ( property_exists($this, '_'.$e_opt_key) ) {

                    $this->{"_$e_opt_key"} = $e_opt_val;
                }
            }
        }

        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $extra_opts);

        \ORM::configure($dsn);
        \ORM::configure('username', $username);
        \ORM::configure('password', $passwd);
        
        if( count($pdo_driver_opts) > 0 ) {
            
            \ORM::configure( 'driver_options', $pdo_driver_opts);
        }
        
        foreach ($extra_opts as $e_opt_key => $e_opt_val) {

            if(
                is_string($e_opt_key) 
                && in_array($e_opt_key, static::$_valid_extra_opts_keys_4_idiorm)
            ) {
                \ORM::configure($e_opt_key, $e_opt_val);
            }
        }
        
        $this->_pdo_driver_name = $this->getPDO()
                                       ->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if(!empty($this->_primary_col) && strlen($this->_primary_col) > 0) {
            
            \ORM::configure('id_column', $this->_primary_col);
            
        } else {
            
            $msg = 'Primary Key Column name ($_primary_col) not set for '.get_class($this);
            throw new GDAOModelPrimaryColNameNotSetDuringConstructionException($msg);
        }
        
        if(empty($this->_table_name)) {
            
            $msg = 'Table name ($_table_name) not set for '.get_class($this);
            throw new GDAOModelTableNameNotSetDuringConstructionException($msg);
        }
        
        ////////////////////////////////////////////////////////
        //Get and Set Table Schema Meta Data if Not Already Set
        ////////////////////////////////////////////////////////
        if ( empty($this->_table_cols) || count($this->_table_cols) <= 0 ) {

            // a column definition factory 
            $column_factory = new ColumnFactory();

            $schema_class_name = '\\Aura\\SqlSchema\\' 
                                 .ucfirst($this->_pdo_driver_name).'Schema';

            // the schema discovery object
            $schema = new $schema_class_name($this->getPDO(), $column_factory);

            $this->_table_cols = array();
            $schema_definitions = $schema->fetchTableCols($this->_table_name);

            foreach( $schema_definitions as $colname => $metadata_obj ) {

                $this->_table_cols[$colname] = array();
                $this->_table_cols[$colname]['name'] = $metadata_obj->name;
                $this->_table_cols[$colname]['type'] = $metadata_obj->type;
                $this->_table_cols[$colname]['size'] = $metadata_obj->size;
                $this->_table_cols[$colname]['scale'] = $metadata_obj->scale;
                $this->_table_cols[$colname]['notnull'] = $metadata_obj->notnull;
                $this->_table_cols[$colname]['default'] = $metadata_obj->default;
                $this->_table_cols[$colname]['autoinc'] = $metadata_obj->autoinc;
                $this->_table_cols[$colname]['primary'] = $metadata_obj->primary;
            }
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function createCollection(\GDAO\Model\GDAORecordsList $list_of_records, array $extra_opts=array()) {
        
        if( empty($this->_collection_class_name) ) {
         
            //default to creating new collection of type \IdiormGDAO\Model\Collection
            $collection = new \IdiormGDAO\Model\Collection($list_of_records);
            
        } else {
            
            $collection = new $this->_collection_class_name($list_of_records);
        }
        
        $collection->setModel($this);
        
        return $collection;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function createRecord(array $col_names_and_values = array(), array $extra_opts=array()) {
        
        if( empty($this->_record_class_name) ) {
         
            //default to creating new record of type \IdiormGDAO\Model\Record
            $record = new \IdiormGDAO\Model\Record($col_names_and_values);
            
        } else {
            
            $record = new $this->_record_class_name($col_names_and_values);
        }
        
        $record->setModel($this);
        
        if( 
            (array_key_exists('_is_new', $extra_opts) &&  $extra_opts['_is_new'] === false)
            || (array_key_exists('is_new', $extra_opts) &&  $extra_opts['is_new'] === false)
        ) {
            $record->markAsNotNew();
        }
        
        return $record;
    }
    
    /**
     * 
     * @param array $params an array of parameters passed to a fetch*() method
     * @param array $disallowed_keys list of keys in $params not to be used to build the query object 
     * @return \Aura\SqlQuery\Common\Select or any of its descendants
     */
    protected function _buildFetchQueryFromParams(
        array $params=array(), array $disallowed_keys=array()
    ) {
        $select_qry_obj = (new QueryFactory($this->_pdo_driver_name))->newSelect();
        $select_qry_obj->from($this->_table_name);
        
        if( !empty($params) && count($params) > 0 ) {
            
            if(
                (
                    !in_array('distinct', $disallowed_keys) 
                    || count($disallowed_keys) <= 0
                )
                && array_key_exists('distinct', $params)
            ) {
                //add distinct clause if specified
                if( $params['distinct'] ) {
                    
                    $select_qry_obj->distinct();
                }
            }
            
            if( 
                (
                    !in_array('cols', $disallowed_keys) 
                    || count($disallowed_keys) <= 0
                )
                && array_key_exists('cols', $params)
            ) {
                if( is_array($params['cols']) ) {
                    
                    $select_qry_obj->cols($params['cols']);
                    
                } else {
                    //throw exception badly structured array
                    $msg = "ERROR: Bad fetch param entry. Array expected as the"
                         . " value of the item with the key named 'cols' in the"
                         . " array: "
                         . PHP_EOL . var_export($params, true) . PHP_EOL
                         . " passed to " 
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . PHP_EOL;

                    throw new ModelBadColsParamSuppliedException($msg);
                }
                
            } else if( !array_key_exists('cols', $params) ) {
                
                //default to SELECT *
                $select_qry_obj->cols(array('*'));
            }
            
            if( 
                (
                    !in_array('where', $disallowed_keys) 
                    || count($disallowed_keys) <= 0
                )
                && array_key_exists('where', $params)
            ) {
                if( is_array($params['where']) ) {
                    
                    $this->_addWhereConditions2Query(
                                $params['where'], $select_qry_obj
                            );
                } else {
                    //throw exception badly structured array
                    $msg = "ERROR: Bad fetch param entry. Array expected as the"
                         . " value of the item with the key named 'where' in the"
                         . " array: "
                         . PHP_EOL . var_export($params, true) . PHP_EOL
                         . " passed to " 
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . PHP_EOL;

                    throw new ModelBadWhereParamSuppliedException($msg);
                }
            }
            
            if( 
                (
                    !in_array('group', $disallowed_keys) 
                    || count($disallowed_keys) <= 0
                )
                && array_key_exists('group', $params)
            ) {
                if( is_array($params['group']) ) {
                    
                    $select_qry_obj->groupBy($params['group']);
                    
                } else {
                    //throw exception badly structured array
                    $msg = "ERROR: Bad fetch param entry. Array expected as the"
                         . " value of the item with the key named 'group' in the"
                         . " array: "
                         . PHP_EOL . var_export($params, true) . PHP_EOL
                         . " passed to " 
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . PHP_EOL;

                    throw new ModelBadGroupByParamSuppliedException($msg);
                }
            }
            
            if( 
                (
                    !in_array('having', $disallowed_keys) 
                    || count($disallowed_keys) <= 0
                )
                && array_key_exists('having', $params)
            ) {
                if( is_array($params['having']) ) {
                    
                    $this->_addHavingConditions2Query(
                                $params['having'], $select_qry_obj
                            );
                } else {
                    //throw exception badly structured array
                    $msg = "ERROR: Bad fetch param entry. Array expected as the"
                         . " value of the item with the key named 'having' in the"
                         . " array: "
                         . PHP_EOL . var_export($params, true) . PHP_EOL
                         . " passed to " 
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . PHP_EOL;

                    throw new ModelBadHavingParamSuppliedException($msg);
                }
            }
            
            if( 
                (
                    !in_array('order', $disallowed_keys) 
                    || count($disallowed_keys) <= 0
                )
                && array_key_exists('order', $params)
            ) {
                if( is_array($params['order']) ) {
                    
                    $select_qry_obj->orderBy($params['order']);
                    
                } else {
                    //throw exception badly structured array
                    $msg = "ERROR: Bad fetch param entry. Array expected as the"
                         . " value of the item with the key named 'order' in the"
                         . " array: "
                         . PHP_EOL . var_export($params, true) . PHP_EOL
                         . " passed to " 
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . PHP_EOL;

                    throw new ModelBadOrderByParamSuppliedException($msg);
                }
            }

            if( 
                (
                    !in_array('limit_size', $disallowed_keys) 
                    || count($disallowed_keys) <= 0
                )
                && array_key_exists('limit_size', $params)
            ) {
                if( is_numeric($params['limit_size']) ) {
                    
                    $select_qry_obj->limit( (int)$params['limit_size'] );
                    
                } else {
                    //throw exception badly structured array
                    $msg = "ERROR: Bad fetch param entry. Integer expected as the"
                         . " value of the item with the key named 'limit_size'"
                         . " in the array: "
                         . PHP_EOL . var_export($params, true) . PHP_EOL
                         . " passed to " 
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . PHP_EOL;

                    throw new ModelBadOrderByParamSuppliedException($msg);
                }
            }
            
            if( 
                (
                    !in_array('limit_offset', $disallowed_keys) 
                    || count($disallowed_keys) <= 0
                )
                && array_key_exists('limit_offset', $params)
            ) {
                if( is_numeric($params['limit_offset']) ) {
                    
                    $select_qry_obj->offset( (int)$params['limit_offset'] );
                    
                } else {
                    //throw exception badly structured array
                    $msg = "ERROR: Bad fetch param entry. Integer expected as the"
                         . " value of the item with the key named 'limit_offset'"
                         . " in the array: "
                         . PHP_EOL . var_export($params, true) . PHP_EOL
                         . " passed to " 
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . PHP_EOL;

                    throw new ModelBadOrderByParamSuppliedException($msg);
                }
            }
        } else {
            
            //defaults
            $select_qry_obj->cols(array('*'));
        }

//r($select_qry_obj->__toString());
//r($select_qry_obj->getBindValues());exit;

        return $select_qry_obj;
    }

    /**
     * 
     * @param array $whr_or_hvn_parms
     * @param \Aura\SqlQuery\Common\Select $select_qry_obj
     * 
     */
    protected function _addWhereConditions2Query(
        array $whr_or_hvn_parms, \Aura\SqlQuery\Common\Select $select_qry_obj
    ) {
        if( !empty($whr_or_hvn_parms) && count($whr_or_hvn_parms) > 0 ) {
            
            if($this->_validateWhereOrHavingParamsArray($whr_or_hvn_parms)) {

                $sql_n_bind_params = 
                    $this->_getWhereOrHavingClauseWithParams($whr_or_hvn_parms);
                                
                $select_qry_obj->where( $sql_n_bind_params[0] );
                
                if( count( $sql_n_bind_params[1] ) > 0 ) {
                    
                    $select_qry_obj->bindValues( $sql_n_bind_params[1] );
                }
            }

        }
    }

    /**
     * 
     * @param array $whr_or_hvn_parms
     * @param \Aura\SqlQuery\Common\Select $select_qry_obj
     * 
     */
    protected function _addHavingConditions2Query(
        array $whr_or_hvn_parms, \Aura\SqlQuery\Common\Select $select_qry_obj
    ) {
        if( !empty($whr_or_hvn_parms) && count($whr_or_hvn_parms) > 0 ) {
            
            if($this->_validateWhereOrHavingParamsArray($whr_or_hvn_parms)) {

                $sql_n_bind_params = 
                    $this->_getWhereOrHavingClauseWithParams($whr_or_hvn_parms);
                                
                $select_qry_obj->having( $sql_n_bind_params[0] );
                
                if( count( $sql_n_bind_params[1] ) > 0 ) {
                    
                    $select_qry_obj->bindValues( $sql_n_bind_params[1] );
                }
            }

        }
    }
    
    /**
     * 
     * Callers of this method should first validate $array via
     * \GDAO\Model::_validateWhereOrHavingParamsArray(array $array)
     * before calling this method.
     * 
     * @staticvar int $bind_params_index
     * @param array $array an array of where or having condition(s) definition as
     *                     specified in the params documentation of the fetch* methods
     * @param int $indent_level the number of tab characters to add to the sql clause
     * @return array an array of two items, the first is the having or where 
     *               clause sql string and the second item is an associative
     *               array of parameters to bind to the query
     * @throws ModelBadWhereParamSuppliedException
     * 
     * @see \GDAO\Model::_validateWhereOrHavingParamsArray(array $array)
     * 
     */
    protected function _getWhereOrHavingClauseWithParams(array &$array, $indent_level=0) {

        static $bind_params_index;

        if( !isset($bind_params_index) ) {

            $bind_params_index = 0;
        }

        $i = 0;
        $result_sql = '';
        $result_bind_params = array();
        $result_sql .= str_repeat("\t", $indent_level). '('. PHP_EOL;

        foreach ( $array as $key => $value ) {

            if ( is_numeric($key) || $key === "OR" || substr($key, 0, 3) === "OR#" ) {

                $and_or = ( is_numeric($key) ) ? 'AND' : 'OR' ;

                if( $i > 0 ) {

                    //not the first item
                    $result_sql .= str_repeat("\t", ($indent_level + 1) ). $and_or. PHP_EOL;
                }

                if( is_array($value) ) {

                    $has_a_val_key = 
                        (is_array($value)) && array_key_exists('val', $value);

                    $has_a_col_and_an_operator_key = 
                        (is_array($value)) 
                        && array_key_exists('col', $value) 
                        && array_key_exists('operator', $value);

                    if( $has_a_col_and_an_operator_key ) {
                        
                        $operator_is_in_or_not_in = 
                            in_array($value['operator'], array('not-in', 'in'));

                        //quote $value['col'] and $value['val'] as needed
                        $db_specific_operator = 
                            static::$_where_or_having_ops_2_dbms_ops[$value['operator']];

                        if( 
                            !$has_a_val_key 
                            ||  in_array( $value['operator'], array('not-null', 'is-null') ) 
                        ) {
                            $result_sql .= str_repeat("\t", ($indent_level + 1) )
                                     . "{$value['col']} $db_specific_operator" . PHP_EOL;

                        } else if( $has_a_val_key ) {

                            //$value['val'] should be pdo quoted.
                            $quoted_val = '';
                            
                            if (is_array($value['val'])) {

                                //quote all string values
                                array_walk(
                                        
                                    $value['val'],

                                    function(&$val, $key, $pdo) {
                                        $val = 
                                            (is_string($val)) 
                                                ? $pdo->quote($val) : $val;
                                    },

                                    $this->getPDO()
                                );

                                $quoted_val = 
                                    " (" . implode(',', $value['val']) . ") ";
                            } else {

                                $quoted_val = 
                                    (
                                        !$operator_is_in_or_not_in 
                                        && is_string($value['val'])
                                    ) ? 
                                        $this->getPDO()->quote($value['val']) 
                                                                : $value['val'];
                                
                                if($operator_is_in_or_not_in) {
                                    
                                    if(
                                        is_numeric($value['val'])
                                        ||
                                        (
                                            is_string($value['val'])
                                            && strpos($value['val'], '(') === false
                                            && strpos($value['val'], ')') === false
                                        )
                                    ) {
                                        $quoted_val = "($quoted_val)";
                                    }
                                }
                            }
                            
                            $bind_params_index++;
                            
                            if( !$operator_is_in_or_not_in ) {
                                
                                $result_sql .= str_repeat("\t", ($indent_level + 1) )
                                         . "{$value['col']} $db_specific_operator :_{$bind_params_index}_ " 
                                         . PHP_EOL;
                                $result_bind_params["_{$bind_params_index}_"] = $quoted_val;
                                
                            } else {
                                //no need for named place holder just place the
                                //quated val directly.
                                $result_sql .= str_repeat("\t", ($indent_level + 1) )
                                         . "{$value['col']} $db_specific_operator $quoted_val " 
                                         . PHP_EOL;
                            }
                        }
                    } else {
                        //a sub-array of more conditions, recurse
                        $full_result = $this->_getWhereOrHavingClauseWithParams($value, ($indent_level + 1) );
                        
                        $result_sql .= $full_result[0];
                        $result_bind_params = array_merge($result_bind_params, $full_result[1]);
                    }
                } else {
                    //throw exception badly structured array
                    $msg = "ERROR: Bad where param array having an entry with a"
                           . " key named '$key' with a non-expected value of "
                           . PHP_EOL . var_export($value, true) . PHP_EOL
                           . "inside the array: "
                           . PHP_EOL . var_export($array, true) . PHP_EOL
                           . " passed to " 
                           . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                           . PHP_EOL;

                    throw new ModelBadWhereParamSuppliedException($msg);
                }
            }
            $i++;
        }

        return array( 
                    $result_sql.str_repeat("\t", $indent_level) . ')' . PHP_EOL,
                    $result_bind_params
               );
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchAll(array $params = array()) {

        //fetch a collection of records [Eager Loading should be considered here]
        
        return $this->createCollection(
                        new \GDAO\Model\GDAORecordsList(
                                $this->fetchAllAsArray($params)
                            )
                    );
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchAllAsArray(array $params = array()) {
        
        //fetch an array of records [Eager Loading should be considered here]        
        $query_obj = $this->_buildFetchQueryFromParams($params);

        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();
        
        $orm_obj = \ORM::for_table($this->_table_name);
        $results = $orm_obj->raw_query($sql, $params_2_bind_2_sql)->find_array();
        
        foreach ($results as $key=>$value) {

            $results[$key] = $this->createRecord($value, array('is_new'=>false));
        }
        
        return $results;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchArray(array $params = array()) {
        
        //fetch an array of records [Eager Loading should be considered here]
        $query_obj = $this->_buildFetchQueryFromParams($params);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $orm_obj = \ORM::for_table($this->_table_name);
        $results = $orm_obj->raw_query($sql, $params_2_bind_2_sql)->find_array();
        
        return $results;
    }
    
    /**
     * 
     * @return PDO
     */
    public function getPDO() {
        
        return \ORM::get_db();
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function deleteRecordsMatchingSpecifiedColsNValues(array $cols_n_vals) {
        
        $result = null;
        
        if ( !empty($cols_n_vals) && count($cols_n_vals) > 0 ) {

            //select query obj will be used to execute a select count(*) query
            //to see if the criteria specified in $col_names_n_vals_2_match
            //matches any cols
            $select_qry_obj = (new QueryFactory($this->_pdo_driver_name))->newSelect();
            $select_qry_obj->cols(array('COUNT(*) AS num_of_matched_records'));
            $select_qry_obj->from($this->_table_name);
            
            //delete statement
            $del_qry_obj = (new QueryFactory($this->_pdo_driver_name))->newDelete();
            $del_qry_obj->from($this->_table_name);

            foreach ($cols_n_vals as $colname => $colval) {

                if (is_array($colval)) {
                    //quote all string values
                    array_walk(
                        $colval,
                        function(&$val, $key, $pdo) {
                            $val = (is_string($val)) ? $pdo->quote($val) : $val;
                        },                     
                        $this->getPDO()
                    );
                      
                    $select_qry_obj->where("{$colname} IN (" . implode(',', $colval) . ") ");
                    $del_qry_obj->where("{$colname} IN (" . implode(',', $colval) . ") ");
                } else {

                    $select_qry_obj->where("{$colname} = ?", $colval);
                    $del_qry_obj->where("{$colname} = ?", $colval);
                }
            }

            $orm_obj = \ORM::for_table($this->_table_name);
            
            $slct_qry = $select_qry_obj->__toString();
            $slct_qry_params = $select_qry_obj->getBindValues();
            $slct_qry_result = $orm_obj->raw_query($slct_qry, $slct_qry_params)
                                       ->find_one()
                                       ->as_array();
            $num_of_matched_records = $slct_qry_result['num_of_matched_records'];
//r($slct_qry);
//r($slct_qry_params);
//r($slct_qry_result);
//r($num_of_matched_records);
            
            if ( $num_of_matched_records > 0 ) {
                
                //there are some rows of data to update
                $dlt_qry = $del_qry_obj->__toString(); //echo $query.'<br>';
                $dlt_qry_params = $del_qry_obj->getBindValues(); //print_r($query_params);
//r($qry);
//r($qry_params);
                $result = $orm_obj->raw_execute($dlt_qry, $dlt_qry_params); 
//echo $orm_obj->get_last_query();
            }
        }
        
        return $result;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function deleteSpecifiedRecord(\GDAO\Model\Record $record) {
        
        //$this->_primary_col should have a valid value because a
        //GDAO\ModelPrimaryColNameNotSetDuringConstructionException
        //is thrown in $this->__construct() if $this->_primary_col is not set.
        $succesfully_deleted = null;
        
        if ( count($record) > 0 ) { //test if the record object has data
            
            $pri_key_val = $record->getPrimaryVal();
            $cols_n_vals = array($this->_primary_col => $pri_key_val);

            $succesfully_deleted = 
                $this->deleteRecordsMatchingSpecifiedColsNValues($cols_n_vals);

            if ($succesfully_deleted) {

                $record->setStateToNew();
            }
        }
        
        return $succesfully_deleted;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchCol(array $params = array()) {
        
        $col_name = '';
        
        if( array_key_exists('cols', $params) && count($params['cols']) > 0 ) {
            
            //extract the first col since only the first col will be returned
            $params['cols'] = ( (array)$params['cols'] );
            $col_name = array_shift($params['cols']);
            $params['cols'] = array( $col_name );
        
        } else {
            
            //throw Exception no col specified
            $msg = "ERROR: Bad param entry. Array expected as the value of the"
                 . " item with the key named 'cols' OR no item with"
                 . " a key named 'cols'  in the array: "
                 . PHP_EOL . var_export($params, true) . PHP_EOL
                 . " passed to " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new ModelBadWhereParamSuppliedException($msg);
        }

        $query_obj = $this->_buildFetchQueryFromParams($params);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $orm_obj = \ORM::for_table($this->_table_name);
        $results = $orm_obj->raw_query($sql, $params_2_bind_2_sql)->find_array();

        return array_column($results, $col_name);
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchOne(array $params = array()) {

        $param_keys_2_exclude = array('limit_offset', 'limit_size');
        
        $query_obj = 
            $this->_buildFetchQueryFromParams($params, $param_keys_2_exclude);
        $query_obj->limit(1);
        
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $orm_obj = \ORM::for_table($this->_table_name);
        $result = $orm_obj->raw_query($sql, $params_2_bind_2_sql)->find_array();
        
        if( count($result) > 0 ) {
            
            $result = 
                $this->createRecord(array_shift($result), array('is_new'=>false));
        }

        return $result;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchPairs(array $params = array()) {
        
        $key_col_name = '';
        $val_col_name = '';
        
        if( array_key_exists('cols', $params) && count($params['cols']) >=2 ) {
            
            //extract the first col since only the first col will be returned
            $params['cols'] = ( (array)$params['cols'] );
            
            $key_col_name = array_shift( $params['cols'] );
            $val_col_name = array_shift( $params['cols'] ) ;
            
            $params['cols'] = array( $key_col_name, $val_col_name);
        
        } else {
            
            //throw Exception no col specified
            $msg = "ERROR: Bad param entry. Array (with at least two items) "
                 . "expected as the value of the item with the key named 'cols'"
                 . " OR no item with a key named 'cols'  in the array: "
                 . PHP_EOL . var_export($params, true) . PHP_EOL
                 . " passed to " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new ModelBadWhereParamSuppliedException($msg);
        }

        $query_obj = $this->_buildFetchQueryFromParams($params);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $orm_obj = \ORM::for_table($this->_table_name);
        $results = $orm_obj->raw_query($sql, $params_2_bind_2_sql)->find_array();
        
        return array_combine(
                    array_column($results, $key_col_name), 
                    array_column($results, $val_col_name)
                );
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchValue(array $params = array()) {
        
        $col_name = '';
        
        if( array_key_exists('cols', $params) && count($params['cols']) > 0 ) {
            
            //extract the first col since only the 1st value from the 1st col 
            //will be returned
            $params['cols'] = ( (array)$params['cols'] );
            $col_name = array_shift($params['cols']);
            $params['cols'] = array( $col_name );
        
        } else {
            
            //throw Exception no col specified
            $msg = "ERROR: Bad param entry. Array expected as the value of the"
                 . " item with the key named 'cols' OR no item with"
                 . " a key named 'cols'  in the array: "
                 . PHP_EOL . var_export($params, true) . PHP_EOL
                 . " passed to " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new ModelBadWhereParamSuppliedException($msg);
        }
        
        $param_keys_2_exclude = array('limit_offset', 'limit_size');
        
        $query_obj = 
            $this->_buildFetchQueryFromParams($params, $param_keys_2_exclude);
        $query_obj->limit(1);
        
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $orm_obj = \ORM::for_table($this->_table_name);
        $result = $orm_obj->raw_query($sql, $params_2_bind_2_sql)->find_array();
       
        if( count($result) > 0 ) {
            
            $result = array_shift($result);
            $result = $result[$col_name];
        }

        return $result;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function insert($col_names_n_vals = array()) {
        
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function updateRecordsMatchingSpecifiedColsNValues(
        array $col_names_n_vals_2_save = array(),
        array $col_names_n_vals_2_match = array()
    ) {
        $result = null;
        
        if ( 
            !empty($col_names_n_vals_2_save) && count($col_names_n_vals_2_save) > 0 
        ) {
            $last_updtd_colname = $this->_updated_timestamp_column_name;
          
            if(
                !empty($last_updtd_colname) 
                && in_array($last_updtd_colname, $this->getTableCols())
            ) {
                //set last updated timestamp to now
                $col_names_n_vals_2_save[$last_updtd_colname] = date('Y-m-d H:i:s');
            }
            
            //select query obj will be used to execute a select count(*) query
            //to see if the criteria specified in $col_names_n_vals_2_match
            //matches any cols
            $select_qry_obj = (new QueryFactory($this->_pdo_driver_name))->newSelect();
            $select_qry_obj->cols(array('COUNT(*) AS num_of_matched_records'));
            $select_qry_obj->from($this->_table_name);
            
            //update statement
            $update_qry_obj = (new QueryFactory($this->_pdo_driver_name))->newUpdate();
            $update_qry_obj->table($this->_table_name);
            $update_qry_obj->cols($col_names_n_vals_2_save);
            
            if ( 
                !empty($col_names_n_vals_2_match) 
                && count($col_names_n_vals_2_match) > 0 
            ) {
                foreach ($col_names_n_vals_2_match as $colname => $colval) {

                    if (is_array($colval)) {
                        //quote all string values
                        array_walk(
                            $colval,
                            function(&$val, $key, $pdo) {
                                $val = (is_string($val)) ? $pdo->quote($val) : $val;
                            },                     
                            $this->getPDO()
                        );

                        $select_qry_obj->where("{$colname} IN (" . implode(',', $colval) . ") ");
                        $update_qry_obj->where("{$colname} IN (" . implode(',', $colval) . ") ");
                    } else {

                        $select_qry_obj->where("{$colname} = ?", $colval);
                        $update_qry_obj->where("{$colname} = ?", $colval);
                    }
                }
            }
           
            $orm_obj = \ORM::for_table($this->_table_name);

            $slct_qry = $select_qry_obj->__toString();
            $slct_qry_params = $select_qry_obj->getBindValues();
            $slct_qry_result = $orm_obj->raw_query($slct_qry, $slct_qry_params)
                                       ->find_one()
                                       ->as_array();
            $num_of_matched_records = $slct_qry_result['num_of_matched_records'];
//r($slct_qry);
//r($slct_qry_params);
//r($slct_qry_result);
//r($num_of_matched_records);
            
            if( $num_of_matched_records > 0 ) {

                //there are some rows of data to update
                $updt_qry = $update_qry_obj->__toString();//echo $query.'<br>';
                $updt_qry_params = $update_qry_obj->getBindValues();// print_r($query_params);
//r($updt_qry);
//r($updt_qry_params);
                $result = $orm_obj->raw_execute($updt_qry, $updt_qry_params); 
//echo $orm_obj->get_last_query();
            }
        }
        
        return $result;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function updateSpecifiedRecord(\GDAO\Model\Record $record) {
        
        //$this->_primary_col should have a valid value because a
        //GDAO\ModelPrimaryColNameNotSetDuringConstructionException
        //is thrown in $this->__construct() if $this->_primary_col is not set.
        $succesfully_updated = null;
        
        if( count($record) > 0 ) { //test if the record object has data
            
            $pri_key_val = $record->getPrimaryVal();
            $cols_n_vals_2_match = array($this->_primary_col=>$pri_key_val);

            $succesfully_updated = 
                $this->updateRecordsMatchingSpecifiedColsNValues(
                            $record->getData(), $cols_n_vals_2_match
                        );
        }
        
        return $succesfully_updated;
    }
    
    public function __get($property_name) {
        
        if( property_exists($this, $property_name) ) {
            
        } else if ( property_exists($this, "_$property_name") ) {
            
            $property_name = "_$property_name";
            
        } else {
            
            $msg = "ERROR: The property named '$property_name' doesn't exist in "
                  . get_class($this) . '.' . PHP_EOL;

            throw new ModelPropertyNotDefinedException($msg);
        }
        
        return $this->$property_name;
    }
}

class ModelBadColsParamSuppliedException extends \Exception{}
class ModelBadGroupByParamSuppliedException extends \Exception{}
class ModelBadHavingParamSuppliedException extends \Exception{}
class ModelBadOrderByParamSuppliedException extends \Exception{}
class ModelBadWhereParamSuppliedException extends \Exception{}
class ModelPropertyNotDefinedException extends \Exception{}