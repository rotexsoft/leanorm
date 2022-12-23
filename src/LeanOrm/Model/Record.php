<?php
declare(strict_types=1);
namespace LeanOrm\Model;

use GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException;

/**
 * Description of Record
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2022, Rotexsoft
 */
class Record implements \GDAO\Model\RecordInterface
{
    /**
     * 
     * Data for this record ([to be saved to the db] or [as read from the db]).
     *
     */
    protected array $data = [];
    
    /**
     * 
     * Data for this record (not to be saved to the db i.e. not from any actual db column and not related data).
     *
     */
    protected array $non_table_col_and_non_related_data = [];
    
    /**
     *
     * Copy of the initial data loaded into this record or data for this record immediately after an insert or update.
     * 
     */
    protected ?array $initial_data = null;
    
    /**
     * 
     * Holds relationship data retrieved based on definitions in the array below.
     * \GDAO\Model::$relations
     *
     */
    protected array $related_data = [];
    
    /**
     * 
     * Tracks if *this record* is new (i.e., not in the database yet).
     *
     */
    protected bool $is_new = true;

    /**
     *
     * The model object that saves and reads data to and from the db on behalf 
     * of this record
     * 
     */
    protected \GDAO\Model $model;
    
    /**
     * 
     * @param array $data associative array of data to be loaded into this record.
     *                    [
     *                      'col_name1'=>'value_for_col1', 
     *                      .............................,
     *                      .............................,
     *                      'col_nameN'=>'value_for_colN'
     *                    ]
     * @param \GDAO\Model $model The model object that transfers data between the db and this record.
     */
    public function __construct(array $data, \GDAO\Model $model) {
        
        $this->setModel($model);
        $this->loadData($data);
    }
    
    public function __destruct() {

        //print "Destroying Record with Primary key Value: " . $this->getPrimaryVal() . "<br>";
        unset($this->data);
        unset($this->initial_data);
        unset($this->is_new);
        unset($this->non_table_col_and_non_related_data);
        unset($this->related_data);
        //Don't unset $this->model, it may still be referenced by other 
        //Record and / or Collection objects.
    }
    
    /**
     * 
     * Delete the record from the db. 
     * 
     * If deletion was successful and the primary key column for the record's db
     * table is auto-incrementing, then unset the primary key field in the data 
     * contained in the record object.
     * 
     * NOTE: data contained in the record include $this->data, $this->related_data,
     *       $this->non_table_col_and_non_related_data and $this->initial_data.
     * 
     * @param bool $set_record_objects_data_to_empty_array true to reset the record object's data to an empty array if db deletion was successful, false to keep record object's data
     * 
     * @return bool true if record was successfully deleted from db or false if not
     * 
     */
    public function delete($set_record_objects_data_to_empty_array=false): bool {
        
        $result = $this->getModel()->deleteSpecifiedRecord($this);
        
        if( $result && $set_record_objects_data_to_empty_array ) {
            
            $this->data = [];
            $this->related_data = [];
            $this->initial_data = [];
            $this->non_table_col_and_non_related_data = [];
        }
        
        // if $result is null this means the record does not even exist in the db
        // and it's as good as it being deleted, so return true
        return $result ?? true;
    }
    
    /**
     * 
     * Get the data for this record.
     * Modifying the returned data will not affect the data inside this record.
     * 
     * @return array a copy of the current data for this record
     */
    public function getData(): array {
        
        return $this->data;
    }
    
    /**
     * 
     * Get a copy of the initial data loaded into this record.
     * Modifying the returned data will not affect the initial data inside this record.
     * 
     * @return array a copy of the initial data loaded into this record.
     */
    public function getInitialData(): ?array {
        
        return $this->initial_data;
    }
    
    
    /**
     * 
     * Get all the related data loaded into this record.
     * Modifying the returned data will not affect the related data inside this record.
     * 
     * @return array a reference to all the related data loaded into this record.
     */
    public function getRelatedData(): array {
        
        return $this->related_data;
    }
    
