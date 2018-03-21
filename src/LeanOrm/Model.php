<?php
namespace LeanOrm;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlSchema\ColumnFactory;

/**
 * 
 * Supported PDO drivers: mysql, pgsql, sqlite and sqlsrv
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2015, Rotimi Adegbamigbe
 */
class Model extends \GDAO\Model
{
    //overriden parent's properties
    /**
     * Name of the collection class for this model. 
     * Must be a descendant of \GDAO\Model\Collection
     * 
     * @var string 
     */
    protected $_collection_class_name = '\\LeanOrm\Model\\Collection';
    
    
    /**
     * Name of the record class for this model. 
     * Must be a descendant of \GDAO\Model\Record
     * 
     * @var string 
     */
    protected $_record_class_name = '\\LeanOrm\\Model\\Record';
    
    /////////////////////////////////////////////////////////////////////////////
    // Properties declared here are specific to \LeanOrm\Model and its kids //
    /////////////////////////////////////////////////////////////////////////////
    protected static $_valid_extra_opts_keys_4_dbconnector = array(
        'logging', //[NOT NEEDED: WILL IMPLEMENT QUERY LOGGING HERE]
        'logger',  //[NOT NEEDED: WILL IMPLEMENT QUERY LOGGING HERE]
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
     *  An object for interacting with the db
     * 
     * @var \LeanOrm\DBConnector
     */
    protected $_db_connector = null;
    

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
        $pri_col_not_set_exception = null;
        
        try {
            
            parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $extra_opts);
            
        } catch (\GDAO\ModelPrimaryColNameNotSetDuringConstructionException $e) {
            
            //$this->_primary_col (primary key colun has not yet been set)
            //hold this exception for later if necessary
            $pri_col_not_set_exception = $e;
        }
            
        DBConnector::configure($dsn, null, $dsn);//use $dsn as connection name in 3rd parameter
        DBConnector::configure('username', $username, $dsn);//use $dsn as connection name in 3rd parameter
        DBConnector::configure('password', $passwd, $dsn);//use $dsn as connection name in 3rd parameter
        
        if( count($pdo_driver_opts) > 0 ) {
            
            DBConnector::configure( 'driver_options', $pdo_driver_opts, $dsn);//use $dsn as connection name in 3rd parameter
        }
        
        foreach ($extra_opts as $e_opt_key => $e_opt_val) {

            if(
                is_string($e_opt_key) 
                && in_array($e_opt_key, static::$_valid_extra_opts_keys_4_dbconnector)
            ) {
                DBConnector::configure($e_opt_key, $e_opt_val, $dsn);//use $dsn as connection name in 3rd parameter
            }
        }
        
