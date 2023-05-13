<?php
declare(strict_types=1);
namespace LeanOrm;

use Aura\SqlQuery\QueryFactory;
use Aura\SqlSchema\ColumnFactory;
use LeanOrm\Utils;
use Psr\Log\LoggerInterface;
use Atlas\Info\Info as AtlasInfo;
use Atlas\Pdo\Connection as AtlasPdoConnection;
use function sprintf;

/**
 * Supported PDO drivers: mysql, pgsql, sqlite and sqlsrv
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2022, Rotexsoft
 */
class Model extends \GDAO\Model {
    
    //overriden parent's properties
    /**
     * Name of the collection class for this model. 
     * Must be a descendant of \GDAO\Model\Collection
     */
    protected ?string $collection_class_name = \LeanOrm\Model\Collection::class;

    /**
     * Name of the record class for this model. 
     * Must be a descendant of \GDAO\Model\Record
     */
    protected ?string $record_class_name = \LeanOrm\Model\Record::class;

    /////////////////////////////////////////////////////////////////////////////
    // Properties declared here are specific to \LeanOrm\Model and its kids //
    /////////////////////////////////////////////////////////////////////////////

    /**
     * Name of the pdo driver currently being used.
     * It must be one of the values returned by $this->getPDO()->getAvailableDrivers()
     */
    protected string $pdo_driver_name = '';

    public function getPdoDriverName(): string {
        
        return $this->pdo_driver_name;
    }

    /**
     *  An object for interacting with the db
     */
    protected ?\LeanOrm\DBConnector $db_connector = null;

    // Query Logging related properties
    protected bool $can_log_queries = false;

    public function canLogQueries(): bool { return $this->can_log_queries; }

    public function enableQueryLogging(): self {

        $this->can_log_queries = true;
        return $this;
    }

    public function disableQueryLogging(): self {

        $this->can_log_queries = false;
        return $this;
    }

    /**
     * @var array<string, array>
     */
    protected array $query_log = [];

    /**
     * @var array<string, array>
     */
    protected static array $all_instances_query_log = [];

    protected ?LoggerInterface $logger = null;

    public function setLogger(?LoggerInterface $logger): self {
        
        $this->logger = $logger;
        return $this;
    }
    
    public function getLogger(): ?LoggerInterface { return $this->logger; }
    