    /**
     * 
     * Get data for this record that does not belong to any of it's table columns and is not related data.
     * 
     * @return array Data for this record (not to be saved to the db i.e. not from any actual db column and not related data).
     */
    public function getNonTableColAndNonRelatedData(): array {
        
        return $this->non_table_col_and_non_related_data;
    }
    
    /**
     * 
     * Get a reference to the data for this record.
     * Modifying the returned data will affect the data inside this record.
     * 
     * @return array a reference to the current data for this record.
     */
    public function &getDataByRef(): array {
        
        return $this->data;
    }
    
    /**
     * 
     * Get a reference to the initial data loaded into this record.
     * Modifying the returned data will affect the initial data inside this record.
     * 
     * @return array a reference to the initial data loaded into this record.
     */
    public function &getInitialDataByRef(): ?array {
        
        return $this->initial_data;
    }
    
    /**
     * 
     * Get a reference to all the related data loaded into this record.
     * Modifying the returned data will affect the related data inside this record.
     * 
     * @return array a reference to all the related data loaded into this record.
     */
    public function &getRelatedDataByRef(): array {
        
        return $this->related_data;
    }
    
    /**
     * 
     * Get data for this record that does not belong to any of it's table columns and is not related data.
     * 
     * @return array reference to the data for this record (not from any actual db column and not related data).
     */
    public function &getNonTableColAndNonRelatedDataByRef(): array {
        
        return $this->non_table_col_and_non_related_data;
    }

    /**
     * 
     * Set relation data for this record.
     * 
     * @param string $key relation name
     * @param mixed $value an array or record or collection containing related data
     * 
     * @throws \GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException
     * 
     */
    public function setRelatedData($key, $value): self {
        
        $my_model = $this->getModel();
        $table_cols = $my_model->getTableColNames();
        
        if( in_array($key, $table_cols) ) {
            
            //Error trying to add a relation whose name collides with an actual
            //name of a column in the db table associated with this record's model.
            $msg = sprintf("ERROR: You cannont add a relationship with the name '%s' ", $key)
                 . " to the record (".get_class($this)."). The database table "
                 . sprintf(" '%s' associated with the ", $my_model->getTableName())
                 . " record's model (".get_class($my_model).") already contains"
                 . " a column with the same name."
                 . PHP_EOL . get_class($this) . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;
                 
            throw new \GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException($msg);
        }
        
        //We're safe, set the related data.
        $this->related_data[$key] = $value;
        
        return $this;
    }
    
    /**
     * 
     * Get the model object that saves and reads data to and from the db on 
     * behalf of this record
     * 
     */
    public function getModel(): \LeanOrm\Model {
        
        return $this->model;
    }
    
    /**
     * 
     * @return string name of the primary-key column of the db table this record belongs to
     * 
     */
    public function getPrimaryCol(): string {

        return $this->getModel()->getPrimaryCol();
    }

    /**
     * 
     * @return mixed the value stored in the primary-key column for this record.
     * 
     */
    public function getPrimaryVal() {

        return $this->{$this->getPrimaryCol()};
    }