        $this->_db_connector = DBConnector::create($dsn);//use $dsn as connection name
        $this->_pdo_driver_name = $this->getPDO()
                                       ->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        ////////////////////////////////////////////////////////
        //Get and Set Table Schema Meta Data if Not Already Set
        ////////////////////////////////////////////////////////
        if ( empty($this->_table_cols) || count($this->_table_cols) <= 0 ) {
            
            static $dsn_n_tname_to_schema_def_map;
            
            if( !$dsn_n_tname_to_schema_def_map ) {
                
                $dsn_n_tname_to_schema_def_map = [];
            }
            
            $schema_definitions = [];
            
            if( array_key_exists($dsn.$this->_table_name, $dsn_n_tname_to_schema_def_map) ) {
                
                // use cached schema definition for the dsn and table name combo
                $schema_definitions = $dsn_n_tname_to_schema_def_map[$dsn.$this->_table_name];
                
            } else {
                // a column definition factory 
                $column_factory = new ColumnFactory();

                $schema_class_name = '\\Aura\\SqlSchema\\' 
                                     .ucfirst($this->_pdo_driver_name).'Schema';

                // the schema discovery object
                $schema = new $schema_class_name($this->getPDO(), $column_factory);

                $this->_table_cols = array();
                $schema_definitions = $schema->fetchTableCols($this->_table_name);
                
                // cache schema definition for the current dsn and table combo
                $dsn_n_tname_to_schema_def_map[$dsn.$this->_table_name] = $schema_definitions;
            }

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
                
                if( is_null($this->_primary_col) && $metadata_obj->primary ) {
                    
                    //this is a primary column
                    $this->_primary_col = $metadata_obj->name;
                }
            }
        }

        //if $this->_primary_col is still null at this point, throw an exception.
        if( is_null($this->_primary_col) ) {
            
            throw $pri_col_not_set_exception;
        }
        
        $table_cols = $this->getTableColNames();
        
        foreach($this->_relations as $relation_name=>$relation_info) {
        
            if( in_array($relation_name, $table_cols) ) {
                
                //Error trying to add a relation whose name collides with an actual
                //name of a column in the db table associated with this model.
                $msg = "ERROR: You cannont add a relationship with the name '$relation_name' "
                     . " to the Model (".get_class($this)."). The database table "
                     . " '{$this->getTableName()}' associated with the "
                     . " model (".get_class($this).") already contains"
                     . " a column with the same name."
                     . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                     . PHP_EOL;
                     
                throw new \GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException($msg);
            }
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function createNewCollection(\GDAO\Model\RecordsList $list_of_records, array $extra_opts=array()) {
        
        if( empty($this->_collection_class_name) ) {
         
            //default to creating new collection of type \LeanOrm\Model\Collection
            $collection = new \LeanOrm\Model\Collection($list_of_records, $this, $extra_opts);
            
        } else {
            
            $collection = new $this->_collection_class_name($list_of_records, $this, $extra_opts);
        }
        
        return $collection;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function createNewRecord(array $col_names_n_vals = array(), array $extra_opts=array()) {
        
        if( empty($this->_record_class_name) ) {
         
            //default to creating new record of type \LeanOrm\Model\Record
            $record = new \LeanOrm\Model\Record($col_names_n_vals, $this, $extra_opts);
            
        } else {
            
            $record = new $this->_record_class_name($col_names_n_vals, $this, $extra_opts);
        }
        
        return $record;
    }
    
    /**
     * 
     * @param array $params an array of parameters passed to a fetch*() method
     * @param array $disallowed_keys list of keys in $params not to be used to build the query object 
     * @param string $table_name name of the table to select from (will default to $this->_table_name if empty)
     * @return \Aura\SqlQuery\Common\Select or any of its descendants
     */
    protected function _buildFetchQueryObjectFromParams(
        array $params=array(), array $disallowed_keys=array(), $table_name=''
    ) {
        $select_qry_obj = (new QueryFactory($this->_pdo_driver_name))->newSelect();
        
        if( empty($table_name) ) {
            
            $select_qry_obj->from($this->_table_name);
            $table_name = $this->_table_name;
            
        } else {
            
            $select_qry_obj->from($table_name);
        }
        
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
                $select_qry_obj->cols(array(" {$table_name}.* "));
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
        } else {
            
            //defaults
            $select_qry_obj->cols(array(" {$table_name}.* "));
        }

        return $select_qry_obj;
    }
    
    public function getDefaultColVals() {
        
        $default_colvals = array();
        
        if( !empty($this->_table_cols) && count($this->_table_cols) > 0 ) {
                        
            foreach($this->_table_cols as $col_name => $col_metadata) {
                
                $default_colvals[$col_name] = $col_metadata['default'];
            }
        }
        
        return $default_colvals;
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
        if( !empty($where_params) && count($where_params) > 0 ) {
            
            if($this->_validateWhereOrHavingParamsArray($where_params)) {

                list($where_clause, $bind_params) = 
                    $this->_getWhereOrHavingClauseWithParams($where_params);
                                
                $select_qry_obj->where( $where_clause );

                if( count( $bind_params ) > 0 ) {
                    
                    $select_qry_obj->bindValues( $bind_params );
                }
            }
        }
    }

    /**
     * 
     * @param array $having_params
     * @param \Aura\SqlQuery\Common\Select $select_qry_obj
     * 
     */
    protected function _addHavingConditions2Query(
        array $having_params, \Aura\SqlQuery\Common\Select $select_qry_obj
    ) {
        if( !empty($having_params) && count($having_params) > 0 ) {
            
            if($this->_validateWhereOrHavingParamsArray($having_params)) {

                list($having_clause, $bind_params) =
                    $this->_getWhereOrHavingClauseWithParams($having_params);
                
                $select_qry_obj->having( $having_clause );
                
                if( count( $bind_params ) > 0 ) {
                    
                    $select_qry_obj->bindValues( $bind_params );
                }
            }
        }
    }
    
    public function loadRelationshipData($rel_name, &$parent_data, $wrap_each_row_in_a_record=false, $wrap_records_in_collection=false) {

        if( 
            array_key_exists($rel_name, $this->_relations) 
            && $this->_relations[$rel_name]['relation_type'] === \GDAO\Model::RELATION_TYPE_HAS_MANY 
        ) {
            $this->_loadHasMany($rel_name, $parent_data, $wrap_each_row_in_a_record, $wrap_records_in_collection);
            
        } else if (
            array_key_exists($rel_name, $this->_relations) 
            && $this->_relations[$rel_name]['relation_type'] === \GDAO\Model::RELATION_TYPE_HAS_MANY_THROUGH        
        ) {
            $this->_loadHasManyTrough($rel_name, $parent_data, $wrap_each_row_in_a_record, $wrap_records_in_collection);
            
        } else if (
            array_key_exists($rel_name, $this->_relations) 
            && $this->_relations[$rel_name]['relation_type'] === \GDAO\Model::RELATION_TYPE_HAS_ONE
        ) {
            $this->_loadHasOne($rel_name, $parent_data, $wrap_each_row_in_a_record);
            
        } else if (
            array_key_exists($rel_name, $this->_relations) 
            && $this->_relations[$rel_name]['relation_type'] === \GDAO\Model::RELATION_TYPE_BELONGS_TO
        ) {
            $this->_loadBelongsTo($rel_name, $parent_data, $wrap_each_row_in_a_record);
        }
    }
    
    protected function _validateRelatedCollectionAndRecordClassNames($collection_class_name, $record_class_name) {
        
        $parent_collection_class_name = '\GDAO\Model\CollectionInterface';
        $parent_record_class_name = '\GDAO\Model\RecordInterface';
    
        if( !is_subclass_of($collection_class_name, $parent_collection_class_name) ) {

            //throw exception
            $msg = "ERROR: '$collection_class_name' is not a subclass of "
                 . "'$parent_collection_class_name'. A collection class name specified"
                 . " for fetching related data must be the name of a class that"
                 . " is a sub-class of '$parent_collection_class_name'"
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new ModelBadCollectionClassNameForFetchingRelatedDataException($msg);
        }
        
        if( !is_subclass_of($record_class_name, $parent_record_class_name)  ) {

            //throw exception
            $msg = "ERROR: '$record_class_name' is not a subclass of "
                 . "'$parent_record_class_name'. A record class name specified for"
                 . " fetching related data must be the name of a class that"
                 . " is a sub-class of '$parent_record_class_name'"
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new ModelBadRecordClassNameForFetchingRelatedDataException($msg);
        }
        
        return true;
    }
    
    /**
     * 
     * @param string $rel_name
     * @return \GDAO\Model\CollectionInterface|array
     */
    protected function _loadHasMany( 
        $rel_name, &$parent_data, $wrap_each_row_in_a_record=false, $wrap_records_in_collection=false 
    ) {
        if( 
            array_key_exists($rel_name, $this->_relations) 
            && $this->_relations[$rel_name]['relation_type']  === \GDAO\Model::RELATION_TYPE_HAS_MANY 
        ) {
            list(
                $fkey_col_in_foreign_table, $foreign_models_record_class_name,
                $foreign_models_collection_class_name, $fkey_col_in_my_table, 
                $foreign_model_obj, $related_data
            ) = $this->_getBelongsToOrHasOneOrHasManyData($rel_name, $parent_data);
            
            $this->_validateRelatedCollectionAndRecordClassNames($foreign_models_collection_class_name, $foreign_models_record_class_name);
            
            /*
                -- BASIC SQL For Fetching the Related Data

                -- $parent_data is a collection or array of records    
                SELECT {$foreign_table_name}.*
                  FROM {$foreign_table_name}
                 WHERE {$foreign_table_name}.{$fkey_col_in_foreign_table} IN ( $fkey_col_in_my_table column values in $parent_data )

                -- OR

                -- $parent_data is a single record
                SELECT {$foreign_table_name}.*
                  FROM {$foreign_table_name}
                 WHERE {$foreign_table_name}.{$fkey_col_in_foreign_table} = {$parent_data->$fkey_col_in_my_table}
            */
            
            if ( 
                $parent_data instanceof \GDAO\Model\CollectionInterface
                || is_array($parent_data)
            ) {
                //stitch the related data to the approriate parent records
                foreach( $parent_data as $p_rec_key => $parent_record ) {

                    $matching_related_records = array();

                    \Rotexsoft\HandyPhpFunctions\search_2d(
                        $related_data,
                        $fkey_col_in_foreign_table, 
                        $parent_record[$fkey_col_in_my_table], 
                        $matching_related_records
                    );
                    
                    $this->_wrapRelatedDataInsideRecordsAndCollection(
                        $wrap_each_row_in_a_record, $matching_related_records, $foreign_models_record_class_name,
                        $foreign_model_obj, $wrap_records_in_collection, $foreign_models_collection_class_name
                    );

                    //set the related data for the current parent record
                    if( $parent_record instanceof \GDAO\Model\RecordInterface ) {

                        $parent_data[$p_rec_key]
                            ->setRelatedData($rel_name, $matching_related_records);

                    } else {

                        //the current record must be an array
                        $parent_data[$p_rec_key][$rel_name] = $matching_related_records;
                    }
                } //foreach( $parent_data as $p_rec_key => $parent_record )
        
            } else if ( $parent_data instanceof \GDAO\Model\RecordInterface ) {
                
                $this->_wrapRelatedDataInsideRecordsAndCollection(
                    $wrap_each_row_in_a_record, $related_data, $foreign_models_record_class_name,
                    $foreign_model_obj, $wrap_records_in_collection, $foreign_models_collection_class_name
                );
                
                //stitch the related data to the parent record
                $parent_data->setRelatedData($rel_name, $related_data);
            } // else if ($parent_data instanceof \GDAO\Model\RecordInterface)
        } // if( array_key_exists($rel_name, $this->_relations) )
    }
    
    /**
     * 
     * @param string $rel_name
     * @return \GDAO\Model\CollectionInterface|array
     */
    protected function _loadHasManyTrough( 
        $rel_name, &$parent_data, $wrap_each_row_in_a_record=false, $wrap_records_in_collection=false 
    ) {
        if( 
            array_key_exists($rel_name, $this->_relations) 
            && $this->_relations[$rel_name]['relation_type']  === \GDAO\Model::RELATION_TYPE_HAS_MANY_THROUGH
        ) {
            $array_get = '\\Rotexsoft\\HandyPhpFunctions\\array_get';
            
            $rel_info = $this->_relations[$rel_name];

            $foreign_table_name = $array_get($rel_info, 'foreign_table');
            
            $fkey_col_in_foreign_table = 
                $array_get($rel_info, 'col_in_foreign_table_linked_to_join_table');
            
            $foreign_models_class_name = 
                $array_get($rel_info, 'foreign_models_class_name', '\\LeanOrm\\Model');
            
            $foreign_models_record_class_name = 
                $array_get($rel_info, 'foreign_models_record_class_name', '\\LeanOrm\\Model\\Record');
            
            $foreign_models_collection_class_name = 
                $array_get($rel_info, 'foreign_models_collection_class_name', '\\LeanOrm\Model\\Collection');
            
            $this->_validateRelatedCollectionAndRecordClassNames($foreign_models_collection_class_name, $foreign_models_record_class_name);
            
            $pri_key_col_in_foreign_models_table = 
                $array_get($rel_info, 'primary_key_col_in_foreign_table');
            
            $fkey_col_in_my_table = 
                    $array_get($rel_info, 'col_in_my_table_linked_to_join_table');
            
            //join table params
            $join_table_name = $array_get($rel_info, 'join_table');
            
            $col_in_join_table_linked_to_my_models_table = 
                $array_get($rel_info, 'col_in_join_table_linked_to_my_table');
            
            $col_in_join_table_linked_to_foreign_models_table = 
                $array_get($rel_info, 'col_in_join_table_linked_to_foreign_table');
            
            $foreign_models_table_sql_params = 
                    $array_get($rel_info, 'foreign_table_sql_params', array());
            
            $extra_opts_for_foreign_model = 
                    $array_get($rel_info, 'extra_opts_for_foreign_model', array());
            
            $query_obj = 
                $this->_buildFetchQueryObjectFromParams(
                            $foreign_models_table_sql_params, 
                            array('relations_to_include', 'limit_offset', 'limit_size'), 
                            $foreign_table_name
                        );
            
            $query_obj->cols( array(" {$join_table_name}.{$col_in_join_table_linked_to_my_models_table} ") );
            
            $query_obj->innerJoin(
                            $join_table_name, 
                            " {$join_table_name}.{$col_in_join_table_linked_to_foreign_models_table} = {$foreign_table_name}.{$fkey_col_in_foreign_table} "
                        );
            
            if ( $parent_data instanceof \GDAO\Model\RecordInterface ) {
                
                $where_cond = " {$join_table_name}.{$col_in_join_table_linked_to_my_models_table} = "
                     . Utils::quoteStrForQuery($this->getPDO(), $parent_data->$fkey_col_in_my_table);
                
                $query_obj->where($where_cond);
                
            } else {
                
                //assume it's a collection or array
                $col_vals = $this->_getPdoQuotedColValsFromArrayOrCollection(
                                        $parent_data, $fkey_col_in_my_table
                                    );

                if( count($col_vals) > 0 ) {
                    
                    $where_cond = " {$join_table_name}.{$col_in_join_table_linked_to_my_models_table} IN ("
                                . implode( ',', $col_vals )
                                . ")";
                    
                    $query_obj->where($where_cond);
                }
            }
            
            $foreign_model_obj = $this->_createRelatedModelObject(
                                            $foreign_models_class_name,
                                            $pri_key_col_in_foreign_models_table,
                                            $foreign_table_name,
                                            $extra_opts_for_foreign_model
                                        );
            
            $params_2_bind_2_sql = $query_obj->getBindValues();
            $sql_2_get_related_data = $query_obj->__toString();
/*
-- SQL For Fetching the Related Data

-- $parent_data is a collection or array of records    
SELECT {$foreign_table_name}.*,
       {$join_table_name}.{$col_in_join_table_linked_to_my_models_table}
  FROM {$foreign_table_name}
  JOIN {$join_table_name} ON {$join_table_name}.{$col_in_join_table_linked_to_foreign_models_table} = {$foreign_table_name}.{$fkey_col_in_foreign_table}
 WHERE {$join_table_name}.{$col_in_join_table_linked_to_my_models_table} IN ( $fkey_col_in_my_table column values in $parent_data )

OR

-- $parent_data is a single record
SELECT {$foreign_table_name}.*,
       {$join_table_name}.{$col_in_join_table_linked_to_my_models_table}
  FROM {$foreign_table_name}
  JOIN {$join_table_name} ON {$join_table_name}.{$col_in_join_table_linked_to_foreign_models_table} = {$foreign_table_name}.{$fkey_col_in_foreign_table}
 WHERE {$join_table_name}.{$col_in_join_table_linked_to_my_models_table} = {$parent_data->$fkey_col_in_my_table}
*/
            //GRAB DA RELATED DATA
            $related_data = 
                $this->_db_connector
                     ->dbFetchAll($sql_2_get_related_data, $params_2_bind_2_sql);

            if ( 
                $parent_data instanceof \GDAO\Model\CollectionInterface
                || is_array($parent_data)
            ) {
                //stitch the related data to the approriate parent records
                foreach( $parent_data as $p_rec_key => $parent_record ) {

                    $matching_related_records = array();

                    \Rotexsoft\HandyPhpFunctions\search_2d(
                        $related_data,
                        $col_in_join_table_linked_to_my_models_table, 
                        $parent_record[$fkey_col_in_my_table], 
                        $matching_related_records
                    );

                    $this->_wrapRelatedDataInsideRecordsAndCollection(
                        $wrap_each_row_in_a_record, $matching_related_records, $foreign_models_record_class_name,
                        $foreign_model_obj, $wrap_records_in_collection, $foreign_models_collection_class_name
                    );

                    //set the related data for the current parent record
                    if( $parent_record instanceof \GDAO\Model\RecordInterface ) {

                        $parent_data[$p_rec_key]
                            ->setRelatedData($rel_name, $matching_related_records);
                    } else {

                        //the current record must be an array
                        $parent_data[$p_rec_key][$rel_name] = $matching_related_records;
                    }
                } //foreach( $parent_data as $p_rec_key => $parent_record )
        
            } else if ( $parent_data instanceof \GDAO\Model\RecordInterface ) {

                $this->_wrapRelatedDataInsideRecordsAndCollection(
                    $wrap_each_row_in_a_record, $related_data, $foreign_models_record_class_name,
                    $foreign_model_obj, $wrap_records_in_collection, $foreign_models_collection_class_name
                );
                
                //stitch the related data to the parent record
                $parent_data->setRelatedData($rel_name, $related_data);
            } // else if ( $parent_data instanceof \GDAO\Model\RecordInterface )
        } // if( array_key_exists($rel_name, $this->_relations) )
    }
    
    /**
     * 
     * @param string $rel_name
     * @return \GDAO\Model\RecordInterface|array
     */
    protected function _loadHasOne( 
        $rel_name, &$parent_data, $wrap_row_in_a_record=false
    ) {
        if( 
            array_key_exists($rel_name, $this->_relations) 
            && $this->_relations[$rel_name]['relation_type']  === \GDAO\Model::RELATION_TYPE_HAS_ONE
        ) {
            list(
                $fkey_col_in_foreign_table, $foreign_models_record_class_name,
                $foreign_models_collection_class_name, $fkey_col_in_my_table, 
                $foreign_model_obj, $related_data
            ) = $this->_getBelongsToOrHasOneOrHasManyData($rel_name, $parent_data);
            
            $this->_validateRelatedCollectionAndRecordClassNames($foreign_models_collection_class_name, $foreign_models_record_class_name);
/*
-- SQL For Fetching the Related Data

-- $parent_data is a collection or array of records    
SELECT {$foreign_table_name}.*
  FROM {$foreign_table_name}
 WHERE {$foreign_table_name}.{$fkey_col_in_foreign_table} IN ( $fkey_col_in_my_table column values in $parent_data )

OR

-- $parent_data is a single record
SELECT {$foreign_table_name}.*
  FROM {$foreign_table_name}
 WHERE {$foreign_table_name}.{$fkey_col_in_foreign_table} = {$parent_data->$fkey_col_in_my_table}
*/
            //re-key related data on the foreign key column values
            $related_data = 
                array_combine(
                    array_column($related_data, $fkey_col_in_foreign_table), 
                    $related_data
                );

            if ( 
                $parent_data instanceof \GDAO\Model\CollectionInterface
                || is_array($parent_data)
            ) {
                //stitch the related data to the approriate parent records
                foreach( $parent_data as $p_rec_key => $parent_record ) {

                    if( isset($related_data[$parent_record[$fkey_col_in_my_table]]) ) {
                        $matching_related_record = 
                            array($related_data[$parent_record[$fkey_col_in_my_table]]);

                        $this->_wrapRelatedDataInsideRecordsAndCollection(
                                    $wrap_row_in_a_record, $matching_related_record, 
                                    $foreign_models_record_class_name, 
                                    $foreign_model_obj, false, ''
                                );

                        //set the related data for the current parent record
                        if( $parent_record instanceof \GDAO\Model\RecordInterface ) {

                            $parent_data[$p_rec_key]
                                ->setRelatedData($rel_name, $matching_related_record[0]);

                        } else {

                            //the current record must be an array
                            $parent_data[$p_rec_key][$rel_name] = $matching_related_record[0];
                        }
                    }
                } //foreach( $parent_data as $p_rec_key => $parent_record )
        
            } else if ( $parent_data instanceof \GDAO\Model\RecordInterface ) {

                $this->_wrapRelatedDataInsideRecordsAndCollection(
                            $wrap_row_in_a_record, $related_data, 
                            $foreign_models_record_class_name,
                            $foreign_model_obj, false, ''
                        );
                
                //stitch the related data to the parent record
                $parent_data->setRelatedData($rel_name, array_shift($related_data));
            } // else if ($parent_data instanceof \GDAO\Model\RecordInterface)
        } // if( array_key_exists($rel_name, $this->_relations) )
    }
    
    /**
     * 
     * @param string $rel_name
     * @return \GDAO\Model\RecordInterface|array
     */
    protected function _loadBelongsTo($rel_name, &$parent_data, $wrap_row_in_a_record=false) {
        
        if( 
            array_key_exists($rel_name, $this->_relations) 
            && $this->_relations[$rel_name]['relation_type']  === \GDAO\Model::RELATION_TYPE_BELONGS_TO
        ) {
            
            //quick hack
            $this->_relations[$rel_name]['relation_type'] = \GDAO\Model::RELATION_TYPE_HAS_ONE;
            
            //I really don't see the difference in the sql to fetch data for
            //a has-one relationship and a belongs-to relationship. Hence, I
            //have resorted to using the same code to satisfy both relationships
            $this->_loadHasOne($rel_name, $parent_data, $wrap_row_in_a_record);
            
            //undo quick hack
            $this->_relations[$rel_name]['relation_type'] = \GDAO\Model::RELATION_TYPE_BELONGS_TO;
        }
    }
    
    
    protected function _getBelongsToOrHasOneOrHasManyData($rel_name, &$parent_data) {
        
        $array_get = '\\Rotexsoft\\HandyPhpFunctions\\array_get';

        $rel_info = $this->_relations[$rel_name];

        $foreign_table_name = $array_get($rel_info, 'foreign_table');

        $fkey_col_in_foreign_table = 
            $array_get($rel_info, 'foreign_key_col_in_foreign_table');

        $foreign_models_class_name = 
            $array_get($rel_info, 'foreign_models_class_name', "\\LeanOrm\\Model");

        $foreign_models_record_class_name = 
            $array_get($rel_info, 'foreign_models_record_class_name', "\\LeanOrm\\Model\\Record");

        $foreign_models_collection_class_name = 
            $array_get($rel_info, 'foreign_models_collection_class_name', "\\LeanOrm\Model\\Collection");

        $pri_key_col_in_foreign_models_table = 
            $array_get($rel_info, 'primary_key_col_in_foreign_table');

        $fkey_col_in_my_table = 
                $array_get($rel_info, 'foreign_key_col_in_my_table');

        $foreign_models_table_sql_params = 
                $array_get($rel_info, 'foreign_table_sql_params', array());

        $extra_opts_for_foreign_model = 
                $array_get($rel_info, 'extra_opts_for_foreign_model', array());

        $query_obj = 
            $this->_buildFetchQueryObjectFromParams(
                        $foreign_models_table_sql_params, 
                        array('relations_to_include', 'limit_offset', 'limit_size'), 
                        $foreign_table_name
                    );

        if ( $parent_data instanceof \GDAO\Model\RecordInterface ) {

            $where_cond = " {$foreign_table_name}.{$fkey_col_in_foreign_table} = "
                        . Utils::quoteStrForQuery($this->getPDO(), $parent_data->$fkey_col_in_my_table);
            $query_obj->where($where_cond);

        } else {
            //assume it's a collection or array                
            $col_vals = $this->_getPdoQuotedColValsFromArrayOrCollection(
                                    $parent_data, $fkey_col_in_my_table
                                );

            if( count($col_vals) > 0 ) {

                $where_cond = " {$foreign_table_name}.{$fkey_col_in_foreign_table} IN ("
                            . implode( ',', $col_vals )
                            . ")";
                $query_obj->where($where_cond);
            }
        }

        $foreign_model_obj = $this->_createRelatedModelObject(
                                        $foreign_models_class_name,
                                        $pri_key_col_in_foreign_models_table,
                                        $foreign_table_name,
                                        $extra_opts_for_foreign_model
                                    );
        $params_2_bind_2_sql = $query_obj->getBindValues();
        $sql_2_get_related_data = $query_obj->__toString();

        //GRAB DA RELATED DATA
        $related_data = 
            $this->_db_connector
                 ->dbFetchAll($sql_2_get_related_data, $params_2_bind_2_sql);

        return array(
            $fkey_col_in_foreign_table, $foreign_models_record_class_name,
            $foreign_models_collection_class_name, $fkey_col_in_my_table, 
            $foreign_model_obj, $related_data
        ); 
    }

    protected function _createRelatedModelObject(
        $f_models_class_name, 
        $pri_key_col_in_f_models_table, 
        $f_table_name,
        $extra_opts_for_foreign_model
    ) {
        //$foreign_models_class_name will never be empty it will default to \LeanOrm\Model
        //$foreign_table_name will never be empty because it is needed for fetching the 
        //related data
        
        if( empty($f_models_class_name) ) {
            
            $f_models_class_name = '\LeanOrm\Model';
        }

        //$pri_key_col_in_foreign_models_table could be empty and if it is then 
        //we'll have to look it up via the schema
        
        if( empty($pri_key_col_in_f_models_table) ) {

            ////////////////////////////////////////////////////////
            //Search for the primary key column via schema meta data
            ////////////////////////////////////////////////////////

            // a column definition factory 
            $column_factory = new ColumnFactory();
            $schema_class_name = '\\Aura\\SqlSchema\\' 
                                 .ucfirst($this->_pdo_driver_name).'Schema';

            // the schema discovery object
            $schema = new $schema_class_name($this->getPDO(), $column_factory);
            $schema_definitions = $schema->fetchTableCols($f_table_name);

            foreach( $schema_definitions as $colname => $metadata_obj ) {
                
                if( $metadata_obj->primary ) {
                    
                    //Yay! we found the primary key col for table $f_table_name
                    $pri_key_col_in_f_models_table = $colname;
                    break;
                }
            }
        }
        
        $merged_extra_opts = array_merge(
            array(
                '_primary_col' => $pri_key_col_in_f_models_table,
                '_table_name' => $f_table_name
            ),
            $extra_opts_for_foreign_model
        );
        
        if(
            !empty($pri_key_col_in_f_models_table)
        ) {
            //try to create a model object for the related data
            return new $f_models_class_name(
                $this->_dsn, 
                $this->_username, 
                $this->_passwd, 
                $this->_pdo_driver_opts,
                $merged_extra_opts
            );
        } else {
            
            $msg = "ERROR: Couldn't create foreign model of type '$f_models_class_name'."
                 . "  No primary key supplied for the database table '$f_table_name'"
                 . " associated with the foreign table class '$f_models_class_name'."
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new ModelRelatedModelNotCreatedException($msg);
        }
        
        return null;
    }
    
    protected function _getPdoQuotedColValsFromArrayOrCollection(
        &$parent_data, $fkey_col_in_my_table
    ) {
        $col_vals = array();
        
        if(
            $parent_data instanceof \GDAO\Model\CollectionInterface
            || is_array($parent_data)
        ) {
            if ( is_array($parent_data) ) {

                foreach($parent_data as $data) {

                    $col_vals[] = $data[$fkey_col_in_my_table];
                }

            } else {

                $col_vals = $parent_data->getColVals($fkey_col_in_my_table);
            }

            if( count($col_vals) > 0 ) {

                $pdo = $this->getPDO();
                $col_vals = array_unique($col_vals);

                foreach ( $col_vals as $key=>$val ) {

                    $col_vals[$key] = \LeanOrm\Utils::quoteStrForQuery($pdo, $val);
                }
            }
        }
        
        return $col_vals;
    }
    
    protected function _wrapRelatedDataInsideRecordsAndCollection(
        $wrap_each_row_in_a_record, &$matching_related_records, $foreign_models_record_class_name,
        $foreign_model_obj, $wrap_records_in_collection, $foreign_models_collection_class_name
    ) {
        if( $wrap_each_row_in_a_record ) {

            //wrap into records of the appropriate class
            foreach ($matching_related_records as $key=>$rec_data) {

                $matching_related_records[$key] = 
                    new $foreign_models_record_class_name(
                        $rec_data, 
                        $foreign_model_obj, 
                        array('is_new'=>false)
                    );
            }
        }

        if($wrap_records_in_collection) {

            //wrap into a collection object
            $matching_related_records = 
                new $foreign_models_collection_class_name (
                    new \GDAO\Model\RecordsList( $matching_related_records ), 
                    $foreign_model_obj
                );
        }
    }
    
    /**
     * 
     * Fetches a record or collection by primary key value(s).
     * 
     * If $ids holds a single scalar value, always return the db row whose primary
     * key value matches the scalar value.
     * 
     * If $ids is an array of scalar values:
     * 
     *      # `$use_collections === true`: return a \LeanOrm\Model\Collection of 
     *        \LeanOrm\Model\Record records each matching the values in $ids
     * 
     *      # `$use_collections === false`:
     * 
     *          - `$use_records === true`: return an array of \LeanOrm\Model\Record 
     *            records each matching the values in $ids
     * 
     *          - `$use_records === false`: return an array of rows (each row being
     *            an associative array) each matching the values in $ids
     * 
     * @param mixed|array $ids scalar primary key field value of a single db 
     *                       record to be fetched or an array of scalar values 
     *                       of the primary key field of db rows to be fetched
     * 
     * @param array $params see documentation of fetchRecordsIntoCollection for 
     *                      the description of the structure of this parameter 
     * 
     * @param bool $use_records true if each matched db row should be wrapped in 
     *                          an instance of \LeanOrm\Model\Record; false if 
     *                          rows should be returned as associative php 
     *                          arrays
     * 
     * @param bool $use_collections true if each matched db row should be wrapped
     *                              in an instance of \LeanOrm\Model\Record and 
     *                              all the records wrapped in an instance of
     *                              \LeanOrm\Model\Collection; false if all 
     *                              matched db rows should be returned in a
     *                              php array
     * 
     * @return array|\LeanOrm\Model\Record|\LeanOrm\Model\Collection Description
     * 
     */
    public function fetch($ids, array $params=array(), $use_records=false, $use_collections=false, $use_p_k_val_as_key=false) {
                
        if( !array_key_exists('where', $params) ) {
            
            $params['where'] = array();
        }
        
        if( is_array($ids) ) {
            
            $params['where'][] = 
                array( 'col'=>$this->getPrimaryColName(), 'op'=>'in', 'val'=>$ids );
            
            if( $use_collections ) {
                
                return ($use_p_k_val_as_key) 
                            ? $this->fetchRecordsIntoCollectionKeyedOnPkVal($params) 
                            : $this->fetchRecordsIntoCollection($params);
                
            } else {
                
                if( $use_records ) {
                    
                    return ($use_p_k_val_as_key) 
                            ? $this->fetchRecordsIntoArrayKeyedOnPkVal($params) 
                            : $this->fetchRecordsIntoArray($params);
                }
                
                //default
                return ($use_p_k_val_as_key) 
                            ? $this->fetchRowsIntoArrayKeyedOnPkVal($params) 
                            : $this->fetchRowsIntoArray($params);
            }
            
        } else {
            
            //assume it's a scalar value, string, int , etc
            $params['where'][] = 
                array( 'col'=>$this->getPrimaryColName(), 'op'=>'=', 'val'=>$ids );

            return $this->fetchOneRecord($params);
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchRecordsIntoCollection(array $params = array()) {

        return $this->doFetchRecordsIntoCollection($params);
    }
    
    public function fetchRecordsIntoCollectionKeyedOnPkVal(array $params = array()) {

        return $this->doFetchRecordsIntoCollection($params, true);
    }
    
    protected function doFetchRecordsIntoCollection(array $params = array(), $use_p_k_val_as_key=false) {
        
        $results = false;
        $data = $this->_getArrayOfRecordObjects($params, $use_p_k_val_as_key);
        
        if($data !== false && is_array($data) && count($data) > 0 ) {
        
            $results = $this->createNewCollection(new \GDAO\Model\RecordsList($data));

            if( array_key_exists('relations_to_include', $params) ) {

                foreach( $params['relations_to_include'] as $rel_name ) {

                    $this->loadRelationshipData($rel_name, $results, true, true);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchRecordsIntoArray(array $params = array()) {
        
        return $this->doFetchRecordsIntoArray($params);
    }
    
    public function fetchRecordsIntoArrayKeyedOnPkVal(array $params = array()) {
        
        return $this->doFetchRecordsIntoArray($params, true);
    }
    
    protected function doFetchRecordsIntoArray(array $params = array(), $use_p_k_val_as_key=false) {
        
        $results = $this->_getArrayOfRecordObjects($params, $use_p_k_val_as_key);

        if( $results !== false && is_array($results) && count($results) > 0 ) {
            
            if( array_key_exists('relations_to_include', $params) ) {

                foreach( $params['relations_to_include'] as $rel_name ) {

                    $this->loadRelationshipData($rel_name, $results, true);
                }
            }
        }
        
        return $results;
    }
    
    protected function _getArrayOfRecordObjects($params, $use_p_k_val_as_key=false) {

        $results = $this->_getArrayOfDbRows($params, $use_p_k_val_as_key);
        
        if( $results !== false && is_array($results) ) {
         
            foreach ($results as $key=>$value) {

                $results[$key] = $this->createNewRecord($value, array('is_new'=>false));
            }
        }
        
        return $results;
    }
    
    protected function _getArrayOfDbRows($params, $use_p_k_val_as_key=false) {
        
        $query_obj = $this->_buildFetchQueryObjectFromParams($params);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();
        
        $results = $this->_db_connector->dbFetchAll($sql, $params_2_bind_2_sql);
        
        if( $use_p_k_val_as_key && is_array($results) && count($results) > 0 && !empty($this->_primary_col) ) {
            
            $results_keyed_by_pk = [];
            
            foreach( $results as $result ) {
                
                if( !array_key_exists($this->_primary_col, $result) ) {
                    
                    $msg = "ERROR: Can't key fetch results by Primary Key value."
                         . PHP_EOL . " One or more result rows has no Primary Key field (`{$this->_primary_col}`)" 
                         . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).'
                         . PHP_EOL . 'Fetch Results:' . PHP_EOL . var_export($results, true) . PHP_EOL
                         . PHP_EOL . "Row without Primary Key field (`{$this->_primary_col}`):" . PHP_EOL . var_export($result, true) . PHP_EOL;
                    
                    throw new \LeanOrm\KeyingFetchResultsByPrimaryKeyFailedException($msg);
                }
                
                // key on primary key value
                $results_keyed_by_pk[$result[$this->_primary_col]] = $result;
            }
            
            $results = $results_keyed_by_pk;
        }

        return $results;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchRowsIntoArray(array $params = array()) {

        return $this->doFetchRowsIntoArray($params);
    }
    
    public function fetchRowsIntoArrayKeyedOnPkVal(array $params = array()) {

        return $this->doFetchRowsIntoArray($params, true);
    }
    
    protected function doFetchRowsIntoArray(array $params = array(), $use_p_k_val_as_key=false) {
        
        $results = $this->_getArrayOfDbRows($params, $use_p_k_val_as_key);

        if( $results !== false && is_array($results) && count($results) > 0 ) {
            
            if( array_key_exists('relations_to_include', $params) ) {

                foreach( $params['relations_to_include'] as $rel_name ) {

                    $this->loadRelationshipData($rel_name, $results);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * 
     * @return PDO
     */
    public function getPDO() {
        
        return DBConnector::getDb($this->_dsn); //return pdo object associated with
                                                //the current dsn
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function deleteMatchingDbTableRows(array $cols_n_vals=array()) {
        
        $result = null;
        
        if ( !empty($cols_n_vals) && count($cols_n_vals) > 0 ) {
            
            //delete statement
            $del_qry_obj = (new QueryFactory($this->_pdo_driver_name))->newDelete();
            $del_qry_obj->from($this->_table_name);

            foreach ($cols_n_vals as $colname => $colval) {

                if (is_array($colval)) {
                    
                    $colval = array_unique($colval);
                    
                    //quote all string values
                    array_walk(
                        $colval,
                        function(&$val, $key, $pdo) {
                            $val = \LeanOrm\Utils::quoteStrForQuery($pdo, $val);
                        },                     
                        $this->getPDO()
                    );
                        
                    $del_qry_obj->where("{$colname} IN (" . implode(',', $colval) . ") ");
                    
                } else {

                    $del_qry_obj->where("{$colname} = ?", $colval);
                }
            }
            
            $dlt_qry = $del_qry_obj->__toString();
            $dlt_qry_params = $del_qry_obj->getBindValues();

            $result = $this->_db_connector->executeQuery($dlt_qry, $dlt_qry_params, true); 

            if( $result[0] === true ) {
                
                //return number of affected rows
                $pdo_statement_used_for_query = $result[1];
                $result = $pdo_statement_used_for_query->rowCount();

            } else {
                
                //return boolean result of the \PDOStatement::execute() call
                //from $this->_db_connector->executeQuery($dlt_qry, $dlt_qry_params, true);
                $result = $result[0];
            }
        }

        return $result;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function deleteSpecifiedRecord(\GDAO\Model\RecordInterface $record) {
        
        //$this->_primary_col should have a valid value because a
        //GDAO\ModelPrimaryColNameNotSetDuringConstructionException
        //is thrown in $this->__construct() if $this->_primary_col is not set.
        $succesfully_deleted = null;
        
        if( $record instanceof \LeanOrm\Model\ReadOnlyRecord ) {

            $msg = "ERROR: Can't delete ReadOnlyRecord from the database in " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).'
                 . PHP_EOL .'Undeleted record' . var_export($record, true) . PHP_EOL;
            throw new \LeanOrm\CantDeleteReadOnlyRecordFromDBException($msg);
        }
        
        if ( count($record) > 0 ) { //test if the record object has data
            
            $pri_key_val = $record->getPrimaryVal();
            $cols_n_vals = array($this->getPrimaryColName() => $pri_key_val);

            $succesfully_deleted = 
                $this->deleteMatchingDbTableRows($cols_n_vals);

            if ( $succesfully_deleted === 1 ) {

                $record->setStateToNew();
            }
        }
        
        return ( $succesfully_deleted === 1 )? true : $succesfully_deleted;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchCol(array $params = array()) {
        
        if( 
            array_key_exists('cols', $params) 
            && ( !is_array($params['cols']) || count($params['cols']) < 1 )
        ) {
            //throw Exception
            $msg = "ERROR: Bad param entry. Array containing at least 1 string "
                 . "value expected as the value of the item with the key named 'cols' ."
                 . PHP_EOL . var_export($params, true) . PHP_EOL
                 . " passed to " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new ModelBadFetchParamsSuppliedException($msg);
        }

        $query_obj = $this->_buildFetchQueryObjectFromParams($params);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        return $this->_db_connector->dbFetchCol($sql, $params_2_bind_2_sql);
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchOneRecord(array $params = array()) {

        $param_keys_2_exclude = array('limit_offset', 'limit_size');
        
        $query_obj = 
            $this->_buildFetchQueryObjectFromParams($params, $param_keys_2_exclude);
        $query_obj->limit(1);
        
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $result = $this->_db_connector->dbFetchOne($sql, $params_2_bind_2_sql);

        if( $result !== false && is_array($result) && count($result) > 0 ) {
            
            $result = $this->createNewRecord($result, array('is_new'=>false));
            
            if( array_key_exists('relations_to_include', $params) ) {

                foreach( $params['relations_to_include'] as $rel_name ) {

                    $this->loadRelationshipData($rel_name, $result, true, true);
                }
            }
        }
        
        return $result;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchPairs(array $params = array()) {

        if( 
            array_key_exists('cols', $params) 
            && ( !is_array($params['cols']) || count($params['cols']) < 2 )
        ) {
            //throw Exception
            $msg = "ERROR: Bad param entry. Array (with at least two items) "
                 . "expected as the value of the item with the key named 'cols'"
                 . " in the array: "
                 . PHP_EOL . var_export($params, true) . PHP_EOL
                 . " passed to " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new ModelBadFetchParamsSuppliedException($msg);
        }

        $query_obj = $this->_buildFetchQueryObjectFromParams($params);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        return $this->_db_connector->dbFetchPairs($sql, $params_2_bind_2_sql);
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchValue(array $params = array()) {
        
        $param_keys_2_exclude = array('limit_offset', 'limit_size');
        
        $query_obj = 
            $this->_buildFetchQueryObjectFromParams($params, $param_keys_2_exclude);
        $query_obj->limit(1);
        
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $result = $this->_db_connector->dbFetchValue($sql, $params_2_bind_2_sql);
        
        //need to issue a second query to get the number of matching rows
        $params['cols'] = array(' COUNT(*) AS num_rows');
        $query_obj_4_num_matching_rows =
                $this->_buildFetchQueryObjectFromParams($params, $param_keys_2_exclude);
        
        $sql = $query_obj_4_num_matching_rows->__toString();
        $params_2_bind_2_sql = $query_obj_4_num_matching_rows->getBindValues();
        
        $num_matching_rows = $this->_db_connector->dbFetchOne($sql, $params_2_bind_2_sql);
        
        //return null if there wasn't any matching row
        return ( intval($num_matching_rows['num_rows']) > 0)? $result : null;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function insert(array $data_2_insert = array()) {

        $result = false;

        if ( 
            !empty($data_2_insert) && count($data_2_insert) > 0 
        ) {
            $table_cols = $this->getTableColNames();
            $time_created_colname = $this->_created_timestamp_column_name;
            $last_updated_colname = $this->_updated_timestamp_column_name;
            
            if(
                !empty($time_created_colname) 
                && in_array($time_created_colname, $table_cols)
            ) {
                //set created timestamp to now
                $data_2_insert[$time_created_colname] = date('Y-m-d H:i:s');
            }
            
            if(
                !empty($last_updated_colname) 
                && in_array($last_updated_colname, $table_cols)
            ) {
                //set last updated timestamp to now
                $data_2_insert[$last_updated_colname] = date('Y-m-d H:i:s');
            }
            
            // remove non-existent table columns from the data
            foreach ($data_2_insert as $key => $val) {
                
                if ( !in_array($key, $table_cols) ) {
                    
                    unset($data_2_insert[$key]);
                    // not in the table, so no need to check for autoinc
                    continue;
                }
                
                // Code below was lifted from Solar_Sql_Model::insert()
                // remove empty autoinc columns to soothe postgres, which won't
                // take explicit NULLs in SERIAL cols.
                if ( $this->_table_cols[$key]['autoinc'] && empty($val) ) {
                    
                    unset($data_2_insert[$key]);
                }
            }
            
            $has_autoinc_pkey_col = false;
            
            foreach($this->_table_cols as $col_name=>$col_info) {
                
                if ( $col_info['autoinc'] === true && $col_info['primary'] === true ) {
                    
                    if(array_key_exists($col_name, $data_2_insert)) {

                        //no need to add primary key value to the insert 
                        //statement since the column is auto incrementing
                        unset($data_2_insert[$col_name]);
                    }
                    
                    $has_autoinc_pkey_col = true;
                }
            }
            
            //Insert statement
            $insrt_qry_obj = (new QueryFactory($this->_pdo_driver_name))->newInsert();
            $insrt_qry_obj->into($this->_table_name)->cols($data_2_insert);
            
            $insrt_qry_sql = $insrt_qry_obj->__toString();
            $insrt_qry_params = $insrt_qry_obj->getBindValues();

            foreach ( $insrt_qry_params as $key => $param ) {
                
                if(
                    !is_bool($param) 
                    && !is_null($param) 
                    && !is_numeric($param)
                    && !is_string($param)
                ) {
                    $msg = "ERROR: the value "
                         . PHP_EOL . var_export($param, true) . PHP_EOL
                         . " you are trying to insert into {$this->_table_name}."
                         . "{$key} is not acceptable ('".  gettype($param) . "'"
                         . " supplied). Boolean, NULL, numeric or string value expected."
                         . PHP_EOL
                         . "Data supplied to "
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . " for insertion:"
                         . PHP_EOL . var_export($data_2_insert, true) . PHP_EOL
                         . PHP_EOL;
                    
                    throw new \GDAO\ModelInvalidInsertValueSuppliedException($msg);
                }
            }

            if( $this->_db_connector->executeQuery($insrt_qry_sql, $insrt_qry_params) ) {
             
                if($has_autoinc_pkey_col) {
                    
                    $last_insert_sequence_name = 
                        $insrt_qry_obj->getLastInsertIdName($this->_primary_col);

                    $pk_val_4_new_record = 
                            $this->getPDO()->lastInsertId($last_insert_sequence_name);

                    if( empty($pk_val_4_new_record) ) {

                        $msg = "ERROR: Could not retrieve the value for the primary"
                             . " key field name '{$this->_primary_col}' after the "
                             . " successful insertion of the data below: "
                             . PHP_EOL . var_export($data_2_insert, true) . PHP_EOL
                             . " into the table named '{$this->_table_name}' in the method " 
                             . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                             . PHP_EOL;

                        //throw exception
                        throw new \GDAO\ModelPrimaryColValueNotRetrievableAfterInsertException($msg);

                    } else {

                        //add primary key value of the newly inserted record to the 
                        //data to be returned.
                        $data_2_insert[$this->_primary_col] = $pk_val_4_new_record;
                    }
                }
                
                //insert was successful
                $result = $data_2_insert;
            } 
        }
        return $result;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function insertMany(array $rows_of_data_2_insert = array()) {
        
        $result = false;
        
        if ( 
            !empty($rows_of_data_2_insert) && count($rows_of_data_2_insert) > 0 
        ) {
            $table_cols = $this->getTableColNames();
            $time_created_colname = $this->_created_timestamp_column_name;
            $last_updated_colname = $this->_updated_timestamp_column_name;
            
            //if the db is sqlite 3.7.10 or prior, we can't take advantage of
            //bulk insert, have to revert to multiple insert statements.
            if( strtolower($this->pdo_driver_name) === 'sqlite' ) {
                
                $pdo_obj = $this->getPDO();
                
                $sqlite_version_number = 
                            $pdo_obj->getAttribute(\PDO::ATTR_SERVER_VERSION);
                
                if( version_compare($sqlite_version_number, '3.7.10', '<=') ) {
                    
                    // start the transaction
                    $pdo_obj->beginTransaction();

                    try {
                        
                        foreach($rows_of_data_2_insert as $row_2_insert) {

                            $result = $this->insert($row_2_insert);
                            
                            if ($result === false) {

                                // insert for current row failed.
                                // throw it all away.
                                $pdo_obj->rollBack();
                                return false;
                            }
                        }

                        //all inserts succeeded
                        $pdo_obj->commit();
                        return true;

                    } catch (\Exception $e) {

                        // roll back and throw the exception
                        $pdo_obj->rollBack();
                        throw $e;
                    }
                }//if( $version_numbers_only <= 3710 )
            }//if( $this->pdo_driver_name === 'sqlite' ) 
            
            ////////////////////////////////////////////////////////////////////
            // Do Bulk insert for other DBMSs including Sqlite 3.7.11 and later
            ////////////////////////////////////////////////////////////////////

            foreach ($rows_of_data_2_insert as $key=>$row_2_insert) {
                
                if(
                    !empty($time_created_colname) 
                    && in_array($time_created_colname, $table_cols)
                ) {
                    //set created timestamp to now
                    $rows_of_data_2_insert[$key][$time_created_colname] = date('Y-m-d H:i:s');
                }

                if(
                    !empty($last_updated_colname) 
                    && in_array($last_updated_colname, $table_cols)
                ) {
                    //set last updated timestamp to now
                    $rows_of_data_2_insert[$key][$last_updated_colname] = date('Y-m-d H:i:s');
                }

                // remove non-existent table columns from the data
                foreach ($row_2_insert as $col_name => $val) {

                    if ( !in_array($col_name, $table_cols) ) {

                        unset($rows_of_data_2_insert[$key][$col_name]);
                        // not in the table, so no need to check for autoinc
                        continue;
                    }

                    // Code below was lifted from Solar_Sql_Model::insert()
                    // remove empty autoinc columns to soothe postgres, which won't
                    // take explicit NULLs in SERIAL cols.
                    if ( $this->_table_cols[$col_name]['autoinc'] === true && empty($val)) {

                        unset($rows_of_data_2_insert[$key][$col_name]);
                    }
                }

                foreach( $this->_table_cols as $col_name=>$col_info ) {

                    if ( $col_info['autoinc'] === true && $col_info['primary'] === true ) {
                        
                        if(array_key_exists($col_name, $row_2_insert)) {

                            //no need to add primary key value to the insert 
                            //statement since the column is auto incrementing
                            unset($rows_of_data_2_insert[$key][$col_name]);
                            
                        } // if(array_key_exists($col_name, $row_2_insert))
                        
                    } // if ( $col_info['autoinc'] === true && $col_info['primary'] === true )
                    
                } // foreach( $this->_table_cols as $col_name=>$col_info )

            } // foreach ($rows_of_data_2_insert as $key=>$row_2_insert)
            
            //Insert statement
            $insrt_qry_obj = (new QueryFactory($this->_pdo_driver_name))->newInsert();
            
            //Batch all the data into one insert query.
            $insrt_qry_obj->into($this->_table_name)->addRows($rows_of_data_2_insert);           
            $insrt_qry_sql = $insrt_qry_obj->__toString();
            $insrt_qry_params = $insrt_qry_obj->getBindValues();

            foreach ( $insrt_qry_params as $key => $param ) {
                
                if(
                    !is_bool($param) 
                    && !is_null($param) 
                    && !is_numeric($param)
                    && !is_string($param)
                ) {
                    $msg = "ERROR: the value "
                         . PHP_EOL . var_export($param, true) . PHP_EOL
                         . " you are trying to insert into {$this->_table_name}."
                         . "{$key} is not acceptable ('".  gettype($param) . "'"
                         . " supplied). Boolean, NULL, numeric or string value expected."
                         . PHP_EOL
                         . "Data supplied to "
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . " for insertion:"
                         . PHP_EOL . var_export($rows_of_data_2_insert, true) . PHP_EOL
                         . PHP_EOL;
                    
                    throw new \GDAO\ModelInvalidInsertValueSuppliedException($msg);
                }
            }
            
            $result = $this->_db_connector->executeQuery($insrt_qry_sql, $insrt_qry_params);
        }
        
        return $result;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function updateMatchingDbTableRows(
        array $col_names_n_vals_2_save = array(),
        array $col_names_n_vals_2_match = array()
    ) {
        $result = null;

        if ( 
            !empty($col_names_n_vals_2_save) && count($col_names_n_vals_2_save) > 0 
        ) {
            $table_cols = $this->getTableColNames();
            $last_updtd_colname = $this->_updated_timestamp_column_name;
          
            if(
                !empty($last_updtd_colname) 
                && in_array($last_updtd_colname, $table_cols)
            ) {
                //set last updated timestamp to now
                $col_names_n_vals_2_save[$last_updtd_colname] = date('Y-m-d H:i:s');
            }
            
            
            $pkey_col_name = $this->getPrimaryColName();
            
            if(array_key_exists($pkey_col_name, $col_names_n_vals_2_save)) {
                
                //don't update the primary key
                unset($col_names_n_vals_2_save[$pkey_col_name]);
            }
            
            // remove non-existent table columns from the data
            // and check that existent table columns have values of  
            // the right data type: ie. Boolean, NULL, Number or String.
            foreach ($col_names_n_vals_2_save as $key => $val) {
                
                if ( !in_array($key, $table_cols) ) {
                    
                    unset($col_names_n_vals_2_save[$key]);
                    
                } else if(
                    !is_bool($val) 
                    && !is_null($val) 
                    && !is_numeric($val)
                    && !is_string($val)
                ) {
                    $msg = "ERROR: the value "
                         . PHP_EOL . var_export($val, true) . PHP_EOL
                         . " you are trying to update {$this->_table_name}."
                         . "{$key} with is not acceptable ('".  gettype($val) . "'"
                         . " supplied). Boolean, NULL, numeric or string value expected."
                         . PHP_EOL
                         . "Data supplied to "
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . " for update:"
                         . PHP_EOL . var_export($col_names_n_vals_2_save, true) . PHP_EOL
                         . PHP_EOL;

                    throw new \GDAO\ModelInvalidUpdateValueSuppliedException($msg);
                }
            }
                        
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
                        
                        $colval = array_unique($colval);
                        
                        //quote all string values
                        array_walk(
                            $colval,
                            function(&$val, $key, $pdo) {
                                $val = \LeanOrm\Utils::quoteStrForQuery($pdo, $val);
                            },                     
                            $this->getPDO()
                        );

                        $update_qry_obj->where("{$colname} IN (" . implode(',', $colval) . ") ");
                        
                    } else {
                        
                        //NOTE: not pdo quoting $colval here because when 
                        //DBConnector::executeQuery($updt_qry, $updt_qry_params)
                        //is called, the pdo quoting gets handled by DBConnector.
                        $update_qry_obj->where("{$colname} = ?", $colval);
                    }
                }
            }
            
            $updt_qry = $update_qry_obj->__toString();
            $updt_qry_params = $update_qry_obj->getBindValues();

            $result = $this->_db_connector->executeQuery($updt_qry, $updt_qry_params, true);

            if( $result[0] === true ) {
                
                //return number of affected rows
                $pdo_statement_used_for_query = $result[1];
                $result = $pdo_statement_used_for_query->rowCount();
                
            } else {
                
                //return boolean result of the \PDOStatement::execute() call
                //from $this->_db_connector->executeQuery($updt_qry, $updt_qry_params, true);
                $result = $result[0];
            }
        }
        
        return $result;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function updateSpecifiedRecord(\GDAO\Model\RecordInterface $record) {
        
        //$this->_primary_col should have a valid value because a
        //GDAO\ModelPrimaryColNameNotSetDuringConstructionException
        //is thrown in $this->__construct() if $this->_primary_col is not set.
        $succesfully_updated = null;
        
        if( $record instanceof \LeanOrm\Model\ReadOnlyRecord ) {

            $msg = "ERROR: Can't save a ReadOnlyRecord to the database in " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).'
                 . PHP_EOL .'Undeleted record' . var_export($record, true) . PHP_EOL;
            throw new \LeanOrm\CantDeleteReadOnlyRecordFromDBException($msg);
        }
        
        $pri_key_val = $record->getPrimaryVal();
        
        //test if the record object has data and is not a new record
        if( count($record) > 0 && !empty($pri_key_val) && is_numeric($pri_key_val)) {
            
            $cols_n_vals_2_match = array($record->getPrimaryCol()=>$pri_key_val);

            $succesfully_updated = 
                $this->updateMatchingDbTableRows(
                            $record->getData(), $cols_n_vals_2_match
                        );
            
            if($succesfully_updated === 1 || $succesfully_updated === true) {
                
                $params = [
                            'where' =>  
                                [
                                    [
                                        'col' => $record->getPrimaryCol(), 
                                         'op' => '=', 
                                        'val' => $record->getPrimaryVal()
                                    ]
                                ],
                        ];

                $updated_data = $this->fetchRowsIntoArray($params);

                //Get the first record. There should only be one record
                //since we are fetching by the primary key column's value.
                $updated_data = array_shift($updated_data);

                //refresh this record with the updated data
                $record->loadData($updated_data);
            }
        }
        
        return is_numeric($succesfully_updated)? 
                        ($succesfully_updated === 1) : $succesfully_updated ;
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

    public function getCurrentConnectionInfo() {

        $attributes = array(
			'database_server_info' => 'SERVER_INFO',
			'driver_name' => 'DRIVER_NAME',
			'pdo_client_version' => 'CLIENT_VERSION',
			'database_server_version' => 'SERVER_VERSION',
			'connection_status' => 'CONNECTION_STATUS',
			'connection_is_persistent' => 'PERSISTENT',
		);

        $pdo_obj = $this->getPDO();
        
		foreach ($attributes as $key => $value)
		{
			$attributes[ $key ] = $pdo_obj->getAttribute(constant('PDO::ATTR_' . $value));
            
            if( $value === 'PERSISTENT' ) {
                
                $attributes[ $key ] = var_export($attributes[ $key ], true);
            }
		}

		return $attributes;
	}
}
