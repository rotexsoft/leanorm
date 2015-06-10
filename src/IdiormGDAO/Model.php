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
        $pdo_driver_opts = array(),
        $extra_opts = array()
    ) {
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
                
            } elseif(is_string($e_opt_val)) {

                \ORM::configure($e_opt_val);
            }
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
        
        if( !empty($params) && count($params) > 0 ) {
            
            if( 
                (in_array('distinct', $allowed_keys) || count($allowed_keys) <= 0)
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
                //add distinct clause if specified
                
                if( is_array($params['cols']) ) {
                    
                    $select_qry_obj->cols($params['cols']);
                }
            }
            
            if( 
                (in_array('where', $allowed_keys) || count($allowed_keys) <= 0)
                && array_key_exists('where', $params)
            ) {
                //add distinct clause if specified
                
                if( is_array($params['where']) ) {
                    
                    //$select_qry_obj->cols($params['cols']);
                    //foreach ($params['where'] as $potential_or_operator => )
                }
            }
            
/*
            $select_qry_obj->cols(array('COUNT(*) AS num_of_matched_records'));
            $select_qry_obj->from($this->_table_name);

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
 */
        }
        return $select_qry_obj;
    }

    /**
     * 
     * @param array $where_params
     * @param \Aura\SqlQuery\Common\Select $select_qry_obj
     * 
     */
    protected function _addWhereConditions2Query( 
        array $where_params, \Aura\SqlQuery\Common\Select $select_qry_obj
    ) {

/*
 Algortithm for validating $where_params array

 $array = [
    'where' =>
      [
         [ 'col'=>'column_name_1', 'operator'=>'>', 'val'=>58 ],
         [ 'col'=>'column_name_2', 'operator'=>'>', 'val'=>58 ],
         'OR'=> [
                    [ 'col'=>'column_name_1', 'operator'=>'<', 'val'=>58 ],
                    [ 'col'=>'column_name_2', 'operator'=>'<', 'val'=>58 ]
                ],
         [ 'col'=>'column_name_3', 'operator'=>'>=', 'val'=>58 ],
         'OR#2'=> [
                    [ 'col'=>'column_name_4', 'operator'=>'=', 'val'=>58 ],
                    [ 'col'=>'column_name_5', 'operator'=>'=', 'val'=>58 ],
                ]
      ]
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveArrayIterator($array['where']),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($iterator as $key => $value) {

    if(is_array($value) && count($value) > 0) {

        //non-leaf node
    	echo "****PARENT***** $key => ".print_r($value,true), PHP_EOL;

    } else {

    	//leaf node
    	echo "====LEAF==== $key => $value", PHP_EOL;
    }

}
 
To validate the array loop through each recursive $key $value pair

The first key in the first iteration of the loop must !== 'OR' and not start with 'OR#'

if $key === 'col' then $value must be a string

else if $key === 'operator' then $value must be in valid expected operators

else if $key === 'val' must be either numeric, string or an array 

else if $key === 'OR' or starts with 'OR#' then $value must be an array whose first item's key (is not 'OR' or starts with 'OR#')

else if $key is numeric then $value must be an array that must be validated with the rules below:
    if count of the $value array is 2 check for these 2 keys 'col' & 'operator' & that the operator's value is not-in / in
    if count of the $value array is 3 check for these 3 keys 'col', 'operator' & 'val'
    if count of the $value array is > 3 throw an exception
 
else if $key does not match any of the preceeding  if .. else if tests then it has an invalid value

Other rules to consider
Logic to consider when generating where clause
===============================================
* Any array item with a key named 'col' must have a string value
* The operators: 'not-null' and 'is-null' do not need 'val' to be set.
* The operators: 'in' and 'not-in' allow 'val' to be set to an array or string value. 
	If 'val' is a string, it must be a valid
 value that a NOT IN or IN operator expects 
	including the opening
 and closing brackets. Eg. "( 1, 2, 3 )" or "( '4', '5', '6' )".
[ 
 '=', '>', '>=', '<', '<=', '!=',	-> 'val'=>string|number
 'in', 'not-in',                    -> 'val'=>string|array //string in the form of (....)
 'like', 'not-like',                -> 'val'=>string
 'is-null', 'not-null'              -> no val needed
]
  
 
USE https://github.com/rotexsoft/HandyPhpFunctions 
Rotexsoft\HandyPhpFunctions\recursively_copy_array($array_from, $array_to, true)
to ensure that numeric keys in $where_params are all properly numbered after 
validating $where_params based on the algorithm above.

$arr = [];
\Rotexsoft\HandyPhpFunctions\recursively_copy_array($where_params, $arr, true);
r($arr);
 
  */        

        if( !empty($where_params) && count($where_params) > 0 ) {
            
            foreach ( $where_params as $key => $value ) {
                
                // The exception check above guarantees that the first item in
                // $where_params does not have a key with a value of 'OR' or
                // prefix of 'OR#'
                if( 
                    $key === "OR" || substr($key, 0, 3) === "OR#"
                ) {

                    //Treat it as a condition to be ORed


                } else {

                    //Treat it as a condition to be ANDed
                    if(
                        is_array($value) 
                        && array_key_exists( 'col', $value)
                        && array_key_exists( 'operator', $value)
                    ) {
                        if(
                            !array_key_exists(
                                $value['operator'],
                                static::$_where_or_having_ops_to_mysql_ops
                            )   
                        ) {
                            //Badly structured where params array supplied.
                            $msg = 'Bad where param array supplied to '
                                    .get_class($this).'::'.__FUNCTION__.'(...) on line. '.__LINE__.PHP_EOL
                                    ."Unsupported 'operator' value of '{$value['operator']}' supplied in ".PHP_EOL
                                    .print_r($value, true).PHP_EOL
                                    .' in '.PHP_EOL
                                    .print_r($where_params, true);

                            throw new ModelBadWhereParamSuppliedException($msg);
                        }
                        
                        if(
                            !array_key_exists( 'val', $value)
                            && !in_array( $value['operator'], array('is-null',  'not-null') )
                        ) {
                            //Badly structured where params array supplied.
                            $msg = 'Bad where param array supplied to '
                                    .get_class($this).'::'.__FUNCTION__.'(...) on line. '.__LINE__.PHP_EOL
                                    .'missing \'val\' key in '.PHP_EOL
                                    .print_r($value, true).PHP_EOL
                                    .' in '.PHP_EOL
                                    .print_r($where_params, true);

                            throw new ModelBadWhereParamSuppliedException($msg);
                        }
                        
                        $mysql_operator = 
                            static::$_where_or_having_ops_to_mysql_ops[$value['operator']];
                        
                        if(
                            in_array(
                                $value['operator'], 
                                array('=', '!=', '>', '>=', '<', '<=', 'like', 'not-like')
                            )
                        ) {
                            $select_qry_obj->where(
                                " {$value['col']} {$mysql_operator} ? ", 
                                $value['val']
                            );
                            
                        } else if ( 
                            in_array(
                                $value['operator'], 
                                array('in', 'not-in') 
                            )
                        ) {
                            if ( is_array($value['val']) ) {
                                
                                //quote all string values
                                array_walk(
                                    $value['val'],
                                    function(&$val, $key, $pdo) {
                                        $val = (is_string($val)) ? $pdo->quote($val) : $val;
                                    },                     
                                    $this->getPDO()
                                );

                                $select_qry_obj->where(
                                    " {$value['col']} {$mysql_operator} (" . implode( ',', $value['val'] ) . ") "
                                );

                            } else {

                                //must be a string or numeric value
                                if( is_numeric($value['val']) ) {
                                    
                                    $select_qry_obj->where(" {$value['col']} {$mysql_operator} ( ? ) ", $value['val']);
                                    
                                } else {
                                    
                                    //a string in the form "(......)" expected
                                    $select_qry_obj->where(
                                        " {$value['col']} {$mysql_operator} " 
                                        . $this->getPDO()->quote($value['val'])
                                    );
                                }
                            }
                            
                        } else if (
                            in_array(
                                $value['operator'], 
                                array('is-null', 'not-null') 
                            )
                        ) {
                            $select_qry_obj->where( 
                                " {$value['col']} {$mysql_operator} " 
                            );
                        }
                        
                    } else {
                        
                        //Badly structured where params array supplied.
                        //Where params array cannot have any non-array values
                        //whose key is not any of 'col', 'operator' or 'val'. 
                        $msg = 'Bad where param array supplied to '
                                .get_class($this).'::'.__FUNCTION__.'(...) on line. '.__LINE__.PHP_EOL
                                .print_r($where_params, true);

                        throw new ModelBadWhereParamSuppliedException($msg);
                    }
                }               
            }
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchAll(array $params = array()) {
        
        //fetch a collection of records [Eager Loading should be considered here]        
        $orm_obj = \ORM::for_table($this->_table_name);
//*
$select_qry_obj = (new QueryFactory('mysql'))->newSelect();
$select_qry_obj
    ->distinct()                    // SELECT DISTINCT
    ->cols(array(                   // select these columns
        'id',                       // column name
        'name AS namecol',          // one way of aliasing
        'col_name' => 'col_alias',  // another way of aliasing
        'COUNT(foo) AS foo_count'   // embed calculations directly
    ))
    ->from('foo AS f')              // FROM these tables
    ->where(
"(
	column_name_1 > 58
	AND
	column_name_2 > 58
	OR
	(
		column_name_1 < 58
		AND
		column_name_2 < 58
	)
	AND
	column_name_3 >= 58
	OR
	(
		column_name_4 = 58
		AND
		column_name_5 = 58
	)
)"
            )           // AND WHERE these conditions
    ->where('bar > :bar')           // AND WHERE these conditions
    ->where('zim = ?', 'zim_val')   // bind 'zim_val' to the ? placeholder
    ->orWhere('( baz < :baz AND baz > :baz )')         // OR WHERE these conditions
    ->orWhere('( baf < ? )', 'baf_val')         // OR WHERE these conditions
    ->groupBy(array('dib'))         // GROUP BY these columns
    ->having('foo = :foo')          // AND HAVING these conditions
    ->having('bar > ?', 'bar_val')  // bind 'bar_val' to the ? placeholder
    ->orHaving('baz < :baz')        // OR HAVING these conditions
    ->orderBy(array('baz'))         // ORDER BY these columns
    ->limit(10)                     // LIMIT 10
    ->offset(40)                    // OFFSET 40
    ->bindValue('foo', 'foo_val')   // bind one value to a placeholder
    ->bindValues(array(             // bind these values to named placeholders
        'bar' => 'bar_val',
        'baz' => 'baz_val',
    ));
r($select_qry_obj->getBindValues());
r($select_qry_obj->__toString());exit;
//*/
        $results = $orm_obj
                        ->where('hidden_fiscal_year', '16')
                        ->where('deactivated', '0')
                        ->where_null('parent_id')
                        ->find_array();
        
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