    /**
     * Code below was lifted from Solar_Sql_Model_Record::isChanged($col=null)
     * 
     * Tells if a particular table-column has changed.
     * 
     * This is slightly complicated.  Changes to or from a null are reported
     * as "changed".  If both the initial value and new value are numeric
     * (that is, whether they are string/float/int), they are compared using
     * normal inequality (!=).  Otherwise, the initial value and new value
     * are compared using strict inequality (!==).
     * 
     * This complexity results from converting string and numeric values in
     * and out of the database.  Coming from the database, a string numeric
     * '1' might be filtered to an integer 1 at some point, making it look
     * like the value was changed when in practice it has not.
     * 
     * Similarly, we need to make allowances for nulls, because a non-numeric
     * null is loosely equal to zero or an empty string.
     * 
     * @param string $col The table-column name.
     * 
     * @return null|bool Returns null if the table-column name does not exist,
     * boolean true if the data is changed, boolean false if not changed.
     * 
     * @todo How to handle changes to array values?
     * 
     */
    public function isChanged($col = null): ?bool {

        // if no column specified, check if the record as a whole has changed
        if ($col === null) {

            $cols = $this->getModel()->getTableColNames();

            foreach ($cols as $col) {
                if ($this->isChanged($col)) {
                    return true;
                }
            }

            return false;
        }

        // col needs to exist in the initial array
        if (
            (    
                !array_key_exists($col, $this->initial_data)
                && array_key_exists($col, $this->data)
            )
            ||
            (    
                array_key_exists($col, $this->initial_data)
                && !array_key_exists($col, $this->data)
            )                    
        ) {
            return true;
            
        } elseif(
            !array_key_exists($col, $this->initial_data)
            && !array_key_exists($col, $this->data)
        ) {
            return null;
            
        } else {
            // array_key_exists($col, $this->initial_data)
            // && array_key_exists($col, $this->data)

            // track changes to or from null
            $from_null = $this->initial_data[$col] === null &&
                    $this->data[$col] !== null;

            $to_null = $this->initial_data[$col] !== null &&
                    $this->data[$col] === null;

            if ($from_null || $to_null) {
                
                return true;
            }

            // track numeric changes
            $both_numeric = is_numeric($this->initial_data[$col]) &&
                    is_numeric($this->data[$col]);

            if ($both_numeric) {
                
                return ''.$this->initial_data[$col] !== ''.$this->data[$col];
            }

            // use strict inequality
            return $this->initial_data[$col] !== $this->data[$col];
        }
    }
    
    /**
     * 
     * Is the record new? (I.e. its data has never been saved to the db)
     * 
     */
	public function isNew(): bool {
        
        return $this->is_new;
    }

