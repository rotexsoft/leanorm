<?php
namespace IdiormGDAO;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlSchema\ColumnFactory;
use Aura\SqlSchema\MysqlSchema;
use GDAO\GDAOModelPrimaryColNameNotSetDuringConstructionException;
use GDAO\GDAOModelTableNameNotSetDuringConstructionException;

/**
 * Description of Model
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
        'connection_string',
        'id_column' => 'id',
        'id_column_overrides',
        'error_mode',
        'username',
        'password',
        'driver_options',
        'identifier_quote_character', // if this is null, will be autodetected
        'limit_clause_style', // if this is null, will be autodetected
        'logging',
        'logger',
        'caching',
        'caching_auto_clear',
        'return_result_sets',
    );
    
    protected static $_where_or_having_ops_to_mysql_ops = array(
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
     * {@inheritDoc}
     */
    public function __construct(
        $dsn = '', 
        $username = '', 
        $passwd = '', 
        array $pdo_driver_opts = array(),
        array $extra_opts = array()
    ) {
        if(count($extra_opts) > 0){
            
            foreach($extra_opts as $e_opt_key => $e_opt_val) {
  
                if ( property_exists($this, $e_opt_key) ) {
                    
                    $this->$e_opt_key = $e_opt_val;

                } elseif ( property_exists($this, '_'.$e_opt_key) ) {

                    $this->{"_$e_opt_key"} = $e_opt_val;
                }
            }
        }

        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $extra_opts);
//r($this->toArray());exit;
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
//            elseif(is_string($e_opt_val)) {
//
//                \ORM::configure($e_opt_val);
//            }
        }
        
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

            // the schema discovery object 
            $schema = new MysqlSchema($this->getPDO(), $column_factory);

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
     * @param array $allowed_keys list of keys in $params to be used to build the query object 
     * @return \Aura\SqlQuery\Common\Select or any of its descendants
     */
    protected function _buildFetchQueryFromParams(
        array $params=array(), array $allowed_keys=array()
    ) {
        $select_qry_obj = (new QueryFactory('mysql'))->newSelect();
        
        $select_qry_obj->from($this->_table_name);
        
        if( !empty($params) && count($params) > 0 ) {
            
            if(
                (
                    in_array('distinct', $allowed_keys) 
                    || count($allowed_keys) <= 0
                )
                && array_key_exists('distinct', $params)
            ) {
                //add distinct clause if specified
                
                if( $params['distinct'] ) {
                    
                    $select_qry_obj->distinct();
                }
            }
            
            if( 
                (in_array('cols', $allowed_keys) || count($allowed_keys) <= 0)
                && array_key_exists('cols', $params)
            ) {                
                if( is_array($params['cols']) ) {
                    
                    $select_qry_obj->cols($params['cols']);
                }
            } else if( !array_key_exists('cols', $params) ) {
                
                //default to SELECT *
                $select_qry_obj->cols(array('*'));
            }
            
            if( 
                (in_array('where', $allowed_keys) || count($allowed_keys) <= 0)
                && array_key_exists('where', $params)
            ) {
                if( is_array($params['where']) ) {
                    
                    $this->_addWhereConditions2Query(
                                $params['where'], $select_qry_obj
                            );
                }
            }
        }
        
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
     * @staticvar int $bind_params_index
     * @param array $array an array of where or having condition(s) definition as
     *                     specified in the params documentation of the fetch* methods
     * @param int $indent_level the number of tab characters to add to the sql clause
     * @return array an array of two items, the first is the having or where 
     *               clause sql string and the second item is an associative
     *               array of parameters to bind to the query
     * @throws ModelBadWhereParamSuppliedException
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

                        //quote $value['col'] and $value['val'] as needed
                        $mysql_operator = 
                            static::$_where_or_having_ops_to_mysql_ops[$value['operator']];

                        if( 
                            !$has_a_val_key 
                            ||  in_array( $value['operator'], array('not-null', 'is-null') ) 
                        ) {
                            //check that operator's value is either 'is-null' or 'not-null'
                            $result_sql .= str_repeat("\t", ($indent_level + 1) )
                                     . "{$value['col']} $mysql_operator" . PHP_EOL;

                        } else if( $has_a_val_key ) {

                            //$value['val'] should not be empty
                            //should be pdo quoted.
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
                                    (is_string($value['val']))? 
                                        $this->getPDO()->quote($value['val']) 
                                                                : $value['val'];
                            }
                            
                            $bind_params_index++;
                            
                            $result_sql .= str_repeat("\t", ($indent_level + 1) )
                                     . "{$value['col']} $mysql_operator :_{$bind_params_index}_ " 
                                     . PHP_EOL;
                            $result_bind_params["_{$bind_params_index}_"] = $quoted_val;
                            
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
        $orm_obj = \ORM::for_table($this->_table_name);

        $query_obj = $this->_buildFetchQueryFromParams($params);
        $fetch_sql = $query_obj->__toString();
        $bind_params = $query_obj->getBindValues();
/*
r($fetch_sql);
r($bind_params);exit;
//*/
        $results = $orm_obj->raw_query($fetch_sql, $bind_params)->find_array();
        
        foreach ($results as $key=>$value) {

            $results[$key] = $this->createRecord($value, array('is_new'=>false) );
        }
        
        return $this->createCollection( new \GDAO\Model\GDAORecordsList($results) );
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchAllAsArray(array $params = array()) {
        
        //fetch an array of records [Eager Loading should be considered here]        
        $orm_obj = \ORM::for_table($this->_table_name);
        
        $results = $orm_obj
                        ->where('hidden_fiscal_year', '16')
                        ->where('deactivated', '0')
                        ->where_null('parent_id')
                        ->find_array();
        
        foreach ($results as $key=>$value) {

            $results[$key] =  $this->createRecord($value, array('is_new'=>false));
        }
        
        return $results;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchArray(array $params = array()) {
        
        //fetch an array of records [Eager Loading should be considered here]
        $orm_obj = \ORM::for_table($this->_table_name);
        
        $results = $orm_obj
                        ->where('hidden_fiscal_year', '16')
                        ->where('deactivated', '0')
                        ->where_null('parent_id')
                        ->find_array();
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
            $select_qry_obj = (new QueryFactory('mysql'))->newSelect();
            $select_qry_obj->cols(array('COUNT(*) AS num_of_matched_records'));
            $select_qry_obj->from($this->_table_name);
            
            //delete statement
            $del_qry_obj = (new QueryFactory('mysql'))->newDelete();
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
        
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchOne(array $params = array()) {
        
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchPairs(array $params = array()) {
        
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchValue(array $params = array()) {
        
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
            $select_qry_obj = (new QueryFactory('mysql'))->newSelect();
            $select_qry_obj->cols(array('COUNT(*) AS num_of_matched_records'));
            $select_qry_obj->from($this->_table_name);
            
            //update statement
            $update_qry_obj = (new QueryFactory('mysql'))->newUpdate();
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
}

class ModelBadWhereParamSuppliedException extends \Exception{}