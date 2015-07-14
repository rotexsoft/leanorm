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
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $extra_opts);

        DBConnector::configure($dsn);
        DBConnector::configure('username', $username);
        DBConnector::configure('password', $passwd);
        
        if( count($pdo_driver_opts) > 0 ) {
            
            DBConnector::configure( 'driver_options', $pdo_driver_opts);
        }
        
        foreach ($extra_opts as $e_opt_key => $e_opt_val) {

            if(
                is_string($e_opt_key) 
                && in_array($e_opt_key, static::$_valid_extra_opts_keys_4_dbconnector)
            ) {
                DBConnector::configure($e_opt_key, $e_opt_val);
            }
        }
        
        $this->_db_connector = DBConnector::create();
        $this->_pdo_driver_name = $this->getPDO()
                                       ->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
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
         
            //default to creating new collection of type \LeanOrm\Model\Collection
            $collection = new \LeanOrm\Model\Collection($list_of_records, $extra_opts);
            
        } else {
            
            $collection = new $this->_collection_class_name($list_of_records, $extra_opts);
        }
        
        $collection->setModel($this);
        
        return $collection;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function createRecord(array $col_names_n_vals = array(), array $extra_opts=array()) {
        
        if( empty($this->_record_class_name) ) {
         
            //default to creating new record of type \LeanOrm\Model\Record
            $record = new \LeanOrm\Model\Record($col_names_n_vals, $extra_opts);
            
        } else {
            
            $record = new $this->_record_class_name($col_names_n_vals, $extra_opts);
        }
        
        $record->setModel($this);
        
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

                $sql_n_bind_params = 
                    $this->_getWhereOrHavingClauseWithParams($where_params);
                                
                $select_qry_obj->where( $sql_n_bind_params[0] );
                
                if( count( $sql_n_bind_params[1] ) > 0 ) {
                    
                    $select_qry_obj->bindValues( $sql_n_bind_params[1] );
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

                $sql_n_bind_params = 
                    $this->_getWhereOrHavingClauseWithParams($having_params);
                                
                $select_qry_obj->having( $sql_n_bind_params[0] );
                
                if( count( $sql_n_bind_params[1] ) > 0 ) {
                    
                    $select_qry_obj->bindValues( $sql_n_bind_params[1] );
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
    
    /**
     * 
     * @param string $rel_name
     * @return \GDAO\Model\Collection|array
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
                $parent_data instanceof \GDAO\Model\Collection
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
                    if( $parent_record instanceof \GDAO\Model\Record ) {

                        $parent_data[$p_rec_key]
                            ->setRelatedData($rel_name, $matching_related_records);

                    } else {

                        //the current record must be an array
                        $parent_data[$p_rec_key][$rel_name] = $matching_related_records;
                    }
                } //foreach( $parent_data as $p_rec_key => $parent_record )
        
            } else if ( $parent_data instanceof \GDAO\Model\Record ) {

                $this->_wrapRelatedDataInsideRecordsAndCollection(
                    $wrap_each_row_in_a_record, $related_data, $foreign_models_record_class_name,
                    $foreign_model_obj, $wrap_records_in_collection, $foreign_models_collection_class_name
                );
                
                //stitch the related data to the parent record
                $parent_data->setRelatedData($rel_name, $related_data);
            } // else if ($parent_data instanceof \GDAO\Model\Record)
        } // if( array_key_exists($rel_name, $this->_relations) )
    }
    
    /**
     * 
     * @param string $rel_name
     * @return \GDAO\Model\Collection|array
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

            $foreign_table_name = $array_get($rel_info, 'foreign_models_table');
            
            $fkey_col_in_foreign_table = 
                $array_get($rel_info, 'col_in_foreign_models_table_linked_to_join_table');
            
            $foreign_models_class_name = 
                $array_get($rel_info, 'foreign_models_class_name', '\\LeanOrm\\Model');
            
            $foreign_models_record_class_name = 
                $array_get($rel_info, 'foreign_models_record_class_name', '\\LeanOrm\\Model\\Record');
            
            $foreign_models_collection_class_name = 
                $array_get($rel_info, 'foreign_models_collection_class_name', '\\LeanOrm\Model\\Collection');
            
            $pri_key_col_in_foreign_models_table = 
                $array_get($rel_info, 'primary_key_col_in_foreign_models_table');
            
            $fkey_col_in_my_table = 
                    $array_get($rel_info, 'col_in_my_models_table_linked_to_join_table');
            
            //join table params
            $join_table_name = $array_get($rel_info, 'join_table_name');
            
            $col_in_join_table_linked_to_my_models_table = 
                $array_get($rel_info, 'col_in_join_table_linked_to_my_models_table');
            
            $col_in_join_table_linked_to_foreign_models_table = 
                $array_get($rel_info, 'col_in_join_table_linked_to_foreign_models_table');
            
            $foreign_models_table_sql_params = 
                    $array_get($rel_info, 'foreign_models_table_sql_params', array());
            
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
            
            if ( $parent_data instanceof \GDAO\Model\Record ) {
                
                $where_cond = " {$join_table_name}.{$col_in_join_table_linked_to_my_models_table} = "
                     . (
                            is_string($parent_data->$fkey_col_in_my_table) ? 
                                $this->getPDO()->quote( $parent_data->$fkey_col_in_my_table )
                                : $parent_data->$fkey_col_in_my_table
                        );
                
                $query_obj->where($where_cond);
                
            } else {
                
                //assume it's a collection or array
                $col_vals = $this->_getColValsFromArrayOrCollection(
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
                                            $foreign_table_name
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
                     ->fetchAllRows($sql_2_get_related_data, $params_2_bind_2_sql);

            if ( 
                $parent_data instanceof \GDAO\Model\Collection
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
                    if( $parent_record instanceof \GDAO\Model\Record ) {

                        $parent_data[$p_rec_key]
                            ->setRelatedData($rel_name, $matching_related_records);
                    } else {

                        //the current record must be an array
                        $parent_data[$p_rec_key][$rel_name] = $matching_related_records;
                    }
                } //foreach( $parent_data as $p_rec_key => $parent_record )
        
            } else if ( $parent_data instanceof \GDAO\Model\Record ) {

                $this->_wrapRelatedDataInsideRecordsAndCollection(
                    $wrap_each_row_in_a_record, $related_data, $foreign_models_record_class_name,
                    $foreign_model_obj, $wrap_records_in_collection, $foreign_models_collection_class_name
                );
                
                //stitch the related data to the parent record
                $parent_data->setRelatedData($rel_name, $related_data);
            } // else if ( $parent_data instanceof \GDAO\Model\Record )
        } // if( array_key_exists($rel_name, $this->_relations) )
    }
    
    /**
     * 
     * @param string $rel_name
     * @return \GDAO\Model\Record|array
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
                $parent_data instanceof \GDAO\Model\Collection
                || is_array($parent_data)
            ) {
                //stitch the related data to the approriate parent records
                foreach( $parent_data as $p_rec_key => $parent_record ) {

                    $matching_related_record = 
                        array($related_data[$parent_record[$fkey_col_in_my_table]]);
                    
                    $this->_wrapRelatedDataInsideRecordsAndCollection(
                                $wrap_row_in_a_record, $matching_related_record, 
                                $foreign_models_record_class_name, 
                                $foreign_model_obj, false, ''
                            );

                    //set the related data for the current parent record
                    if( $parent_record instanceof \GDAO\Model\Record ) {

                        $parent_data[$p_rec_key]
                            ->setRelatedData($rel_name, $matching_related_record[0]);

                    } else {

                        //the current record must be an array
                        $parent_data[$p_rec_key][$rel_name] = $matching_related_record[0];
                    }
                } //foreach( $parent_data as $p_rec_key => $parent_record )
        
            } else if ( $parent_data instanceof \GDAO\Model\Record ) {

                $this->_wrapRelatedDataInsideRecordsAndCollection(
                            $wrap_row_in_a_record, $related_data, 
                            $foreign_models_record_class_name,
                            $foreign_model_obj, false, ''
                        );
                
                //stitch the related data to the parent record
                $parent_data->setRelatedData($rel_name, array_shift($related_data));
            } // else if ($parent_data instanceof \GDAO\Model\Record)
        } // if( array_key_exists($rel_name, $this->_relations) )
    }
    
    /**
     * 
     * @param string $rel_name
     * @return \GDAO\Model\Record|array
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

        $foreign_table_name = $array_get($rel_info, 'foreign_models_table');

        $fkey_col_in_foreign_table = 
            $array_get($rel_info, 'foreign_key_col_in_foreign_models_table');

        $foreign_models_class_name = 
            $array_get($rel_info, 'foreign_models_class_name', '\\LeanOrm\\Model');

        $foreign_models_record_class_name = 
            $array_get($rel_info, 'foreign_models_record_class_name', '\\LeanOrm\\Model\\Record');

        $foreign_models_collection_class_name = 
            $array_get($rel_info, 'foreign_models_collection_class_name', '\\LeanOrm\Model\\Collection');

        $pri_key_col_in_foreign_models_table = 
            $array_get($rel_info, 'primary_key_col_in_foreign_models_table');

        $fkey_col_in_my_table = 
                $array_get($rel_info, 'foreign_key_col_in_my_models_table');

        $foreign_models_table_sql_params = 
                $array_get($rel_info, 'foreign_models_table_sql_params', array());

        $query_obj = 
            $this->_buildFetchQueryObjectFromParams(
                        $foreign_models_table_sql_params, 
                        array('relations_to_include', 'limit_offset', 'limit_size'), 
                        $foreign_table_name
                    );

        if ( $parent_data instanceof \GDAO\Model\Record ) {

            $where_cond = " {$foreign_table_name}.{$fkey_col_in_foreign_table} = "
                        . (
                           is_string($parent_data->$fkey_col_in_my_table) ? 
                               $this->getPDO()
                                    ->quote( $parent_data->$fkey_col_in_my_table )
                                : $parent_data->$fkey_col_in_my_table
                           );
            $query_obj->where($where_cond);

        } else {
            //assume it's a collection or array                
            $col_vals = $this->_getColValsFromArrayOrCollection(
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
                                        $foreign_table_name
                                    );
        $params_2_bind_2_sql = $query_obj->getBindValues();
        $sql_2_get_related_data = $query_obj->__toString();

        //GRAB DA RELATED DATA
        $related_data = 
            $this->_db_connector
                 ->fetchAllRows($sql_2_get_related_data, $params_2_bind_2_sql);

        return array(
            $fkey_col_in_foreign_table, $foreign_models_record_class_name,
            $foreign_models_collection_class_name, $fkey_col_in_my_table, 
            $foreign_model_obj, $related_data
        ); 
    }

    protected function _createRelatedModelObject(
        $foreign_models_class_name, 
        $pri_key_col_in_foreign_models_table, 
        $foreign_table_name
    ) {
        if(
            !empty($foreign_models_class_name)
            && !empty($pri_key_col_in_foreign_models_table)
        ) {
            //try to create a model object for the related data
            return new $foreign_models_class_name(
                $this->_dsn, 
                $this->_username, 
                $this->_passwd, 
                $this->_pdo_driver_opts,
                array(
                    '_primary_col' => $pri_key_col_in_foreign_models_table,
                    '_table_name' => $foreign_table_name
                )
            );
        }
        
        return null;
    }
    
    protected function _getColValsFromArrayOrCollection(
        &$parent_data, $fkey_col_in_my_table
    ) {
        $col_vals = array();
        
        if(
            $parent_data instanceof \GDAO\Model\Collection
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

                foreach ( $col_vals as $key=>$val ) {

                    $col_vals[$key] = 
                                is_string($val)? $pdo->quote($val) : $val;
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
                        $rec_data, array('is_new'=>false)
                    );

                if(!empty($foreign_model_obj)) {

                    $matching_related_records[$key]
                                    ->setModel($foreign_model_obj);
                }
            }
        }

        if($wrap_records_in_collection) {

            //wrap into a collection object
            $matching_related_records = new $foreign_models_collection_class_name (
                new \GDAO\Model\GDAORecordsList( $matching_related_records )
            );

            if( !empty($foreign_model_obj) ) {

                $matching_related_records->setModel($foreign_model_obj);
            }
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchAll(array $params = array()) {

        $results = $this->createCollection(
                        new \GDAO\Model\GDAORecordsList(
                                $this->_getData4FetchAll($params)
                            )
                    );

        if( array_key_exists('relations_to_include', $params) ) {
            
            foreach( $params['relations_to_include'] as $rel_name ) {

                $this->loadRelationshipData($rel_name, $results, true, true);
            }
        }
        
        return $results;
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchAllAsArray(array $params = array()) {
        
        $results = $this->_getData4FetchAll($params);

        if( array_key_exists('relations_to_include', $params) ) {
            
            foreach( $params['relations_to_include'] as $rel_name ) {

                $this->loadRelationshipData($rel_name, $results, true);
            }
        }
        
        return $results;
    }
    
    protected function _getData4FetchAll($params) {

        $results = $this->_getData4FetchArray($params);
        
        foreach ($results as $key=>$value) {

            $results[$key] = $this->createRecord($value, array('is_new'=>false));
        }
        
        return $results;
    }
    
    protected function _getData4FetchArray($params) {
        
        $query_obj = $this->_buildFetchQueryObjectFromParams($params);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        return $this->_db_connector->fetchAllRows($sql, $params_2_bind_2_sql);
    }
    
    /**
     * 
     * {@inheritDoc}
     */
    public function fetchArray(array $params = array()) {

        $results = $this->_getData4FetchArray($params);

        if( array_key_exists('relations_to_include', $params) ) {
            
            foreach( $params['relations_to_include'] as $rel_name ) {

                $this->loadRelationshipData($rel_name, $results);
            }
        }
        
        return $results;
    }
    
    /**
     * 
     * @return PDO
     */
    public function getPDO() {
        
        return DBConnector::getDb();
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

            $orm_obj = $this->_db_connector;
            
            $slct_qry = $select_qry_obj->__toString();
            $slct_qry_params = $select_qry_obj->getBindValues();
            $slct_qry_result = $orm_obj->fetchOneRow($slct_qry, $slct_qry_params);
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
                $result = $orm_obj->executeQuery($dlt_qry, $dlt_qry_params); 
                
                if( $result === true ) {
                    
                    $result = $num_of_matched_records;
                }
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

            if ( $succesfully_deleted === 1 ) {

                $record->setStateToNew();
            }
        }
        
        return is_numeric($succesfully_deleted)?
                        ((bool) $succesfully_deleted) : $succesfully_deleted;
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

            throw new ModelBadFetchParamsSuppliedException($msg);
        }

        $query_obj = $this->_buildFetchQueryObjectFromParams($params);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $results = $this->_db_connector->fetchAllRows($sql, $params_2_bind_2_sql);

        return array_column($results, $col_name);
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function fetchOne(array $params = array()) {

        $param_keys_2_exclude = array('limit_offset', 'limit_size');
        
        $query_obj = 
            $this->_buildFetchQueryObjectFromParams($params, $param_keys_2_exclude);
        $query_obj->limit(1);
        
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $result = $this->_db_connector->fetchAllRows($sql, $params_2_bind_2_sql);
        
        if( count($result) > 0 ) {
            
            $result = 
                $this->createRecord(array_shift($result), array('is_new'=>false));
        }

        if( array_key_exists('relations_to_include', $params) ) {
            
            foreach( $params['relations_to_include'] as $rel_name ) {

                $this->loadRelationshipData($rel_name, $result, true, true);
            }
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

            throw new ModelBadFetchParamsSuppliedException($msg);
        }

        $query_obj = $this->_buildFetchQueryObjectFromParams($params);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $results = $this->_db_connector->fetchAllRows($sql, $params_2_bind_2_sql);

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

            throw new ModelBadFetchParamsSuppliedException($msg);
        }
        
        $param_keys_2_exclude = array('limit_offset', 'limit_size');
        
        $query_obj = 
            $this->_buildFetchQueryObjectFromParams($params, $param_keys_2_exclude);
        $query_obj->limit(1);
        
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();

        $result = $this->_db_connector->fetchAllRows($sql, $params_2_bind_2_sql);
       
        if( count($result) > 0 ) {
            
            $record = array_shift($result);
            $result = $record[$col_name];
        }

        return $result;
    }

    /**
     * 
     * {@inheritDoc}
     */
    public function insert($col_names_n_vals = array()) {

        $result = false;
        
        if ( 
            !empty($col_names_n_vals) && count($col_names_n_vals) > 0 
        ) {
            $table_cols = $this->getTableCols();
            $time_created_colname = $this->_created_timestamp_column_name;
          
            if(
                !empty($time_created_colname) 
                && in_array($time_created_colname, $table_cols)
            ) {
                //set created timestamp to now
                $col_names_n_vals[$time_created_colname] = date('Y-m-d H:i:s');
            }
            
            $last_updated_colname = $this->_updated_timestamp_column_name;
          
            if(
                !empty($last_updated_colname) 
                && in_array($last_updated_colname, $table_cols)
            ) {
                //set last updated timestamp to now
                $col_names_n_vals[$last_updated_colname] = date('Y-m-d H:i:s');
            }
            
            // remove non-existent table columns from the data
            foreach ($col_names_n_vals as $key => $val) {
                
                if ( !in_array($key, $table_cols) ) {
                    
                    unset($col_names_n_vals[$key]);
                    // not in the table, so no need to check for autoinc
                    continue;
                }
                
                // Code below was lifted from Solar_Sql_Model::insert()
                // remove empty autoinc columns to soothe postgres, which won't
                // take explicit NULLs in SERIAL cols.
                if ( $this->_table_cols[$key]['autoinc'] && empty($val)) {
                    
                    unset($col_names_n_vals[$key]);
                }
            }
            
            //Insert statement
            $insrt_qry_obj = (new QueryFactory($this->_pdo_driver_name))->newInsert();
            $insrt_qry_obj->into($this->_table_name)->cols($col_names_n_vals);
            
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
                         . PHP_EOL . var_export($col_names_n_vals, true) . PHP_EOL
                         . PHP_EOL;
                    
                    throw new \GDAO\ModelInvalidInsertValueSuppliedException($msg);
                }
            }
            
            if( $this->_db_connector->executeQuery($insrt_qry_sql, $insrt_qry_params) ) {
             
                $last_insert_sequence_name = 
                    $insrt_qry_obj->getLastInsertIdName($this->_primary_col);

                $pk_val_4_new_record = 
                        $this->getPDO()->lastInsertId($last_insert_sequence_name);

                if( empty($pk_val_4_new_record) ) {
                    
                    $msg = "ERROR: Could not retrieve the value for the primary"
                         . " key field name '{$this->_primary_col}' after the "
                         . " successful insertion of the data below: "
                         . PHP_EOL . var_export($col_names_n_vals, true) . PHP_EOL
                         . " into the table named '{$this->_table_name}' in the method " 
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . PHP_EOL;
                    
                    //throw exception
                    throw new \GDAO\ModelPrimaryColValueNotRetrievableAfterInsertException($msg);
                    
                } else {
                    
                    //add primary key value of the newly inserted record to the 
                    //data to be returned.
                    $col_names_n_vals[$this->_primary_col] = $pk_val_4_new_record;
                }
                
                //insert was successful
                $result = $col_names_n_vals;
            } 
        }
        
        return $result;
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
            $table_cols = $this->getTableCols();
            $last_updtd_colname = $this->_updated_timestamp_column_name;
          
            if(
                !empty($last_updtd_colname) 
                && in_array($last_updtd_colname, $table_cols)
            ) {
                //set last updated timestamp to now
                $col_names_n_vals_2_save[$last_updtd_colname] = date('Y-m-d H:i:s');
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
                        
                        //NOTE: not pdo quoting $colval here because when 
                        //$orm_obj->find_one($slct_qry, $slct_qry_params) and 
                        //DBConnector::raw_execute($updt_qry, $updt_qry_params)
                        //are called, the pdo quoting gets handled by DBConnector.
                        $select_qry_obj->where("{$colname} = ?", $colval);
                        $update_qry_obj->where("{$colname} = ?", $colval);
                    }
                }
            }
           
            $orm_obj = $this->_db_connector;

            $slct_qry = $select_qry_obj->__toString();
            $slct_qry_params = $select_qry_obj->getBindValues();
            $slct_qry_result = $orm_obj->fetchOneRow($slct_qry, $slct_qry_params);
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
                $result = $orm_obj->executeQuery($updt_qry, $updt_qry_params);
                
                if( $result === true ) {
                    
                    //return number of matched records
                    $result = $num_of_matched_records;
                }
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
}

class ModelPropertyNotDefinedException extends \Exception{}
class ModelBadColsParamSuppliedException extends \Exception{}
class ModelBadWhereParamSuppliedException extends \Exception{}
class ModelBadFetchParamsSuppliedException extends \Exception{}
class ModelBadHavingParamSuppliedException extends \Exception{}
class ModelBadGroupByParamSuppliedException extends \Exception{}
class ModelBadOrderByParamSuppliedException extends \Exception{}
class ModelBadWhereOrHavingParamSuppliedException extends \Exception{}