    /**
     * \GDAO\Model\Record::$initial_data should be set here only if it has the 
     * initial value of null.
     * 
     * This method partially or completely overwrites pre-existing data and 
     * replaces it with the new data. Related data should also be loaded if 
     * $data_2_load is an instance of \GDAO\Model\RecordInterface. However,
     * because of the way __get is implemented, there's no need to load
     * relationship data here, __get will load that data on-demand if not 
     * already loaded.
     * 
     * Note if $cols_2_load === null all data should be replaced, else only
     * replace data for the cols in $cols_2_load.
     * 
     * If $data_2_load is an instance of \GDAO\Model\RecordInterface and is also an instance 
     * of a sub-class of the Record class in a package that implements this API and
     * if $data_2_load->getModel()->getTableName() !== $this->getModel()->getTableName(), 
     * then the exception below should be thrown:
     * 
     *      \GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException
     * 
     * @param \GDAO\Model\RecordInterface|array $data_2_load
     * @param array $cols_2_load name of field to load from $data_2_load. If empty, 
     *                           load all fields in $data_2_load.
     * 
     * @throws \GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException
     * 
     */
    public function loadData($data_2_load, array $cols_2_load = []): self {

        if(
            !is_array($data_2_load) 
            && !($data_2_load instanceof \GDAO\Model\RecordInterface)
        ) {
            $datasource_type = is_object($data_2_load)? 
                                get_class($data_2_load) : gettype($data_2_load);
            
            $msg = "ERROR: Trying to load data into a record from an unsupported"
                   . sprintf(" data source of type '%s'. An 'Array' or", $datasource_type)
                   . " instance of '\\LeanOrm\\Model\\Record' or any of its"
                   . " subclasses are the allowed data sources acceptable by "
                   . get_class($this).'::'.__FUNCTION__.'(...)'
                   . PHP_EOL . "Unloaded Data:"
                   . PHP_EOL . var_export($data_2_load, true) . PHP_EOL;
            
            throw new LoadingDataFromInvalidSourceIntoRecordException($msg);
        }
        
        if (
            $data_2_load instanceof \GDAO\Model\RecordInterface
            && $data_2_load->getModel()->getTableName() !== $this->getModel()->getTableName()
        ) {
            //Cannot load data
            //2 records whose models are associated with different db tables.
            //Can't load data, schemas don't match.
            $msg = "ERROR: Can't load data from an instance of '".get_class($data_2_load)
                   . "' into an instance of '".get_class($this)."'. Their models"
                   . "'".get_class($data_2_load->getModel())."' and '"
                   . get_class($this->getModel())."' are associated with different"
                   . " db tables ('".$data_2_load->getModel()->getTableName() 
                   ."' and '". $this->getModel()->getTableName()."')."
                   . PHP_EOL . "Unloaded Data:"
                   . PHP_EOL . var_export($data_2_load->getData(), true)  . PHP_EOL;
            
            throw new LoadingDataFromInvalidSourceIntoRecordException($msg);
        }

        $table_col_names_4_my_model = $this->getModel()->getTableColNames();
        
        if ( $cols_2_load === [] ) {

            if ( is_array($data_2_load) && $data_2_load !== [] ) {

                foreach( $data_2_load as $col_name => $value_2_load ) {
                    
                    if ( in_array($col_name, $table_col_names_4_my_model) ) {
                        
                        $this->data[$col_name] = $value_2_load;
                        
                    } else {
                        
                        $this->non_table_col_and_non_related_data[$col_name] = $value_2_load;
                    }
                }
                
            } elseif ($data_2_load instanceof \GDAO\Model\RecordInterface) {

                $this->data = $data_2_load->getData();
                $this->non_table_col_and_non_related_data = $data_2_load->getNonTableColAndNonRelatedData();
            }
            
        } else {

            foreach ( $cols_2_load as $col_name ) {

                if (
                    (
                        is_array($data_2_load)
                        && $data_2_load !== []
                        && array_key_exists($col_name, $data_2_load)
                    ) 
                    || (
                        $data_2_load instanceof \GDAO\Model\RecordInterface 
                        && isset($data_2_load[$col_name])
                    )
                ) {
                    if ( in_array($col_name, $table_col_names_4_my_model) ) {

                        $this->data[$col_name] = $data_2_load[$col_name];

                    } else {

                        $this->non_table_col_and_non_related_data[$col_name] = $data_2_load[$col_name];
                    }
                }
            } // foreach ( $cols_2_load as $col_name )
        }// elseif ( is_array($cols_2_load) && $cols_2_load !== [] )

        if ($this->initial_data === null) {
             
            $initial_data = [];

            foreach($table_col_names_4_my_model as $col_name) {

                $initial_data[$col_name] = 
                    array_key_exists($col_name, $this->data)? $this->data[$col_name] : '';
            }
            
            $this->initial_data = $initial_data;
        }
        
        return $this;
    }
    
    /**
     * 
     * Set the is_new attribute of this record to true (meaning that the data
     * for this record has never been saved to the db).
     * 
     */
    public function markAsNew(): self {
        
        $this->is_new = true;
        
        return $this;
    }
    
    /**
     * 
     * Set the is_new attribute of this record to false (meaning that the data
     * for this record has been saved to the db or was read from the db).
     * 
     */
    public function markAsNotNew(): self {
        
        $this->is_new = false;
        
        return $this;
    }
    
    /**
     * Set all properties of this record to the state they should be in for a new record.
     * For example:
     *  - unset its primary key value via unset($this[$this->getPrimaryCol()]);
     *  - call $this->markAsNew()
     *  - etc.
     * 
     * The _data & _initial_data properties can be updated as needed by the 
     * implementing sub-class. 
     * For example:
     *  - they could be left as is 
     *  - or the value of _data could be copied to _initial_data
     *  - or the value of _initial_data could be copied to _data
     *  - etc.
     */
    public function setStateToNew(): self {

        $this->data = [];
        $this->related_data = [];
        $this->initial_data = null;
        $this->non_table_col_and_non_related_data = [];
        $this->markAsNew();
        
        return $this;
    }
    