    /**
     * {@inheritDoc}
     */
    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [],
        string $primary_col_name='',
        string $table_name=''
    ) {
        $pri_col_not_set_exception_msg = '';

        try {

            parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);

        } catch (\GDAO\ModelPrimaryColNameNotSetDuringConstructionException $e) {

            //$this->primary_col (primary key colun has not yet been set)
            //hold this exception for later if necessary
            $pri_col_not_set_exception_msg = $e->getMessage();
        }
        
        DBConnector::configure($dsn, null, $dsn);//use $dsn as connection name in 3rd parameter
        DBConnector::configure('username', $username, $dsn);//use $dsn as connection name in 3rd parameter
        DBConnector::configure('password', $passwd, $dsn);//use $dsn as connection name in 3rd parameter

        if( $pdo_driver_opts !== [] ) {

            DBConnector::configure( 'driver_options', $pdo_driver_opts, $dsn);//use $dsn as connection name in 3rd parameter
        }

        $this->db_connector = DBConnector::create($dsn);//use $dsn as connection name
        $this->pdo_driver_name = $this->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $this->pdoServerVersionCheck();

        ////////////////////////////////////////////////////////
        //Get and Set Table Schema Meta Data if Not Already Set
        ////////////////////////////////////////////////////////
        if ( $this->table_cols === [] ) {

            static $dsn_n_tname_to_schema_def_map;

            if( !$dsn_n_tname_to_schema_def_map ) {

                $dsn_n_tname_to_schema_def_map = [];
            }

            if( array_key_exists($dsn.$this->getTableName(), $dsn_n_tname_to_schema_def_map) ) {

                // use cached schema definition for the dsn and table name combo
                $schema_definitions = $dsn_n_tname_to_schema_def_map[$dsn.$this->getTableName()];

            } else {

                // let's make sure that $this->getTableName() is an actual table / view in the db
                if( !$this->tableExistsInDB($this->getTableName()) ) {

                    $msg = "ERROR: Table name `{$this->getTableName()}` supplied to " 
                            . get_class($this) . '::' . __FUNCTION__ . '(...)'
                            . ' does not exist as a table or view in the database';
                    throw new BadModelTableNameException($msg);
                }

                $this->table_cols = [];
                $schema_definitions = $this->fetchTableColsFromDB($this->getTableName());

                // cache schema definition for the current dsn and table combo
                $dsn_n_tname_to_schema_def_map[$dsn.$this->getTableName()] = $schema_definitions;

            } // if( array_key_exists($dsn.$this->getTableName(), $dsn_n_tname_to_schema_def_map) )

            if( 
                $primary_col_name !== ''
                && !$this->columnExistsInDbTable($this->getTableName(), $primary_col_name) 
            ) {
                $msg = "ERROR: The Primary Key column name `{$primary_col_name}` supplied to " 
                        . get_class($this) . '::' . __FUNCTION__ . '(...)'
                        . " does not exist as an actual column in the supplied table `{$this->getTableName()}`.";
                throw new BadModelPrimaryColumnNameException($msg);
            }

            foreach( $schema_definitions as $colname => $metadata_obj ) {

                $this->table_cols[$colname] = [];
                $this->table_cols[$colname]['name'] = $metadata_obj->name;
                $this->table_cols[$colname]['type'] = $metadata_obj->type;
                $this->table_cols[$colname]['size'] = $metadata_obj->size;
                $this->table_cols[$colname]['scale'] = $metadata_obj->scale;
                $this->table_cols[$colname]['notnull'] = $metadata_obj->notnull;
                $this->table_cols[$colname]['default'] = $metadata_obj->default;
                $this->table_cols[$colname]['autoinc'] = $metadata_obj->autoinc;
                $this->table_cols[$colname]['primary'] = $metadata_obj->primary;

                if( $this->getPrimaryCol() === '' && $metadata_obj->primary ) {

                    //this is a primary column
                    $this->setPrimaryCol($metadata_obj->name);

                } // $this->getPrimaryCol() === '' && $metadata_obj->primary
            } // foreach( $schema_definitions as $colname => $metadata_obj )
            
        } else { // $this->table_cols !== []

            if($this->getPrimaryCol() === '') {

                foreach ($this->table_cols as $colname => $col_metadata) {

                    if($col_metadata['primary']) {

                        $this->setPrimaryCol($colname);
                        break;
                    }
                }
            } // if($this->getPrimaryCol() === '')
            
        }// if ( $this->table_cols === [] )

        //if $this->getPrimaryCol() is still '' at this point, throw an exception.
        if( $this->getPrimaryCol() === '' ) {

            throw new \GDAO\ModelPrimaryColNameNotSetDuringConstructionException($pri_col_not_set_exception_msg);
        }
    }
    
    /**
     * Detect if an unsupported DB Engine version is being used
     */
    protected function pdoServerVersionCheck(): void {

        if(strtolower($this->getPdoDriverName()) === 'sqlite') {

            $pdo_obj = $this->getPDO();
            $sqlite_version_number = $pdo_obj->getAttribute(\PDO::ATTR_SERVER_VERSION);

            if(version_compare($sqlite_version_number, '3.7.10', '<=')) {

                $source = get_class($this) . '::' . __FUNCTION__ . '(...)';
                $msg = "ERROR ({$source}): Sqlite version `{$sqlite_version_number}`"
                        . " detected. This package requires Sqlite version `3.7.11`"
                        . " or greater. Use a newer version of sqlite or use another"
                        . " DB server supported by this package." . PHP_EOL . 'Goodbye!!';

                throw new UnsupportedPdoServerVersionException($msg);

            } // if( version_compare($sqlite_version_number, '3.7.10', '<=') )
        } // if( strtolower($this->getPdoDriverName()) === 'sqlite' )
    }
    
    protected function columnExistsInDbTable(string $table_name, string $column_name): bool {
        
        $schema_definitions = $this->fetchTableColsFromDB($table_name);
        
        return array_key_exists($column_name, $schema_definitions);
    }
    
    protected function tableExistsInDB(string $table_name): bool {
        
        $list_of_tables_and_views = $this->fetchTableListFromDB();
        
        return in_array($table_name, $list_of_tables_and_views, true);
    }
    
    protected function getSchemaQueryingObject(): \Aura\SqlSchema\AbstractSchema {
        
        // a column definition factory 
        $column_factory = new ColumnFactory();
        $pdo_driver_name = $this->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $schema_class_name = '\\Aura\\SqlSchema\\' . ucfirst($pdo_driver_name) . 'Schema';

        // the schema discovery object
        return new $schema_class_name($this->getPDO(), $column_factory);
    }
    
    /**
     * @return mixed[]|string[]
     */
    protected function fetchTableListFromDB(): array {
        
        if(strtolower($this->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME)) === 'sqlite') {
            
            // Do this to return both tables and views
            // $this->getSchemaQueryingObject()->fetchTableList()
            // only returns table names but no views. That's why
            // we are doing this here
            return $this->db_connector->dbFetchCol("
                SELECT name FROM sqlite_master
                UNION ALL
                SELECT name FROM sqlite_temp_master
                ORDER BY name
            ");
        }
        
        $schema = $this->getSchemaQueryingObject();
        
        if(strtolower($this->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME)) ===  'pgsql') {
            
            // Calculate schema name for postgresql
            $schema_name = $this->db_connector->dbFetchValue('SELECT CURRENT_SCHEMA');
            
            return $schema->fetchTableList($schema_name);
        }
        
        return $schema->fetchTableList();
    }
    
    /**
     * @return  mixed[]|\Aura\SqlSchema\Column[]
     */
    protected function fetchTableColsFromDB(string $table_name): array {
                
        if(strtolower($this->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME)) ===  'pgsql') {
            
            // Use Atlas Info to get this data for Postgresql because 
            // Aura Sql Schema keeps blowing up when fetchTableCols
            // is called on \Aura\SqlSchema\PgsqlSchema
            $info = AtlasInfo::new(AtlasPdoConnection::new($this->getPDO()));
            
            $columns_info = $info->fetchColumns($table_name);

            foreach ($columns_info as $key=>$column_info) {

                // Convert each row to objects because 
                // $this->getSchemaQueryingObject()->fetchTableCols(..)
                // returns an array of Aura\SqlSchema\Column objects
                // whose properties are used to populate $this->table_cols.
                // Converting each row to an object will allow for each
                // row's data to be accessible via object property syntax
                $columns_info[$key] = (object)$column_info;
            }
             
             return $columns_info;
        }
        
        // This works so far for mysql & sqlite.  
        // Will need to test what works for MS Sql Server
        return $this->getSchemaQueryingObject()->fetchTableCols($table_name);
    }

    public function getSelect(): \Aura\SqlQuery\Common\Select {

        $selectObj = (new QueryFactory($this->getPdoDriverName()))->newSelect();

        $selectObj->from($this->getTableName());

        return $selectObj;
    }

    /**
     * {@inheritDoc}
     */
    public function createNewCollection(\GDAO\Model\RecordInterface ...$list_of_records): \GDAO\Model\CollectionInterface {

        return empty($this->collection_class_name)
                ? //default to creating new collection of type \LeanOrm\Model\Collection
                  new \LeanOrm\Model\Collection($this, ...$list_of_records)
                : new $this->collection_class_name($this, ...$list_of_records);
    }

    /**
     * {@inheritDoc}
     */
    public function createNewRecord(array $col_names_n_vals = []): \GDAO\Model\RecordInterface {

        return empty($this->record_class_name)
                ? //default to creating new record of type \LeanOrm\Model\Record
                  new \LeanOrm\Model\Record($col_names_n_vals, $this)
                : new $this->record_class_name($col_names_n_vals, $this);
    }

    /**
     * 
     * @param array $params an array of parameters passed to a fetch*() method
     * @param array $disallowed_keys list of keys in $params not to be used to build the query object 
     * @param string $table_name name of the table to select from (will default to $this->getTableName() if empty)
     * @return \Aura\SqlQuery\Common\Select or any of its descendants
     */
    protected function createQueryObjectIfNullAndAddColsToQuery(
        ?\Aura\SqlQuery\Common\Select $select_obj=null, string $table_name=''
    ): \Aura\SqlQuery\Common\Select {
        
        $initiallyNull = !( $select_obj instanceof \Aura\SqlQuery\Common\Select );
        $select_obj ??= $this->getSelect();

        if( $table_name === '' ) {

            $table_name = $this->getTableName();
        }

        if($initiallyNull || !$select_obj->hasCols()) {

            // We either just created the select object in this method or
            // there are no cols to select specified yet. 
            // Let's select all cols.
            $select_obj->cols([' ' . $table_name . '.* ']);
        }

        return $select_obj;
    }

    /**
     * @return mixed[]
     */
    public function getDefaultColVals(): array {

        $default_colvals = [];

        foreach($this->table_cols as $col_name => $col_metadata) {

            $default_colvals[$col_name] = $col_metadata['default'];
        }

        return $default_colvals;
    }

    /**
     * @param \GDAO\Model\RecordInterface|\GDAO\Model\CollectionInterface|array<string|int, array> $parent_data
     */
    public function loadRelationshipData($rel_name, &$parent_data, bool $wrap_each_row_in_a_record=false, bool $wrap_records_in_collection=false): self {

        if( 
            array_key_exists($rel_name, $this->relations) 
            && $this->relations[$rel_name]['relation_type'] === \GDAO\Model::RELATION_TYPE_HAS_MANY 
        ) {
            $this->loadHasMany($rel_name, $parent_data, $wrap_each_row_in_a_record, $wrap_records_in_collection);

        } else if (
            array_key_exists($rel_name, $this->relations) 
            && $this->relations[$rel_name]['relation_type'] === \GDAO\Model::RELATION_TYPE_HAS_MANY_THROUGH        
        ) {
            $this->loadHasManyThrough($rel_name, $parent_data, $wrap_each_row_in_a_record, $wrap_records_in_collection);

        } else if (
            array_key_exists($rel_name, $this->relations) 
            && $this->relations[$rel_name]['relation_type'] === \GDAO\Model::RELATION_TYPE_HAS_ONE
        ) {
            $this->loadHasOne($rel_name, $parent_data, $wrap_each_row_in_a_record);

        } else if (
            array_key_exists($rel_name, $this->relations) 
            && $this->relations[$rel_name]['relation_type'] === \GDAO\Model::RELATION_TYPE_BELONGS_TO
        ) {
            $this->loadBelongsTo($rel_name, $parent_data, $wrap_each_row_in_a_record);
        }

        return $this;
    }
    
    protected function validateRelatedModelClassName(string $model_class_name): bool {
        
        // DO NOT use static::class here, we always want self::class
        // Subclasses can override this method to redefine their own
        // Valid Related Model Class logic.
        $parent_model_class_name = self::class;
        
        if( !is_a($model_class_name, $parent_model_class_name, true) ) {
            
            //throw exception
            $msg = "ERROR: '{$model_class_name}' is not a subclass or instance of "
                 . "'{$parent_model_class_name}'. A model class name specified"
                 . " for fetching related data must be the name of a class that"
                 . " is a sub-class or instance of '{$parent_model_class_name}'"
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new BadModelClassNameForFetchingRelatedDataException($msg);
        }
        
        return true;
    }

    protected function validateRelatedCollectionClassName(string $collection_class_name): bool {

        $parent_collection_class_name = \GDAO\Model\CollectionInterface::class;

        if( !is_subclass_of($collection_class_name, $parent_collection_class_name, true) ) {

            //throw exception
            $msg = "ERROR: '{$collection_class_name}' is not a subclass of "
                 . "'{$parent_collection_class_name}'. A collection class name specified"
                 . " for fetching related data must be the name of a class that"
                 . " is a sub-class of '{$parent_collection_class_name}'"
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new BadCollectionClassNameForFetchingRelatedDataException($msg);
        }

        return true;
    }

    protected function validateRelatedRecordClassName(string $record_class_name): bool {
        
        $parent_record_class_name = \GDAO\Model\RecordInterface::class;

        if( !is_subclass_of($record_class_name, $parent_record_class_name, true)  ) {

            //throw exception
            $msg = "ERROR: '{$record_class_name}' is not a subclass of "
                 . "'{$parent_record_class_name}'. A record class name specified for"
                 . " fetching related data must be the name of a class that"
                 . " is a sub-class of '{$parent_record_class_name}'"
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new BadRecordClassNameForFetchingRelatedDataException($msg);
        }

        return true;
    }
    
    /**
     * @param \GDAO\Model\RecordInterface|\GDAO\Model\CollectionInterface|array<string|int, array> $parent_data
     */
    protected function loadHasMany( 
        string $rel_name, &$parent_data, bool $wrap_each_row_in_a_record=false, bool $wrap_records_in_collection=false 
    ): void {
        if( 
            array_key_exists($rel_name, $this->relations) 
            && $this->relations[$rel_name]['relation_type']  === \GDAO\Model::RELATION_TYPE_HAS_MANY
        ) {
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
            [
                $fkey_col_in_foreign_table, $fkey_col_in_my_table, 
                $foreign_model_obj, $related_data
            ] = $this->getBelongsToOrHasOneOrHasManyData($rel_name, $parent_data);

            if ( 
                $parent_data instanceof \GDAO\Model\CollectionInterface
                || is_array($parent_data)
            ) {
                ///////////////////////////////////////////////////////////
                // Stitch the related data to the approriate parent records
                ///////////////////////////////////////////////////////////

                $fkey_val_to_related_data_keys = [];

                // Generate a map of 
                //      foreign key value => [keys of related rows in $related_data]
                foreach ($related_data as $curr_key => $related_datum) {

                    $curr_fkey_val = $related_datum[$fkey_col_in_foreign_table];

                    if(!array_key_exists($curr_fkey_val, $fkey_val_to_related_data_keys)) {

                        $fkey_val_to_related_data_keys[$curr_fkey_val] = [];
                    }

                    // Add current key in $related_data to sub array of keys for the 
                    // foreign key value in the current related row $related_datum
                    $fkey_val_to_related_data_keys[$curr_fkey_val][] = $curr_key;

                } // foreach ($related_data as $curr_key => $related_datum)

                // Now use $fkey_val_to_related_data_keys map to
                // look up related rows of data for each parent row of data
                foreach( $parent_data as $p_rec_key => $parent_row ) {

                    $matching_related_rows = [];

                    if(array_key_exists($parent_row[$fkey_col_in_my_table], $fkey_val_to_related_data_keys)) {

                        foreach ($fkey_val_to_related_data_keys[$parent_row[$fkey_col_in_my_table]] as $related_data_key) {

                            $matching_related_rows[] = $related_data[$related_data_key];
                        }
                    }

                    $this->wrapRelatedDataInsideRecordsAndCollection(
                        $matching_related_rows, $foreign_model_obj, 
                        $wrap_each_row_in_a_record, $wrap_records_in_collection
                    );

                    //set the related data for the current parent row / record
                    if( $parent_row instanceof \GDAO\Model\RecordInterface ) {

                        $parent_data[$p_rec_key]->setRelatedData($rel_name, $matching_related_rows);

                    } else {

                        //the current row must be an array
                        $parent_data[$p_rec_key][$rel_name] = $matching_related_rows;
                    }
                } // foreach( $parent_data as $p_rec_key => $parent_record )

                ////////////////////////////////////////////////////////////////
                // End: Stitch the related data to the approriate parent records
                ////////////////////////////////////////////////////////////////

            } else if ( $parent_data instanceof \GDAO\Model\RecordInterface ) {

                $this->wrapRelatedDataInsideRecordsAndCollection(
                    $related_data, $foreign_model_obj, 
                    $wrap_each_row_in_a_record, $wrap_records_in_collection
                );

                ///////////////////////////////////////////////
                //stitch the related data to the parent record
                ///////////////////////////////////////////////
                $parent_data->setRelatedData($rel_name, $related_data);

            } // else if ($parent_data instanceof \GDAO\Model\RecordInterface)
        } // if( array_key_exists($rel_name, $this->relations) )
    }
    
    /**
     * @param \GDAO\Model\RecordInterface|\GDAO\Model\CollectionInterface|array<string|int, array> $parent_data
     */
    protected function loadHasManyThrough( 
        string $rel_name, &$parent_data, bool $wrap_each_row_in_a_record=false, bool $wrap_records_in_collection=false 
    ): void {
        if( 
            array_key_exists($rel_name, $this->relations) 
            && $this->relations[$rel_name]['relation_type']  === \GDAO\Model::RELATION_TYPE_HAS_MANY_THROUGH
        ) {
            $rel_info = $this->relations[$rel_name];

            $foreign_table_name = Utils::arrayGet($rel_info, 'foreign_table');

            $fkey_col_in_foreign_table = 
                Utils::arrayGet($rel_info, 'col_in_foreign_table_linked_to_join_table');

            $foreign_models_class_name = 
                Utils::arrayGet($rel_info, 'foreign_models_class_name', \LeanOrm\Model::class);

            $pri_key_col_in_foreign_models_table = 
                Utils::arrayGet($rel_info, 'primary_key_col_in_foreign_table');

            $fkey_col_in_my_table = 
                    Utils::arrayGet($rel_info, 'col_in_my_table_linked_to_join_table');

            //join table params
            $join_table_name = Utils::arrayGet($rel_info, 'join_table');

            $col_in_join_table_linked_to_my_models_table = 
                Utils::arrayGet($rel_info, 'col_in_join_table_linked_to_my_table');

            $col_in_join_table_linked_to_foreign_models_table = 
                Utils::arrayGet($rel_info, 'col_in_join_table_linked_to_foreign_table');

            $sql_query_modifier = 
                Utils::arrayGet($rel_info, 'sql_query_modifier', null);

            $foreign_model_obj = 
                $this->createRelatedModelObject(
                    $foreign_models_class_name,
                    $pri_key_col_in_foreign_models_table,
                    $foreign_table_name
                );

            $foreign_models_collection_class_name = 
                Utils::arrayGet($rel_info, 'foreign_models_collection_class_name', '');

            $foreign_models_record_class_name = 
                Utils::arrayGet($rel_info, 'foreign_models_record_class_name', '');

            if($foreign_models_collection_class_name !== '') {

                $foreign_model_obj->setCollectionClassName($foreign_models_collection_class_name);
            }

            if($foreign_models_record_class_name !== '') {

                $foreign_model_obj->setRecordClassName($foreign_models_record_class_name);
            }

            $query_obj = $foreign_model_obj->getSelect();

            $query_obj->cols( [" {$join_table_name}.{$col_in_join_table_linked_to_my_models_table} ", " {$foreign_table_name}.* "] );

            $query_obj->innerJoin(
                            $join_table_name, 
                            " {$join_table_name}.{$col_in_join_table_linked_to_foreign_models_table} = {$foreign_table_name}.{$fkey_col_in_foreign_table} "
                        );

            if ( $parent_data instanceof \GDAO\Model\RecordInterface ) {

                $query_obj->where(
                    " {$join_table_name}.{$col_in_join_table_linked_to_my_models_table} = ? ",
                    $parent_data->$fkey_col_in_my_table
                );

            } else {

                //assume it's a collection or array
                $col_vals = $this->getColValsFromArrayOrCollection(
                                $parent_data, $fkey_col_in_my_table
                            );

                if( $col_vals !== [] ) {

                    $this->addWhereInAndOrIsNullToQuery(
                        "{$join_table_name}.{$col_in_join_table_linked_to_my_models_table}", 
                        $col_vals, 
                        $query_obj
                    );
                }
            }

            if(\is_callable($sql_query_modifier)) {

                $sql_query_modifier = Utils::getClosureFromCallable($sql_query_modifier);
                // modify the query object before executing the query
                $query_obj = $sql_query_modifier($query_obj);
            }

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
            $this->logQuery($sql_2_get_related_data, $params_2_bind_2_sql, __METHOD__, '' . __LINE__);

            //GRAB DA RELATED DATA
            $related_data = 
                $this->db_connector
                     ->dbFetchAll($sql_2_get_related_data, $params_2_bind_2_sql);

            if ( 
                $parent_data instanceof \GDAO\Model\CollectionInterface
                || is_array($parent_data)
            ) {
                ///////////////////////////////////////////////////////////
                // Stitch the related data to the approriate parent records
                ///////////////////////////////////////////////////////////

                $fkey_val_to_related_data_keys = [];

                // Generate a map of 
                //      foreign key value => [keys of related rows in $related_data]
                foreach ($related_data as $curr_key => $related_datum) {

                    $curr_fkey_val = $related_datum[$col_in_join_table_linked_to_my_models_table];

                    if(!array_key_exists($curr_fkey_val, $fkey_val_to_related_data_keys)) {

                        $fkey_val_to_related_data_keys[$curr_fkey_val] = [];
                    }

                    // Add current key in $related_data to sub array of keys for the 
                    // foreign key value in the current related row $related_datum
                    $fkey_val_to_related_data_keys[$curr_fkey_val][] = $curr_key;

                } // foreach ($related_data as $curr_key => $related_datum)

                // Now use $fkey_val_to_related_data_keys map to
                // look up related rows of data for each parent row of data
                foreach( $parent_data as $p_rec_key => $parent_row ) {

                    $matching_related_rows = [];

                    if(array_key_exists($parent_row[$fkey_col_in_my_table], $fkey_val_to_related_data_keys)) {

                        foreach ($fkey_val_to_related_data_keys[$parent_row[$fkey_col_in_my_table]] as $related_data_key) {

                            $matching_related_rows[] = $related_data[$related_data_key];
                        }
                    }

                    $this->wrapRelatedDataInsideRecordsAndCollection(
                        $matching_related_rows, $foreign_model_obj, 
                        $wrap_each_row_in_a_record, $wrap_records_in_collection
                    );

                    //set the related data for the current parent row / record
                    if( $parent_row instanceof \GDAO\Model\RecordInterface ) {

                        $parent_data[$p_rec_key]->setRelatedData($rel_name, $matching_related_rows);

                    } else {

                        //the current row must be an array
                        $parent_data[$p_rec_key][$rel_name] = $matching_related_rows;
                    }

                } // foreach( $parent_data as $p_rec_key => $parent_record )

                ////////////////////////////////////////////////////////////////
                // End: Stitch the related data to the approriate parent records
                ////////////////////////////////////////////////////////////////

            } else if ( $parent_data instanceof \GDAO\Model\RecordInterface ) {

                $this->wrapRelatedDataInsideRecordsAndCollection(
                    $related_data, $foreign_model_obj, 
                    $wrap_each_row_in_a_record, $wrap_records_in_collection
                );

                //stitch the related data to the parent record
                $parent_data->setRelatedData($rel_name, $related_data);
            } // else if ( $parent_data instanceof \GDAO\Model\RecordInterface )
        } // if( array_key_exists($rel_name, $this->relations) )
    }
    
    /**
     * @param \GDAO\Model\RecordInterface|\GDAO\Model\CollectionInterface|array<string|int, array> $parent_data
     */
    protected function loadHasOne( 
        string $rel_name, &$parent_data, bool $wrap_row_in_a_record=false
    ): void {
        if( 
            array_key_exists($rel_name, $this->relations) 
            && $this->relations[$rel_name]['relation_type']  === \GDAO\Model::RELATION_TYPE_HAS_ONE
        ) {
            [
                $fkey_col_in_foreign_table, $fkey_col_in_my_table, 
                $foreign_model_obj, $related_data
            ] = $this->getBelongsToOrHasOneOrHasManyData($rel_name, $parent_data);

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

            if ( 
                $parent_data instanceof \GDAO\Model\CollectionInterface
                || is_array($parent_data)
            ) {
                ///////////////////////////////////////////////////////////
                // Stitch the related data to the approriate parent records
                ///////////////////////////////////////////////////////////

                $fkey_val_to_related_data_keys = [];

                // Generate a map of 
                //      foreign key value => [keys of related rows in $related_data]
                foreach ($related_data as $curr_key => $related_datum) {

                    $curr_fkey_val = $related_datum[$fkey_col_in_foreign_table];

                    if(!array_key_exists($curr_fkey_val, $fkey_val_to_related_data_keys)) {

                        $fkey_val_to_related_data_keys[$curr_fkey_val] = [];
                    }

                    // Add current key in $related_data to sub array of keys for the 
                    // foreign key value in the current related row $related_datum
                    $fkey_val_to_related_data_keys[$curr_fkey_val][] = $curr_key;

                } // foreach ($related_data as $curr_key => $related_datum)

                // Now use $fkey_val_to_related_data_keys map to
                // look up related rows of data for each parent row of data
                foreach( $parent_data as $p_rec_key => $parent_row ) {

                    $matching_related_rows = [];

                    if(array_key_exists($parent_row[$fkey_col_in_my_table], $fkey_val_to_related_data_keys)) {

                        foreach ($fkey_val_to_related_data_keys[$parent_row[$fkey_col_in_my_table]] as $related_data_key) {

                            // There should really only be one matching related 
                            // record per parent record since this is a hasOne
                            // relationship
                            $matching_related_rows[] = $related_data[$related_data_key];
                        }
                    }

                    $this->wrapRelatedDataInsideRecordsAndCollection(
                        $matching_related_rows, $foreign_model_obj, 
                        $wrap_row_in_a_record, false
                    );

                    //set the related data for the current parent row / record
                    if( $parent_row instanceof \GDAO\Model\RecordInterface ) {

                        // There should really only be one matching related 
                        // record per parent record since this is a hasOne
                        // relationship. That's why we are doing 
                        // $matching_related_rows[0]
                        $parent_data[$p_rec_key]->setRelatedData($rel_name, $matching_related_rows[0]);

                    } else {

                        // There should really only be one matching related 
                        // record per parent record since this is a hasOne
                        // relationship. That's why we are doing 
                        // $matching_related_rows[0]

                        //the current row must be an array
                        $parent_data[$p_rec_key][$rel_name] = $matching_related_rows[0];
                    }
                } // foreach( $parent_data as $p_rec_key => $parent_record )

                ////////////////////////////////////////////////////////////////
                // End: Stitch the related data to the approriate parent records
                ////////////////////////////////////////////////////////////////

            } else if ( $parent_data instanceof \GDAO\Model\RecordInterface ) {

                $this->wrapRelatedDataInsideRecordsAndCollection(
                            $related_data, $foreign_model_obj, 
                            $wrap_row_in_a_record, false
                        );

                //stitch the related data to the parent record
                $parent_data->setRelatedData($rel_name, array_shift($related_data));
            } // else if ($parent_data instanceof \GDAO\Model\RecordInterface)
        } // if( array_key_exists($rel_name, $this->relations) )
    }
    
    /**
     * @param \GDAO\Model\RecordInterface|\GDAO\Model\CollectionInterface|array<string|int, array> $parent_data
     */
    protected function loadBelongsTo(string $rel_name, &$parent_data, bool $wrap_row_in_a_record=false): void {

        if( 
            array_key_exists($rel_name, $this->relations) 
            && $this->relations[$rel_name]['relation_type']  === \GDAO\Model::RELATION_TYPE_BELONGS_TO
        ) {
            //quick hack
            $this->relations[$rel_name]['relation_type'] = \GDAO\Model::RELATION_TYPE_HAS_ONE;

            //I really don't see the difference in the sql to fetch data for
            //a has-one relationship and a belongs-to relationship. Hence, I
            //have resorted to using the same code to satisfy both relationships
            $this->loadHasOne($rel_name, $parent_data, $wrap_row_in_a_record);

            //undo quick hack
            $this->relations[$rel_name]['relation_type'] = \GDAO\Model::RELATION_TYPE_BELONGS_TO;
        }
    }
    
    /**
     * @param \GDAO\Model\RecordInterface|\GDAO\Model\CollectionInterface|array<string|int, array> $parent_data
     * @return mixed[]
     */
    protected function getBelongsToOrHasOneOrHasManyData(string $rel_name, &$parent_data): array {

        $rel_info = $this->relations[$rel_name];

        $foreign_table_name = Utils::arrayGet($rel_info, 'foreign_table');

        $fkey_col_in_foreign_table = 
            Utils::arrayGet($rel_info, 'foreign_key_col_in_foreign_table');

        $foreign_models_class_name = 
            Utils::arrayGet($rel_info, 'foreign_models_class_name', \LeanOrm\Model::class);

        $pri_key_col_in_foreign_models_table = 
            Utils::arrayGet($rel_info, 'primary_key_col_in_foreign_table');

        $fkey_col_in_my_table = 
                Utils::arrayGet($rel_info, 'foreign_key_col_in_my_table');

        $sql_query_modifier = 
                Utils::arrayGet($rel_info, 'sql_query_modifier', null);

        $foreign_model_obj = $this->createRelatedModelObject(
                                        $foreign_models_class_name,
                                        $pri_key_col_in_foreign_models_table,
                                        $foreign_table_name
                                    );
        
        $foreign_models_collection_class_name = 
            Utils::arrayGet($rel_info, 'foreign_models_collection_class_name', '');

        $foreign_models_record_class_name = 
            Utils::arrayGet($rel_info, 'foreign_models_record_class_name', '');

        if($foreign_models_collection_class_name !== '') {

            $foreign_model_obj->setCollectionClassName($foreign_models_collection_class_name);
        }

        if($foreign_models_record_class_name !== '') {

            $foreign_model_obj->setRecordClassName($foreign_models_record_class_name);
        }

        $query_obj = $foreign_model_obj->getSelect();

        if ( $parent_data instanceof \GDAO\Model\RecordInterface ) {

            $query_obj->where(
                " {$foreign_table_name}.{$fkey_col_in_foreign_table} = ? ",
                $parent_data->$fkey_col_in_my_table
            );

        } else {
            //assume it's a collection or array                
            $col_vals = $this->getColValsFromArrayOrCollection(
                            $parent_data, $fkey_col_in_my_table
                        );

            if( $col_vals !== [] ) {
                
                $this->addWhereInAndOrIsNullToQuery(
                    "{$foreign_table_name}.{$fkey_col_in_foreign_table}", 
                    $col_vals, 
                    $query_obj
                );
            }
        }

        if(\is_callable($sql_query_modifier)) {

            $sql_query_modifier = Utils::getClosureFromCallable($sql_query_modifier);
            
            // modify the query object before executing the query 
            $query_obj = $sql_query_modifier($query_obj);
        }

        if(!$query_obj->hasCols()){

            $query_obj->cols(["{$foreign_table_name}.*"]);
        }

        $params_2_bind_2_sql = $query_obj->getBindValues();
        $sql_2_get_related_data = $query_obj->__toString();
        $this->logQuery($sql_2_get_related_data, $params_2_bind_2_sql, __METHOD__, '' . __LINE__);
        
        return [
            $fkey_col_in_foreign_table, $fkey_col_in_my_table, $foreign_model_obj,
            $this->db_connector->dbFetchAll($sql_2_get_related_data, $params_2_bind_2_sql) // fetch the related data
        ]; 
    }

    protected function createRelatedModelObject(
        string $f_models_class_name, 
        string $pri_key_col_in_f_models_table, 
        string $f_table_name
    ): Model {
        //$foreign_models_class_name will never be empty it will default to \LeanOrm\Model
        //$foreign_table_name will never be empty because it is needed for fetching the 
        //related data
        if( empty($f_models_class_name) ) {

            $f_models_class_name = \LeanOrm\Model::class;
        }

        try {
            //try to create a model object for the related data
            $related_model = new $f_models_class_name(
                $this->dsn, 
                $this->username, 
                $this->passwd, 
                $this->pdo_driver_opts,
                $pri_key_col_in_f_models_table,
                $f_table_name
            );
            
        } catch (\GDAO\ModelPrimaryColNameNotSetDuringConstructionException $e) {
            
            $msg = "ERROR: Couldn't create foreign model of type '{$f_models_class_name}'."
                 . "  No primary key supplied for the database table '{$f_table_name}'"
                 . " associated with the foreign table class '{$f_models_class_name}'."
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;
            throw new RelatedModelNotCreatedException($msg);
            
        } catch (\GDAO\ModelTableNameNotSetDuringConstructionException $e) {
            
            $msg = "ERROR: Couldn't create foreign model of type '{$f_models_class_name}'."
                 . "  No database table name supplied."
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;
            throw new RelatedModelNotCreatedException($msg);
            
        } catch (BadModelTableNameException $e) {
            
            $msg = "ERROR: Couldn't create foreign model of type '{$f_models_class_name}'."
                 . " The supplied table name `{$f_table_name}` does not exist as a table or"
                 . " view in the database."
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;
            throw new RelatedModelNotCreatedException($msg);
            
        } catch(BadModelPrimaryColumnNameException $e) {
            
            $msg = "ERROR: Couldn't create foreign model of type '{$f_models_class_name}'."
                 . " The supplied primary key column `{$pri_key_col_in_f_models_table}` "
                 . " does not exist in the supplied table named `{$f_table_name}`."
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;
            throw new RelatedModelNotCreatedException($msg);
        }
        
        if($this->canLogQueries()) {
            
            // Transfer logger settings from this model
            // to the newly created model
            $related_model->enableQueryLogging();
        }
        
        if( 
            $this->getLogger() instanceof \Psr\Log\LoggerInterface
            && $related_model->getLogger() === null
        ) {
            $related_model->setLogger($this->getLogger());
        }
        
        return $related_model;
    }

    /**
     * @param \GDAO\Model\CollectionInterface|array<string|int, array> $parent_data
     * @return mixed[]
     */
    protected function getColValsFromArrayOrCollection(
        &$parent_data, string $fkey_col_in_my_table
    ): array {
        $col_vals = [];

        if ( is_array($parent_data) ) {

            foreach($parent_data as $data) {

                $col_vals[] = $data[$fkey_col_in_my_table];
            }

        } elseif($parent_data instanceof \GDAO\Model\CollectionInterface) {

            $col_vals = $parent_data->getColVals($fkey_col_in_my_table);
        }

        return $col_vals;
    }

    protected function wrapRelatedDataInsideRecordsAndCollection(
        array &$matching_related_records, Model $foreign_model_obj, 
        bool $wrap_each_row_in_a_record, bool $wrap_records_in_collection
    ): void {
        
        if( $wrap_each_row_in_a_record ) {

            //wrap into records of the appropriate class
            foreach ($matching_related_records as $key=>$rec_data) {
                
                // Mark as not new because this is a related row of data that 
                // already exists in the db as opposed to a row of data that
                // has never been saved to the db
                $matching_related_records[$key] = 
                    $foreign_model_obj->createNewRecord($rec_data)
                                      ->markAsNotNew();
            }
        }

        if($wrap_records_in_collection) {
            
            $matching_related_records = 
                $foreign_model_obj->createNewCollection(...$matching_related_records);
        }
    }

    /**
     * 
     * Fetches a collection by primary key value(s).
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
     * @param array $ids an array of scalar values of the primary key field of db rows to be fetched
     * 
     * @param bool $use_records true if each matched db row should be wrapped in 
     *                          an instance of \LeanOrm\Model\Record; false if 
     *                          rows should be returned as associative php 
     *                          arrays. If $use_collections === true, records
     *                          will be returned inside a collection regardless
     *                          of the value of $use_records
     * 
     * @param bool $use_collections true if each matched db row should be wrapped
     *                              in an instance of \LeanOrm\Model\Record and 
     *                              all the records wrapped in an instance of
     *                              \LeanOrm\Model\Collection; false if all 
     *                              matched db rows should be returned in a
     *                              php array
     * 
     * @param bool $use_p_k_val_as_key true means the collection or array returned should be keyed on the primary key values
     * 
     * @return array|\LeanOrm\Model\Collection 
     * 
     */
    public function fetch(
        array $ids, 
        ?\Aura\SqlQuery\Common\Select $select_obj=null, 
        array $relations_to_include=[], 
        bool $use_records=false, 
        bool $use_collections=false, 
        bool $use_p_k_val_as_key=false
    ) {
        $select_obj ??= $this->createQueryObjectIfNullAndAddColsToQuery($select_obj);
        
        if( $ids !== [] ) {
            
            $result = [];
            $this->addWhereInAndOrIsNullToQuery($this->getPrimaryCol(), $ids, $select_obj);

            if( $use_collections ) {

                $result = ($use_p_k_val_as_key) 
                            ? $this->fetchRecordsIntoCollectionKeyedOnPkVal($select_obj, $relations_to_include) 
                            : $this->fetchRecordsIntoCollection($select_obj, $relations_to_include);

            } else {

                if( $use_records ) {

                    $result = ($use_p_k_val_as_key) 
                                ? $this->fetchRecordsIntoArrayKeyedOnPkVal($select_obj, $relations_to_include) 
                                : $this->fetchRecordsIntoArray($select_obj, $relations_to_include);
                } else {

                    //default
                    $result = ($use_p_k_val_as_key) 
                                ? $this->fetchRowsIntoArrayKeyedOnPkVal($select_obj, $relations_to_include) 
                                : $this->fetchRowsIntoArray($select_obj, $relations_to_include);
                } // if( $use_records ) else ...
            } // if( $use_collections ) else ...
            
            if(!($result instanceof \GDAO\Model\CollectionInterface) && !is_array($result)) {
               
                return $use_collections ? $this->createNewCollection() : [];
                
            } 
            
            return $result;
            
        } // if( $ids !== [] )

        // return empty collection or array
        return $use_collections ? $this->createNewCollection() : [];
    }

    /**
     * {@inheritDoc}
     */
    public function fetchRecordsIntoCollection(?object $select_obj=null, array $relations_to_include=[]): \GDAO\Model\CollectionInterface {

        return $this->doFetchRecordsIntoCollection($select_obj, $relations_to_include);
    }

    public function fetchRecordsIntoCollectionKeyedOnPkVal(?\Aura\SqlQuery\Common\Select $select_obj=null, array $relations_to_include=[]): \GDAO\Model\CollectionInterface {

        return $this->doFetchRecordsIntoCollection($select_obj, $relations_to_include, true);
    }

    protected function doFetchRecordsIntoCollection(
        ?\Aura\SqlQuery\Common\Select $select_obj=null, 
        array $relations_to_include=[], 
        bool $use_p_k_val_as_key=false
    ) {
        $results = $this->createNewCollection();
        $data = $this->getArrayOfRecordObjects($select_obj, $use_p_k_val_as_key);

        if($data !== [] ) {

            if($use_p_k_val_as_key) {
                
                foreach ($data as $pkey => $current_record) {
                    
                    $results[$pkey] = $current_record;
                }
                
            } else {
               
                $results = $this->createNewCollection(...$data);
            }

            foreach( $relations_to_include as $rel_name ) {

                $this->loadRelationshipData($rel_name, $results, true, true);
            }
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchRecordsIntoArray(?object $select_obj=null, array $relations_to_include=[]): array {
        
        return $this->doFetchRecordsIntoArray($select_obj, $relations_to_include);
    }

    /**
     * @return \GDAO\Model\RecordInterface[]
     */
    public function fetchRecordsIntoArrayKeyedOnPkVal(?\Aura\SqlQuery\Common\Select $select_obj=null, array $relations_to_include=[]): array {
        
        return $this->doFetchRecordsIntoArray($select_obj, $relations_to_include, true);
    }

    /**
     * @return \GDAO\Model\RecordInterface[]
     */
    protected function doFetchRecordsIntoArray(
        ?\Aura\SqlQuery\Common\Select $select_obj=null, 
        array $relations_to_include=[], 
        bool $use_p_k_val_as_key=false
    ): array {
        $results = $this->getArrayOfRecordObjects($select_obj, $use_p_k_val_as_key);

        if( $results !== [] ) {

            foreach( $relations_to_include as $rel_name ) {

                $this->loadRelationshipData($rel_name, $results, true);
            }
        }

        return $results;
    }

    /**
     * @return \GDAO\Model\RecordInterface[]
     */
    protected function getArrayOfRecordObjects(?\Aura\SqlQuery\Common\Select $select_obj=null, bool $use_p_k_val_as_key=false): array {

        $results = $this->getArrayOfDbRows($select_obj, $use_p_k_val_as_key);

        foreach ($results as $key=>$value) {

            $results[$key] = $this->createNewRecord($value)->markAsNotNew();
        }
        
        return $results;
    }

    /**
     * @return mixed[]
     */
    protected function getArrayOfDbRows(?\Aura\SqlQuery\Common\Select $select_obj=null, bool $use_p_k_val_as_key=false): array {

        $query_obj = $this->createQueryObjectIfNullAndAddColsToQuery($select_obj);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();
        $this->logQuery($sql, $params_2_bind_2_sql, __METHOD__, '' . __LINE__);

        $results = $this->db_connector->dbFetchAll($sql, $params_2_bind_2_sql);
        
        if( $use_p_k_val_as_key && $results !== [] && $this->getPrimaryCol() !== '' ) {

            $results_keyed_by_pk = [];

            foreach( $results as $result ) {

                if( !array_key_exists($this->getPrimaryCol(), $result) ) {

                    $msg = "ERROR: Can't key fetch results by Primary Key value."
                         . PHP_EOL . " One or more result rows has no Primary Key field (`{$this->getPrimaryCol()}`)" 
                         . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).'
                         . PHP_EOL . 'Fetch Results:' . PHP_EOL . var_export($results, true) . PHP_EOL
                         . PHP_EOL . "Row without Primary Key field (`{$this->getPrimaryCol()}`):" . PHP_EOL . var_export($result, true) . PHP_EOL;

                    throw new \LeanOrm\KeyingFetchResultsByPrimaryKeyFailedException($msg);
                }

                // key on primary key value
                $results_keyed_by_pk[$result[$this->getPrimaryCol()]] = $result;
            }

            $results = $results_keyed_by_pk;
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchRowsIntoArray(?object $select_obj=null, array $relations_to_include=[]): array {

        return $this->doFetchRowsIntoArray($select_obj, $relations_to_include);
    }

    /**
     * @return array[]
     */
    public function fetchRowsIntoArrayKeyedOnPkVal(?\Aura\SqlQuery\Common\Select $select_obj=null, array $relations_to_include=[]): array {

        return $this->doFetchRowsIntoArray($select_obj, $relations_to_include, true);
    }

    /**
     * @return array[]
     */
    protected function doFetchRowsIntoArray(
        ?\Aura\SqlQuery\Common\Select $select_obj=null, 
        array $relations_to_include=[], 
        bool $use_p_k_val_as_key=false
    ): array {
        $results = $this->getArrayOfDbRows($select_obj, $use_p_k_val_as_key);
        
        if( $results !== [] ) {

            foreach( $relations_to_include as $rel_name ) {

                $this->loadRelationshipData($rel_name, $results);
            }
        }

        return $results;
    }

    public function getPDO(): \PDO {

        //return pdo object associated with the current dsn
        return DBConnector::getDb($this->dsn); 
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMatchingDbTableRows(array $cols_n_vals): int {

        $result = 0;

        if ( $cols_n_vals !== [] ) {

            //delete statement
            $del_qry_obj = (new QueryFactory($this->getPdoDriverName()))->newDelete();
            $del_qry_obj->from($this->getTableName());
            $table_cols = $this->getTableColNames();

            foreach ($cols_n_vals as $colname => $colval) {

                if(!in_array($colname, $table_cols)) {

                    // specified column is not a valid db table col, remove it
                    unset($cols_n_vals[$colname]);
                    continue;
                }

                if (is_array($colval)) {

                    foreach($colval as $key=>$val) {

                        if(!$this->isAcceptableDeleteQueryValue($val)) {

                            $this->throwExceptionForInvalidDeleteQueryArg($val, $cols_n_vals);
                        }

                        $colval[$key] = $this->stringifyIfStringable($val);
                    }

                    $this->addWhereInAndOrIsNullToQuery($colname, $colval, $del_qry_obj);

                } else {

                    if(!$this->isAcceptableDeleteQueryValue($colval)) {

                        $this->throwExceptionForInvalidDeleteQueryArg($colval, $cols_n_vals);
                    }

                    $del_qry_obj->where("{$colname} = ?", $this->stringifyIfStringable($colval));
                }
            }

            // if at least one of the column names in the array is an actual 
            // db table columns, then do delete
            if($cols_n_vals !== []) {

                $dlt_qry = $del_qry_obj->__toString();
                $dlt_qry_params = $del_qry_obj->getBindValues();
                $this->logQuery($dlt_qry, $dlt_qry_params, __METHOD__, '' . __LINE__);

                $result = $this->db_connector->executeQuery($dlt_qry, $dlt_qry_params, true); 

                if( $result['query_result'] === true ) {

                    //return number of affected rows
                    $pdo_statement_used_for_query = $result['pdo_statement'];
                    $result = $pdo_statement_used_for_query->rowCount();
                } else {

                    // something went wrong
                    // TODO: Maybe throw an exception
                    $result = 0;
                } // if( $result['query_result'] === true )
            } // if($cols_n_vals !== []) 
        } // if ( $cols_n_vals !== [] )

        return $result;
    }
    
    /**
     * @return never
     */
    protected function throwExceptionForInvalidDeleteQueryArg($val, array $cols_n_vals): void {

        $msg = "ERROR: the value "
             . PHP_EOL . var_export($val, true) . PHP_EOL
             . " you are trying to use to bulid the where clause for deleting from the table `{$this->getTableName()}`"
             . " is not acceptable ('".  gettype($val) . "'"
             . " supplied). Boolean, NULL, numeric or string value expected."
             . PHP_EOL
             . "Data supplied to "
             . get_class($this) . '::' . __FUNCTION__ . '(...).' 
             . " for buiding the where clause for the deletion:"
             . PHP_EOL . var_export($cols_n_vals, true) . PHP_EOL
             . PHP_EOL;

        throw new InvalidArgumentException($msg);
    }
    
    /**
     * {@inheritDoc}
     */
    public function deleteSpecifiedRecord(\GDAO\Model\RecordInterface $record): ?bool {

        $succesfully_deleted = null;

        if( $record instanceof \LeanOrm\Model\ReadOnlyRecord ) {

            $msg = "ERROR: Can't delete ReadOnlyRecord from the database in " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).'
                 . PHP_EOL .'Undeleted record' . var_export($record, true) . PHP_EOL;
            throw new \LeanOrm\CantDeleteReadOnlyRecordFromDBException($msg);
        }
        
        if( 
            $record->getModel()->getTableName() !== $this->getTableName() 
            || get_class($record->getModel()) !== get_class($this)  
        ) {
            $msg = "ERROR: Can't delete a record (an instance of `%s` belonging to the Model class `%s`) belonging to the database table `%s` " 
                . "using a Model instance of `%s` belonging to the database table `%s` in " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).'
                 . PHP_EOL .'Undeleted record: ' . PHP_EOL . var_export($record, true) . PHP_EOL; 
            throw new InvalidArgumentException(
                sprintf(
                    $msg, get_class($record), get_class($record->getModel()), 
                    $record->getModel()->getTableName(),
                    get_class($this), $this->getTableName()
                )
            );
        }

        if ( $record->getData() !== [] ) { //test if the record object has data

            $pri_key_val = $record->getPrimaryVal();
            $cols_n_vals = [$record->getPrimaryCol() => $pri_key_val];

            $succesfully_deleted = 
                $this->deleteMatchingDbTableRows($cols_n_vals);

            if ( $succesfully_deleted === 1 ) {
                
                $record->markAsNew();
                
                foreach ($this->getRelationNames() as $relation_name) {
                    
                    // Remove all the related data since the primary key of the 
                    // record may change or there may be ON DELETE CASACADE 
                    // constraints that may have triggred those records being 
                    // deleted from the db because of the deletion of this record
                    unset($record[$relation_name]);
                }
                
                if(
                    $this->table_cols[$record->getPrimaryCol()]['autoinc']
                ) {
                    // unset the primary key value for auto-incrementing
                    // primary key cols. It is actually set to null via
                    // Record::offsetUnset(..)
                    unset($record[$this->getPrimaryCol()]); 
                }
                
            } elseif($succesfully_deleted === 0) {
                
                $succesfully_deleted = null;
                
            } elseif( $this->fetch([$pri_key_val], null, [], true, true)->count() === 1 ) {
                
                //we were still able to fetch the record from the db, so delete failed
                $succesfully_deleted = false;
            }
        }

        return ( $succesfully_deleted === 1 )? true : $succesfully_deleted;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchCol(?object $select_obj=null): array {

        $query_obj = $this->createQueryObjectIfNullAndAddColsToQuery($select_obj);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();
        $this->logQuery($sql, $params_2_bind_2_sql, __METHOD__, '' . __LINE__);

        return $this->db_connector->dbFetchCol($sql, $params_2_bind_2_sql);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOneRecord(?object $select_obj=null, array $relations_to_include=[]): ?\GDAO\Model\RecordInterface {

        $query_obj = $this->createQueryObjectIfNullAndAddColsToQuery($select_obj);
        $query_obj->limit(1);

        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();
        $this->logQuery($sql, $params_2_bind_2_sql, __METHOD__, '' . __LINE__);

        $result = $this->db_connector->dbFetchOne($sql, $params_2_bind_2_sql);

        if( $result !== false && is_array($result) && $result !== [] ) {

            $result = $this->createNewRecord($result)->markAsNotNew();

            foreach( $relations_to_include as $rel_name ) {

                $this->loadRelationshipData($rel_name, $result, true, true);
            }
        }
        
        if(!($result instanceof \GDAO\Model\RecordInterface)) {
            
            $result = null;
        }

        return $result;
    }

    /**
     * Convenience method to fetch one record by the specified primary key value.
     * 
     * @param string|int $id
     * @param array $relations_to_include
     * 
     * @return \GDAO\Model\RecordInterface|null
     */
    public function fetchOneByPkey($id, $relations_to_include = []): ?\GDAO\Model\RecordInterface {
        
        $select = $this->getSelect();
        $select->where(" {$this->getPrimaryCol()} = ? ", [$id]);
        
        return $this->fetchOneRecord($select, $relations_to_include);
    }
    
    /**
     * {@inheritDoc}
     */
    public function fetchPairs(?object $select_obj=null): array {

        $query_obj = $this->createQueryObjectIfNullAndAddColsToQuery($select_obj);
        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();
        $this->logQuery($sql, $params_2_bind_2_sql, __METHOD__, '' . __LINE__);

        return $this->db_connector->dbFetchPairs($sql, $params_2_bind_2_sql);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchValue(?object $select_obj=null) {

        $query_obj = $this->createQueryObjectIfNullAndAddColsToQuery($select_obj);
        $query_obj->limit(1);

        $query_obj_4_num_matching_rows = clone $query_obj;

        $sql = $query_obj->__toString();
        $params_2_bind_2_sql = $query_obj->getBindValues();
        $this->logQuery($sql, $params_2_bind_2_sql, __METHOD__, '' . __LINE__);

        $result = $this->db_connector->dbFetchValue($sql, $params_2_bind_2_sql);

        // need to issue a second query to get the number of matching rows
        // clear the cols part of the query above while preserving all the
        // other parts of the query
        $query_obj_4_num_matching_rows->resetCols();
        $query_obj_4_num_matching_rows->cols([' COUNT(*) AS num_rows']);

        $sql = $query_obj_4_num_matching_rows->__toString();
        $params_2_bind_2_sql = $query_obj_4_num_matching_rows->getBindValues();
        $this->logQuery($sql, $params_2_bind_2_sql, __METHOD__, '' . __LINE__);

        $num_matching_rows = $this->db_connector->dbFetchOne($sql, $params_2_bind_2_sql);

        //return null if there wasn't any matching row
        return (((int)$num_matching_rows['num_rows']) > 0) ? $result : null;
    }
    
    protected function addTimestampToData(array &$data, ?string $timestamp_col_name, array $table_cols): void {
        
        if(
            !empty($timestamp_col_name) 
            && in_array($timestamp_col_name, $table_cols)
            && 
            (
                !array_key_exists($timestamp_col_name, $data)
                || empty($data[$timestamp_col_name])
            )
        ) {
            //set timestamp to now
            $data[$timestamp_col_name] = date('Y-m-d H:i:s');
        }
    }

    /**
     * @return mixed
     */
    protected function stringifyIfStringable($col_val, string $col_name='', array $table_cols=[]) {
        
        if(
            ( 
                ($col_name === '' && $table_cols === []) 
                || in_array($col_name, $table_cols) 
            )
            && is_object($col_val) && method_exists($col_val, '__toString')
        ) {
            return $col_val->__toString();
        }
        
        return $col_val;
    }
        
    protected function isAcceptableInsertValue($val): bool {
        
        return is_bool($val) || is_null($val) || is_numeric($val) || is_string($val)
               || ( is_object($val) && method_exists($val, '__toString') );
    }
    
    protected function isAcceptableUpdateValue($val): bool {
        
        return $this->isAcceptableInsertValue($val);
    }
    
    protected function isAcceptableUpdateQueryValue($val): bool {
        
        return $this->isAcceptableUpdateValue($val);
    }
    
    protected function isAcceptableDeleteQueryValue($val): bool {
        
        return $this->isAcceptableUpdateQueryValue($val);
    }

    protected function processRowOfDataToInsert(
        array &$data, array &$table_cols, bool &$has_autoinc_pk_col=false
    ): void {

        $this->addTimestampToData($data, $this->created_timestamp_column_name, $table_cols);
        $this->addTimestampToData($data, $this->updated_timestamp_column_name, $table_cols);

        // remove non-existent table columns from the data and also
        // converts object values for objects with __toString() to 
        // their string value
        foreach ($data as $key => $val) {

            $data[$key] = $this->stringifyIfStringable($val, $key, $table_cols);

            if ( !in_array($key, $table_cols) ) {

                unset($data[$key]);
                // not in the table, so no need to check for autoinc
                continue;

            } elseif( !$this->isAcceptableInsertValue($val) ) {

                $msg = "ERROR: the value "
                     . PHP_EOL . var_export($val, true) . PHP_EOL
                     . " you are trying to insert into `{$this->getTableName()}`."
                     . "`{$key}` is not acceptable ('".  gettype($val) . "'"
                     . " supplied). Boolean, NULL, numeric or string value expected."
                     . PHP_EOL
                     . "Data supplied to "
                     . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                     . " for insertion:"
                     . PHP_EOL . var_export($data, true) . PHP_EOL
                     . PHP_EOL;

                throw new \GDAO\ModelInvalidInsertValueSuppliedException($msg);
            }

            // Code below was lifted from Solar_Sql_Model::insert()
            // remove empty autoinc columns to soothe postgres, which won't
            // take explicit NULLs in SERIAL cols.
            if ( $this->table_cols[$key]['autoinc'] && empty($val) ) {

                unset($data[$key]);

            } // if ( $this->table_cols[$key]['autoinc'] && empty($val) )
        } // foreach ($data as $key => $val)

        foreach($this->table_cols as $col_name=>$col_info) {

            if ( $col_info['autoinc'] === true && $col_info['primary'] === true ) {

                if(array_key_exists($col_name, $data)) {

                    //no need to add primary key value to the insert 
                    //statement since the column is auto incrementing
                    unset($data[$col_name]);

                } // if(array_key_exists($col_name, $data_2_insert))

                $has_autoinc_pk_col = true;

            } // if ( $col_info['autoinc'] === true && $col_info['primary'] === true )
        } // foreach($this->table_cols as $col_name=>$col_info)
    }
    
    protected function updateInsertDataArrayWithTheNewlyInsertedRecordFromDB(
        array &$data_2_insert, array $table_cols
    ): void {
        
        if(
            array_key_exists($this->getPrimaryCol(), $data_2_insert)
            && !empty($data_2_insert[$this->getPrimaryCol()])
        ) {
            $data_2_insert = 
                $this->fetchOneRecord(
                        $this->getSelect()
                             ->where(
                                " {$this->getPrimaryCol()} = ? ",
                                $data_2_insert[$this->getPrimaryCol()]
                             )
                     )->getData();
        } else {

            // we don't have the primary key.
            // Do a select using all the fields.
            // If only one record is returned, we have found
            // the record we just inserted, else we return $data_2_insert as is 

            $select = $this->getSelect();

            foreach ($data_2_insert as $col => $val) {

                $processed_val = $this->stringifyIfStringable($val, $col, $table_cols);

                if(is_string($processed_val) || is_numeric($processed_val)) {

                    $select->where(" {$col} = ? ", $val);

                } elseif(is_null($processed_val) && $this->getPrimaryCol() !== $col) {

                    $select->where(" {$col} IS NULL ");
                } // if(is_string($processed_val) || is_numeric($processed_val))
            } // foreach ($data_2_insert as $col => $val)

            $matching_rows = $this->fetchRowsIntoArray($select);

            if(count($matching_rows) === 1) {

                $data_2_insert = array_pop($matching_rows);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function insert(array $data_2_insert = []) {

        $result = false;

        if ( $data_2_insert !== [] ) {

            $table_cols = $this->getTableColNames();
            $has_autoinc_pkey_col=false;

            $this->processRowOfDataToInsert(
                $data_2_insert, $table_cols, $has_autoinc_pkey_col
            );

            // Do we still have anything left to save after removing items
            // in the array that do not map to actual db table columns
            if( (is_countable($data_2_insert) ? count($data_2_insert) : 0) > 0 ) {

                //Insert statement
                $insrt_qry_obj = (new QueryFactory($this->getPdoDriverName()))->newInsert();
                $insrt_qry_obj->into($this->getTableName())->cols($data_2_insert);

                $insrt_qry_sql = $insrt_qry_obj->__toString();
                $insrt_qry_params = $insrt_qry_obj->getBindValues();
                $this->logQuery($insrt_qry_sql, $insrt_qry_params, __METHOD__, '' . __LINE__);

                if( $this->db_connector->executeQuery($insrt_qry_sql, $insrt_qry_params) ) {

                    // insert was successful, we are now going to try to 
                    // fetch the inserted record from the db to get and 
                    // return the db representation of the data
                    if($has_autoinc_pkey_col) {

                        $last_insert_sequence_name = 
                            $insrt_qry_obj->getLastInsertIdName($this->getPrimaryCol());

                        $pk_val_4_new_record = 
                            $this->getPDO()->lastInsertId($last_insert_sequence_name);

                        // Add retrieved primary key value 
                        // or null (if primary key value is empty) 
                        // to the data to be returned.
                        $data_2_insert[$this->primary_col] = 
                            empty($pk_val_4_new_record) ? null : $pk_val_4_new_record;

                        $this->updateInsertDataArrayWithTheNewlyInsertedRecordFromDB(
                            $data_2_insert, $table_cols
                        );

                    } else {

                        $this->updateInsertDataArrayWithTheNewlyInsertedRecordFromDB(
                            $data_2_insert, $table_cols
                        );

                    } // if($has_autoinc_pkey_col)

                    //insert was successful
                    $result = $data_2_insert;

                } // if( $this->db_connector->executeQuery($insrt_qry_sql, $insrt_qry_params) )
            } // if(count($data_2_insert) > 0 ) 
        } // if ( $data_2_insert !== [] )

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function insertMany(array $rows_of_data_2_insert = []): bool {

        $result = false;

        if ($rows_of_data_2_insert !== []) {

            $table_cols = $this->getTableColNames();

            foreach (array_keys($rows_of_data_2_insert) as $key) {

                if( !is_array($rows_of_data_2_insert[$key]) ) {

                    $item_type = gettype($rows_of_data_2_insert[$key]);

                    $msg = "ERROR: " . get_class($this) . '::' . __FUNCTION__ . '(...)' 
                         . " expects you to supply an array of arrays."
                         . " One of the items in the array supplied is not an array."
                         . PHP_EOL . " Item below of type `{$item_type}` is not an array: "
                         . PHP_EOL . var_export($rows_of_data_2_insert[$key], true) 
                         . PHP_EOL . PHP_EOL . "Data supplied to "
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . " for insertion into the db table `{$this->getTableName()}`:"
                         . PHP_EOL . var_export($rows_of_data_2_insert, true) . PHP_EOL
                         . PHP_EOL;

                    throw new \GDAO\ModelInvalidInsertValueSuppliedException($msg);
                }

                $this->processRowOfDataToInsert($rows_of_data_2_insert[$key], $table_cols);

                if((is_countable($rows_of_data_2_insert[$key]) ? count($rows_of_data_2_insert[$key]) : 0) === 0) {

                    // all the keys in the curent row of data aren't valid
                    // db table columns, remove the row of data from the 
                    // data to be inserted into the DB.
                    unset($rows_of_data_2_insert[$key]);

                } // if(count($rows_of_data_2_insert[$key]) === 0)

            } // foreach ($rows_of_data_2_insert as $key=>$row_2_insert)

            // do we still have any data left to insert after all the filtration above?
            if($rows_of_data_2_insert !== []) {

                //Insert statement
                $insrt_qry_obj = (new QueryFactory($this->getPdoDriverName()))->newInsert();

                //Batch all the data into one insert query.
                $insrt_qry_obj->into($this->getTableName())->addRows($rows_of_data_2_insert);           
                $insrt_qry_sql = $insrt_qry_obj->__toString();
                $insrt_qry_params = $insrt_qry_obj->getBindValues();

                $this->logQuery($insrt_qry_sql, $insrt_qry_params, __METHOD__, '' . __LINE__);
                $result = $this->db_connector->executeQuery($insrt_qry_sql, $insrt_qry_params);

            } // if(count($rows_of_data_2_insert) > 0)
        } // if ($rows_of_data_2_insert !== [])

        return $result;
    }
    
    /**
     * @return never
     */
    protected function throwExceptionForInvalidUpdateQueryArg($val, array $cols_n_vals): void {

        $msg = "ERROR: the value "
             . PHP_EOL . var_export($val, true) . PHP_EOL
             . " you are trying to use to bulid the where clause for updating the table `{$this->getTableName()}`"
             . " is not acceptable ('".  gettype($val) . "'"
             . " supplied). Boolean, NULL, numeric or string value expected."
             . PHP_EOL
             . "Data supplied to "
             . get_class($this) . '::' . __FUNCTION__ . '(...).' 
             . " for buiding the where clause for the update:"
             . PHP_EOL . var_export($cols_n_vals, true) . PHP_EOL
             . PHP_EOL;

        throw new InvalidArgumentException($msg);
    }
    
    /**
     * {@inheritDoc}
     */
    public function updateMatchingDbTableRows(
        array $col_names_n_vals_2_save = [],
        array $col_names_n_vals_2_match = []
    ): self {
        $num_initial_match_items = count($col_names_n_vals_2_match);

        if ($col_names_n_vals_2_save !== []) {

            $table_cols = $this->getTableColNames();
            $pkey_col_name = $this->getPrimaryCol();
            $this->addTimestampToData(
                $col_names_n_vals_2_save, $this->updated_timestamp_column_name, $table_cols
            );

            if(array_key_exists($pkey_col_name, $col_names_n_vals_2_save)) {

                //don't update the primary key
                unset($col_names_n_vals_2_save[$pkey_col_name]);
            }

            // remove non-existent table columns from the data
            // and check that existent table columns have values of  
            // the right data type: ie. Boolean, NULL, Number or String.
            // Convert objects with a __toString to their string value.
            foreach ($col_names_n_vals_2_save as $key => $val) {

                $col_names_n_vals_2_save[$key] = 
                    $this->stringifyIfStringable($val, $key, $table_cols);

                if ( !in_array($key, $table_cols) ) {

                    unset($col_names_n_vals_2_save[$key]);

                } else if( !$this->isAcceptableUpdateValue($val) ) {

                    $msg = "ERROR: the value "
                         . PHP_EOL . var_export($val, true) . PHP_EOL
                         . " you are trying to update `{$this->getTableName()}`.`{$key}`."
                         . "{$key} with is not acceptable ('".  gettype($val) . "'"
                         . " supplied). Boolean, NULL, numeric or string value expected."
                         . PHP_EOL
                         . "Data supplied to "
                         . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                         . " for update:"
                         . PHP_EOL . var_export($col_names_n_vals_2_save, true) . PHP_EOL
                         . PHP_EOL;

                    throw new \GDAO\ModelInvalidUpdateValueSuppliedException($msg);
                } // if ( !in_array($key, $table_cols) )
            } // foreach ($col_names_n_vals_2_save as $key => $val)

            // After filtering out non-table columns, if we have any table
            // columns data left, we can do the update
            if($col_names_n_vals_2_save !== []) {

                //update statement
                $update_qry_obj = (new QueryFactory($this->getPdoDriverName()))->newUpdate();
                $update_qry_obj->table($this->getTableName());
                $update_qry_obj->cols($col_names_n_vals_2_save);

                foreach ($col_names_n_vals_2_match as $colname => $colval) {

                    if(!in_array($colname, $table_cols)) {

                        //non-existent table column
                        unset($col_names_n_vals_2_match[$colname]);
                        continue;
                    }

                    if (is_array($colval)) {

                        if($colval !== []) {

                            foreach ($colval as $key=>$val) {

                                if(!$this->isAcceptableUpdateQueryValue($val)) {

                                    $this->throwExceptionForInvalidUpdateQueryArg(
                                            $val, $col_names_n_vals_2_match
                                        );
                                }

                                $colval[$key] = $this->stringifyIfStringable($val);
                            }

                            $this->addWhereInAndOrIsNullToQuery($colname, $colval, $update_qry_obj);

                        } // if($colval !== []) 

                    } else {

                        if(!$this->isAcceptableUpdateQueryValue($colval)) {

                            $this->throwExceptionForInvalidUpdateQueryArg(
                                    $colval, $col_names_n_vals_2_match
                                );
                        }

                        if(is_null($colval)) {

                            $update_qry_obj->where(
                                " {$colname} IS NULL "
                            );

                        } else {

                            $update_qry_obj->where(
                                " {$colname} = ? ", 
                                $this->stringifyIfStringable($colval) 
                            );
                        }

                    } // if (is_array($colval))
                } // foreach ($col_names_n_vals_2_match as $colname => $colval)

                // If after filtering out non existing cols in $col_names_n_vals_2_match
                // if there is still data left in $col_names_n_vals_2_match, then
                // finish building the update query and do the update
                if( 
                    $col_names_n_vals_2_match !== [] // there are valid db table cols in here
                    || 
                    (
                        $num_initial_match_items === 0
                        && $col_names_n_vals_2_match === [] // empty match array passed, we are updating all rows
                    )
                ) {

                    $updt_qry = $update_qry_obj->__toString();
                    $updt_qry_params = $update_qry_obj->getBindValues();
                    $this->logQuery($updt_qry, $updt_qry_params, __METHOD__, '' . __LINE__);

                    $this->db_connector->executeQuery($updt_qry, $updt_qry_params, true);
                }

            } // if($col_names_n_vals_2_save !== [])
        } // if ($col_names_n_vals_2_save !== [])

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function updateSpecifiedRecord(\GDAO\Model\RecordInterface $record): self {
        
        if( $record instanceof \LeanOrm\Model\ReadOnlyRecord ) {

            $msg = "ERROR: Can't save a ReadOnlyRecord to the database in " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).'
                 . PHP_EOL .'Unupdated record' . var_export($record, true) . PHP_EOL;
            throw new \LeanOrm\CantSaveReadOnlyRecordException($msg);
        }
        
        if( $record->getModel()->getTableName() !== $this->getTableName() ) {
            
            $msg = "ERROR: Can't update a record (an instance of `%s`) belonging to the database table `%s` " 
                . "using a Model instance of `%s` belonging to the database table `%s` in " 
                 . get_class($this) . '::' . __FUNCTION__ . '(...).'
                 . PHP_EOL .'Unupdated record: ' . PHP_EOL . var_export($record, true) . PHP_EOL; 
            throw new \GDAO\ModelInvalidUpdateValueSuppliedException(
                sprintf(
                    $msg, get_class($record), $record->getModel()->getTableName(),
                    get_class($this), $this->getTableName()
                )
            );
        }

        $pri_key_val = $record->getPrimaryVal();
        
        if( 
            count($record) > 0  // There is data in the record
            && !$record->isNew() // This is not a new record that wasn't fetched from the DB
            && !Utils::isEmptyString(''.$pri_key_val) // Record has a primary key value
            && $record->isChanged() // The data in the record has changed from the state it was when initially fetched from DB
        ) {
            $cols_n_vals_2_match = [$record->getPrimaryCol()=>$pri_key_val];

            if($this->getUpdatedTimestampColumnName() !== null) {

                // Record has changed value(s) & must definitely be updated.
                // Set the value of the $this->getUpdatedTimestampColumnName()
                // field to an empty string, force updateMatchingDbTableRows
                // to add a new updated timestamp value during the update.
                $record->{$this->getUpdatedTimestampColumnName()} = '';
            }

            $data_2_save = $record->getData();
            $this->updateMatchingDbTableRows(
                $data_2_save, 
                $cols_n_vals_2_match
            );

            // update the record with the new updated copy from the DB
            // which will contain the new updated timestamp value.
            $record = $this->fetchOneRecord(
                        $this->getSelect()
                             ->where(
                                    " {$record->getPrimaryCol()} = ? ", 
                                    $record->getPrimaryVal()
                                )
                    );
        } // if( count($record) > 0 && !$record->isNew()........

        return $this;
    }

    protected function addWhereInAndOrIsNullToQuery(
        string $colname, array &$colvals, \Aura\SqlQuery\Common\WhereInterface $qry_obj
    ): void {
        
        if($colvals !== []) { // make sure it's a non-empty array
            
            // if there are one or more null values in the array,
            // we need to unset them and add an
            //      OR $colname IS NULL 
            // clause to the query
            $unique_colvals = array_unique($colvals);
            $keys_for_null_vals = array_keys($unique_colvals, null, true);

            foreach($keys_for_null_vals as $key_for_null_val) {

                // remove the null vals from $colval
                unset($unique_colvals[$key_for_null_val]);
            }

            if(
                $keys_for_null_vals !== [] && $unique_colvals !== []
            ) {
                // Some values in the array are null and some are non-null
                // Generate WHERE COL IN () OR COL IS NULL
                $colval_placeholders = array_fill(0, count($unique_colvals), '?');
                //  we are trying to do something like this 
                // ->where('id IN (?, ?, ?)', 1, 2, 3)
                $qry_obj->where(
                    " {$colname} IN (" . implode(',', $colval_placeholders) . ") ",
                    ...$unique_colvals
                )->orWhere(" {$colname} IS NULL ");

            } elseif (
                $keys_for_null_vals !== []
                && $unique_colvals === []
            ) {
                // All values in the array are null
                // Only generate WHERE COL IS NULL
                $qry_obj->where(" {$colname} IS NULL ");

            } else { // ($keys_for_null_vals === [] && $unique_colvals !== []) // no nulls found
                
                ////////////////////////////////////////////////////////////////
                // NOTE: ($keys_for_null_vals === [] && $unique_colvals === [])  
                // is impossible because we started with if($colvals !== [])
                ////////////////////////////////////////////////////////////////

                // All values in the array are non-null
                // Only generate WHERE COL IN ()
                $colval_placeholders = array_fill(0, count($unique_colvals), '?');
                //  we are trying to do something like this 
                // ->where('id IN (?, ?, ?)', 1, 2, 3)
                $qry_obj->where(
                    " {$colname} IN (" . implode(',', $colval_placeholders) . ") ",
                    ...$unique_colvals
                );
            }
        }
    }
    
    /**
     * @return array{
     *              database_server_info: mixed, 
     *              driver_name: mixed, 
     *              pdo_client_version: mixed, 
     *              database_server_version: mixed, 
     *              connection_status: mixed, 
     *              connection_is_persistent: mixed
     *          }
     */
    public function getCurrentConnectionInfo(): array {

        $pdo_obj = $this->getPDO();
        $attributes = [
            'database_server_info' => 'SERVER_INFO',
            'driver_name' => 'DRIVER_NAME',
            'pdo_client_version' => 'CLIENT_VERSION',
            'database_server_version' => 'SERVER_VERSION',
            'connection_status' => 'CONNECTION_STATUS',
            'connection_is_persistent' => 'PERSISTENT',
        ];

        foreach ($attributes as $key => $value) {
            
            try {
                
                $attributes[ $key ] = $pdo_obj->getAttribute(constant(\PDO::class .'::ATTR_' . $value));
                
            } catch (\PDOException $e) {
                
                $attributes[ $key ] = 'Unsupported attribute for the current PDO driver';
                continue;
            }

            if( $value === 'PERSISTENT' ) {

                $attributes[ $key ] = var_export($attributes[ $key ], true);
            }
        }

        return $attributes;
    }

    /**
     * @return mixed[]
     */
    public function getQueryLog(): array {

        return $this->query_log;
    }

    /**
     * To get the log for all existing instances of this class & its subclasses,
     * call this method with no args or with null.
     * 
     * To get the log for instances of a specific class (this class or a
     * particular sub-class of this class), you must call this method with 
     * an instance of the class whose log you want to get.
     * 
     * @return mixed[]
     */
    public static function getQueryLogForAllInstances(?\GDAO\Model $obj=null): array {
        
        $key = ($obj instanceof \GDAO\Model) ? static::createLoggingKey($obj) : '';
        
        return ($obj instanceof \GDAO\Model)
                ?
                (
                    array_key_exists($key, static::$all_instances_query_log) 
                    ? static::$all_instances_query_log[$key] : [] 
                )
                : static::$all_instances_query_log 
                ;
    }
    
    public static function clearQueryLogForAllInstances(): void {
        
        static::$all_instances_query_log = [];
    }

    protected static function createLoggingKey(\GDAO\Model $obj): string {
        
        return "{$obj->getDsn()}::" . get_class($obj);
    }
    
    protected function logQuery(string $sql, array $bind_params, string $calling_method='', string $calling_line=''): self {

        if( $this->can_log_queries ) {

            $key = static::createLoggingKey($this);
            
            if(!array_key_exists($key, static::$all_instances_query_log)) {

                static::$all_instances_query_log[$key] = [];
            }

            $log_record = [
                'sql' => $sql,
                'bind_params' => $bind_params,
                'date_executed' => date('Y-m-d H:i:s'),
                'class_method' => $calling_method,
                'line_of_execution' => $calling_line,
            ];

            $this->query_log[] = $log_record;
            static::$all_instances_query_log[$key][] = $log_record;

            if($this->logger instanceof \Psr\Log\LoggerInterface) {

                $this->logger->info(
                    PHP_EOL . PHP_EOL .
                    'SQL:' . PHP_EOL . "{$sql}" . PHP_EOL . PHP_EOL . PHP_EOL .
                    'BIND PARAMS:' . PHP_EOL . var_export($bind_params, true) .
                    PHP_EOL . "Calling Method: `{$calling_method}`" . PHP_EOL .
                    "Line of Execution: `{$calling_line}`" . PHP_EOL .
                     PHP_EOL . PHP_EOL . PHP_EOL
                );
            }                    
        }

        return $this;
    }

    ///////////////////////////////////////
    // Methods for defining relationships
    ///////////////////////////////////////

    public function hasOne(
        string $relation_name,
        string $foreign_key_col_in_this_models_table,
        string $foreign_table_name,
        string $foreign_key_col_in_foreign_table,
        string $primary_key_col_in_foreign_table,
        string $foreign_models_class_name = \LeanOrm\Model::class,
        string $foreign_models_record_class_name = '',
        string $foreign_models_collection_class_name = '',
        ?callable $sql_query_modifier = null
    ): self {
        $this->checkThatRelationNameIsNotAnActualColumnName($relation_name);
        $this->validateRelatedModelClassName($foreign_models_class_name);
        
        if($foreign_models_collection_class_name !== '') {
            
            $this->validateRelatedCollectionClassName($foreign_models_collection_class_name);
        }
        
        if($foreign_models_record_class_name !== '') {
            
            $this->validateRelatedRecordClassName($foreign_models_record_class_name);
        }
        
        $this->validateTableName($foreign_table_name);
        
        $this->validateThatTableHasColumn($this->getTableName(), $foreign_key_col_in_this_models_table);
        $this->validateThatTableHasColumn($foreign_table_name, $foreign_key_col_in_foreign_table);
        $this->validateThatTableHasColumn($foreign_table_name, $primary_key_col_in_foreign_table);
        
        $this->relations[$relation_name] = [];
        $this->relations[$relation_name]['relation_type'] = \GDAO\Model::RELATION_TYPE_HAS_ONE;
        $this->relations[$relation_name]['foreign_key_col_in_my_table'] = $foreign_key_col_in_this_models_table;
        $this->relations[$relation_name]['foreign_table'] = $foreign_table_name;
        $this->relations[$relation_name]['foreign_key_col_in_foreign_table'] = $foreign_key_col_in_foreign_table;
        $this->relations[$relation_name]['primary_key_col_in_foreign_table'] = $primary_key_col_in_foreign_table;

        $this->relations[$relation_name]['foreign_models_class_name'] = $foreign_models_class_name;
        $this->relations[$relation_name]['foreign_models_record_class_name'] = $foreign_models_record_class_name;
        $this->relations[$relation_name]['foreign_models_collection_class_name'] = $foreign_models_collection_class_name;

        $this->relations[$relation_name]['sql_query_modifier'] = $sql_query_modifier;

        return $this;
    }

    public function belongsTo(
        string $relation_name,
        string $foreign_key_col_in_this_models_table,
        string $foreign_table_name,
        string $foreign_key_col_in_foreign_table,
        string $primary_key_col_in_foreign_table,
        string $foreign_models_class_name = \LeanOrm\Model::class,
        string $foreign_models_record_class_name = '',
        string $foreign_models_collection_class_name = '',
        ?callable $sql_query_modifier = null
    ): self {
        $this->checkThatRelationNameIsNotAnActualColumnName($relation_name);
        $this->validateRelatedModelClassName($foreign_models_class_name);
        
        if($foreign_models_collection_class_name !== '') {
        
            $this->validateRelatedCollectionClassName($foreign_models_collection_class_name);
        }
        
        if($foreign_models_record_class_name !== '') {
            
            $this->validateRelatedRecordClassName($foreign_models_record_class_name);
        }
        
        $this->validateTableName($foreign_table_name);
        
        $this->validateThatTableHasColumn($this->getTableName(), $foreign_key_col_in_this_models_table);
        $this->validateThatTableHasColumn($foreign_table_name, $foreign_key_col_in_foreign_table);
        $this->validateThatTableHasColumn($foreign_table_name, $primary_key_col_in_foreign_table);
        
        $this->relations[$relation_name] = [];
        $this->relations[$relation_name]['relation_type'] = \GDAO\Model::RELATION_TYPE_BELONGS_TO;
        $this->relations[$relation_name]['foreign_key_col_in_my_table'] = $foreign_key_col_in_this_models_table;
        $this->relations[$relation_name]['foreign_table'] = $foreign_table_name;
        $this->relations[$relation_name]['foreign_key_col_in_foreign_table'] = $foreign_key_col_in_foreign_table;
        $this->relations[$relation_name]['primary_key_col_in_foreign_table'] = $primary_key_col_in_foreign_table;

        $this->relations[$relation_name]['foreign_models_class_name'] = $foreign_models_class_name;
        $this->relations[$relation_name]['foreign_models_record_class_name'] = $foreign_models_record_class_name;
        $this->relations[$relation_name]['foreign_models_collection_class_name'] = $foreign_models_collection_class_name;

        $this->relations[$relation_name]['sql_query_modifier'] = $sql_query_modifier;

        return $this;
    }

    public function hasMany(
        string $relation_name,
        string $foreign_key_col_in_this_models_table,
        string $foreign_table_name,
        string $foreign_key_col_in_foreign_table,
        string $primary_key_col_in_foreign_table,
        string $foreign_models_class_name = \LeanOrm\Model::class,
        string $foreign_models_record_class_name = '',
        string $foreign_models_collection_class_name = '',
        ?callable $sql_query_modifier = null
    ): self {
        $this->checkThatRelationNameIsNotAnActualColumnName($relation_name);
        $this->validateRelatedModelClassName($foreign_models_class_name);
        
        if($foreign_models_collection_class_name !== '') {
            
            $this->validateRelatedCollectionClassName($foreign_models_collection_class_name);
        }
            
        if($foreign_models_record_class_name !== '') {
            
            $this->validateRelatedRecordClassName($foreign_models_record_class_name);
        }
            
        
        $this->validateTableName($foreign_table_name);
        
        $this->validateThatTableHasColumn($this->getTableName(), $foreign_key_col_in_this_models_table);
        $this->validateThatTableHasColumn($foreign_table_name, $foreign_key_col_in_foreign_table);
        $this->validateThatTableHasColumn($foreign_table_name, $primary_key_col_in_foreign_table);
        
        $this->relations[$relation_name] = [];
        $this->relations[$relation_name]['relation_type'] = \GDAO\Model::RELATION_TYPE_HAS_MANY;
        $this->relations[$relation_name]['foreign_key_col_in_my_table'] = $foreign_key_col_in_this_models_table;
        $this->relations[$relation_name]['foreign_table'] = $foreign_table_name;
        $this->relations[$relation_name]['foreign_key_col_in_foreign_table'] = $foreign_key_col_in_foreign_table;
        $this->relations[$relation_name]['primary_key_col_in_foreign_table'] = $primary_key_col_in_foreign_table;

        $this->relations[$relation_name]['foreign_models_class_name'] = $foreign_models_class_name;
        $this->relations[$relation_name]['foreign_models_record_class_name'] = $foreign_models_record_class_name;
        $this->relations[$relation_name]['foreign_models_collection_class_name'] = $foreign_models_collection_class_name;

        $this->relations[$relation_name]['sql_query_modifier'] = $sql_query_modifier;

        return $this;
    }

    public function hasManyThrough(
        string $relation_name,
        string $col_in_my_table_linked_to_join_table,
        string $join_table,
        string $col_in_join_table_linked_to_my_table,
        string $col_in_join_table_linked_to_foreign_table,
        string $foreign_table_name,
        string $col_in_foreign_table_linked_to_join_table,
        string $primary_key_col_in_foreign_table,
        string $foreign_models_class_name = \LeanOrm\Model::class,
        string $foreign_models_record_class_name = '',
        string $foreign_models_collection_class_name = '',
        ?callable $sql_query_modifier = null
    ): self {
        $this->checkThatRelationNameIsNotAnActualColumnName($relation_name);
        $this->validateRelatedModelClassName($foreign_models_class_name);
        
        if ($foreign_models_collection_class_name !== '') {
            
            $this->validateRelatedCollectionClassName($foreign_models_collection_class_name);
        }
        
        if ($foreign_models_record_class_name !== '') {
            
            $this->validateRelatedRecordClassName($foreign_models_record_class_name);
        }
        
        $this->validateTableName($foreign_table_name);
        $this->validateTableName($join_table);
        
        $this->validateThatTableHasColumn($this->getTableName(), $col_in_my_table_linked_to_join_table);
        $this->validateThatTableHasColumn($join_table, $col_in_join_table_linked_to_my_table);
        $this->validateThatTableHasColumn($join_table, $col_in_join_table_linked_to_foreign_table);
        $this->validateThatTableHasColumn($foreign_table_name, $col_in_foreign_table_linked_to_join_table);
        $this->validateThatTableHasColumn($foreign_table_name, $primary_key_col_in_foreign_table);
        
        $this->relations[$relation_name] = [];
        $this->relations[$relation_name]['relation_type'] = \GDAO\Model::RELATION_TYPE_HAS_MANY_THROUGH;
        $this->relations[$relation_name]['col_in_my_table_linked_to_join_table'] = $col_in_my_table_linked_to_join_table;
        $this->relations[$relation_name]['join_table'] = $join_table;
        $this->relations[$relation_name]['col_in_join_table_linked_to_my_table'] = $col_in_join_table_linked_to_my_table;
        $this->relations[$relation_name]['col_in_join_table_linked_to_foreign_table'] = $col_in_join_table_linked_to_foreign_table;
        $this->relations[$relation_name]['foreign_table'] = $foreign_table_name;
        $this->relations[$relation_name]['col_in_foreign_table_linked_to_join_table'] = $col_in_foreign_table_linked_to_join_table;
        $this->relations[$relation_name]['primary_key_col_in_foreign_table'] = $primary_key_col_in_foreign_table;

        $this->relations[$relation_name]['foreign_models_class_name'] = $foreign_models_class_name;
        $this->relations[$relation_name]['foreign_models_record_class_name'] = $foreign_models_record_class_name;
        $this->relations[$relation_name]['foreign_models_collection_class_name'] = $foreign_models_collection_class_name;

        $this->relations[$relation_name]['sql_query_modifier'] = $sql_query_modifier;

        return $this;
    }
    
    protected function checkThatRelationNameIsNotAnActualColumnName(string $relationName): void {

        $tableCols = $this->getTableColNames();
        $tableColsLowerCase = array_map('strtolower', $tableCols);

        if( in_array(strtolower($relationName), $tableColsLowerCase) ) {

            //Error trying to add a relation whose name collides with an actual
            //name of a column in the db table associated with this model.
            $msg = sprintf("ERROR: You cannont add a relationship with the name '%s' ", $relationName)
                 . " to the Model (".get_class($this)."). The database table "
                 . sprintf(" '%s' associated with the ", $this->getTableName())
                 . " model (".get_class($this).") already contains"
                 . " a column with the same name."
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;

            throw new \GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException($msg);
        } // if( in_array(strtolower($relationName), $tableColsLowerCase) ) 
    }
    
    protected function validateTableName(string $table_name): bool {
        
        if(!$this->tableExistsInDB($table_name)) {
            
            //throw exception
            $msg = "ERROR: The specified table `{$table_name}` does not exist in the DB."
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;
            throw new BadModelTableNameException($msg);
        } // if(!$this->tableExistsInDB($table_name))
        
        return true;
    }
    
    protected function validateThatTableHasColumn(string $table_name, string $column_name): bool {
        
        if(!$this->columnExistsInDbTable($table_name, $column_name)) {

            //throw exception
            $msg = "ERROR: The specified table `{$table_name}` in the DB"
                 . " does not contain the specified column `{$column_name}`."
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;
            throw new BadModelColumnNameException($msg);
        } // if(!$this->columnExistsInDbTable($table_name, $column_name))
        
        return true;
    }
}