    /**
     * 
     * Save the specified or already existing data for this record to the db.
     * Since this record can only talk to the db via its model property (_model)
     * the save operation will actually be done via $this->model.
     * 
     * @param \GDAO\Model\RecordInterface|array $data_2_save
     * 
     * @return null|bool true: successful save, false: failed save, null: no changed data to save
     * 
     */
    public function save($data_2_save = null): ?bool {

        $result = null;
        
        if (is_null($data_2_save) || empty($data_2_save)) {

            $data_2_save = $this->getData();
        }

        if ( !empty($data_2_save) && count($data_2_save) > 0 ) {
            
            $pri_val = $this->getPrimaryVal();
            
            if ( empty($pri_val) ) {
                
                // New record because of empty primary key value, do insert
                $inserted_data = $this->getModel()->insert($data_2_save);
                $result = ($inserted_data !== false);
                
                if( $result && is_array($inserted_data) && $inserted_data !== [] ) {
                    
                    //update the record with the newly inserted data
                    $this->loadData($inserted_data);
                    
                    //update initial data
                    $this->initial_data = $inserted_data;
                    
                    //record has now been saved to the DB, 
                    //it is no longer a new record (it now exists in the DB).
                    $this->markAsNotNew();
                }
                
            } else {

                //load data into the record
                $this->loadData($data_2_save);
                
                if( $this->isChanged() ) {
                    
                    //update
                    $this->getModel()->updateSpecifiedRecord($this);
                    $this->initial_data = $this->data;
                    $result = true;
                }
            }
        }
        
        return $result;
    }

    /**
     * 
     * Save the specified or already existing data for this record to the db.
     * 
     * This save operation is gaurded by the PDO transaction mechanism. 
     * If the save operation fails all changes are rolled back.
     * 
     * If there is no transaction mechanism available a 
     * \LeanOrm\Model\RecordOperationNotSupportedByDriverException Exception is 
     * thrown.
     * 
     * @param \GDAO\Model\RecordInterface|array $data_2_save
     * 
     * @return bool|null true for a successful save, false for failed save, null: no changed data to save
     * 
     * @throws \Exception throws exception if an error occurred during transaction
     */
    public function saveInTransaction($data_2_save = null): ?bool {

        $pdo_obj = $this->getModel()->getPDO();
        // start the transaction
        $pdo_obj->beginTransaction();
        
        try {

            
            $save_status = $this->save($data_2_save);

            // attempt the save
            if ($save_status === true) {

                // entire save was valid, keep it
                $pdo_obj->commit();
                return true;

            } elseif ($save_status === false) {

                // at least one part of the save was *not* valid.
                // throw it all away.
                $pdo_obj->rollBack();
                return false;

            } else {
                
                $pdo_obj->commit();
                return null; //$save_status === null nothing was done
            }
        } catch (\Exception $exception) {

            if($pdo_obj->inTransaction()) {
                
                // roll back
                $pdo_obj->rollBack();
            }
            
            // throw the exception
            throw $exception;
        }
    }

    /**
     * 
     * Set the \GDAO\Model object for this record
     * 
     * @param \GDAO\Model $model
     */
	public function setModel(\GDAO\Model $model): self {
        
        $this->model = $model;
        
        return $this;
    }
    
    /**
     * 
     * Get all the data and property (name & value pairs) for this record.
     * 
     * @return array of all data & property (name & value pairs) for this record.
     * 
     */
    public function toArray(): array {

        return get_object_vars($this);
    }
    
    //Interface Methods

    /**
     * 
     * ArrayAccess: does the requested key exist?
     * 
     * @param string $key The requested key.
     *  
     */
    public function offsetExists($key): bool {

        return $this->__isset($key);
    }

    /**
     * 
     * ArrayAccess: get a key value.
     * 
     * @param string $key The requested key.
     * 
     * @return mixed
     * 
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key) {

        return $this->__get($key);
    }

    /**
     * 
     * ArrayAccess: set a key value.
     * 
     * @param string $key The requested key.
     * 
     * @param string $val The value to set it to.
     * 
     */
    public function offsetSet($key, $val): void {

        $this->__set($key, $val);
    }

    /**
     * 
     * ArrayAccess: unset a key.
     * 
     * @param string $key The requested key.
     * 
     */
    public function offsetUnset($key): void {

        $this->__unset($key);
    }

    /**
     * 
     * Countable: how many keys are there?
     * 
     */
    public function count(): int {

        return count($this->data);
    }

    public function getIterator(): \ArrayIterator {

        return new \ArrayIterator($this->data + $this->related_data + $this->non_table_col_and_non_related_data);
    }

    //Magic Methods

    /**
     * 
     * Gets a data value.
     * 
     * @param string $key The requested data key.
     * 
     * @return mixed The data value.
     * 
     */
    public function __get($key) {

        if ( array_key_exists($key, $this->data) ) {
            
            return $this->data[$key];
            
        } elseif ( array_key_exists($key, $this->related_data) ) {

            return $this->related_data[$key];
            
        } elseif ( array_key_exists($key, $this->non_table_col_and_non_related_data) ) { 
            
            return $this->non_table_col_and_non_related_data[$key];
            
        } elseif ( 
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getTableColNames()) 
        ) {
            //$key is a valid db column in the db table assoiciated with this 
            //model's record. Initialize it to a null value since it has not 
            //yet been set.
            $this->data[$key] = null;
            
            return $this->data[$key];
            
        } elseif( 
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getRelationNames()) 
        ) {
            //$key is a valid relation name in the model for this record but the 
            //related data needs to be loaded for this particular record.
            $this->getModel()->loadRelationshipData($key, $this, true, true);
            
            //return loaded data
            return $this->related_data[$key];
            
        } else {

            //$key is not a valid db column name or relation name.
            $msg = sprintf("Property '%s' does not exist in ", $key) 
                   . get_class($this) . PHP_EOL . $this->__toString();
            
            throw new NoSuchPropertyForRecordException($msg);
        }
    }

    /**
     * 
     * Does a certain key exist in the data?
     * 
     * Note that this is slightly different from normal PHP isset(); it will
     * say the key is set, even if the key value is null or otherwise empty.
     * 
     * @param string $key The requested data key.
     * 
     */
    public function __isset($key): bool {
        
        try { $this->$key;  } //access the property first to make sure the data is loaded
        catch ( \Exception $exception ) {  } //do nothing if exception was thrown
        
        return array_key_exists($key, $this->data) 
            || array_key_exists($key, $this->related_data)
            || array_key_exists($key, $this->non_table_col_and_non_related_data);
    }

    /**
     * 
     * Sets a key value.
     * 
     * @param string $key The requested data key.
     * 
     * @param mixed $val The value to set the data to.
     * 
     */
    public function __set($key, $val): void {
        
        if ( 
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getTableColNames()) 
        ) {
            //$key is a valid db column in the db table assoiciated with this 
            //model's record.
            $this->data[$key] = $val;
            
        } elseif( 
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getRelationNames()) 
        ) {
            //$key is a valid relation name in the model for this record.
            $this->related_data[$key] = $val;
            
        } else {

            $this->non_table_col_and_non_related_data[$key] = $val;
        }
    }

    /**
     * 
     * Removes a key and its value in the data.
     * 
     * @param string $key The requested data key.
     * 
     */
    public function __unset($key): void {
        
        if( array_key_exists($key, $this->initial_data) ) {
            
            unset($this->initial_data[$key]);
            $this->initial_data[$key] = null;
        }
        
        if( array_key_exists($key, $this->data) ) {
            
            unset($this->data[$key]);
            $this->data[$key] = null;
        }
        
        if( array_key_exists($key, $this->related_data) ) {
            
            unset($this->related_data[$key]);
            $this->related_data[$key] = null;
        }
        
        if( array_key_exists($key, $this->non_table_col_and_non_related_data) ) {
            
            unset($this->non_table_col_and_non_related_data[$key]);
            $this->non_table_col_and_non_related_data[$key] = null;
        }
    }

    /**
     * 
     * Get the string representation of all the data and property 
     * (name & value pairs) for this record.
     * 
     * @return string string representation of all the data & property 
     *                (name & value pairs) for this record.
     * 
     */
    public function __toString(): string {
        
        return var_export($this->toArray(), true);
    }
}

