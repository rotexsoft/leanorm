<?php
declare(strict_types=1);
namespace LeanOrm\Model;

use GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException;

/**
 * This is a record class that is lighter weight than \LeanOrm\Model\Record.
 * It does not have an _initial_data array for tracking changes made to a record
 * and it does not allow changing the values of a record's fields once the data
 * has been retreived from the database. It is intended for scenarios where you
 * are only reading data from the database for display purposes and do not plan
 * to update or delete the data. You cannot save new records to the database via
 * instances of this class.
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2022, Rotexsoft
 */
class ReadOnlyRecord implements \GDAO\Model\RecordInterface
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
     * Holds relationship data retrieved based on definitions in the array below.
     * \GDAO\Model::$relations
     * 
     */
    protected array $related_data = [];

    /**
     *
     * The model object that saves and reads data to and from the db on behalf 
     * of this record.
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
     */
    public function __construct(array $data, \GDAO\Model $model) {
        
        $this->setModel($model);
        $this->loadData($data);
    }
    
    public function __destruct() {

        //print "Destroying Record with Primary key Value: " . $this->getPrimaryVal() . "<br>";

        unset($this->data);
        unset($this->related_data);
        unset($this->non_table_col_and_non_related_data);

        //Don't unset $this->_model, it may still be referenced by other 
        //Record and / or Collection objects.
    }
    
    protected function _throwNotSupportedException($function_name): void {
        
        $msg = "ERROR: ". get_class($this) . '::' . $function_name . '(...)' 
             . " is not supported in a ReadOnly Record. ";

        throw new \GDAO\Model\RecordOperationNotSupportedException($msg);
    }

    /**
     * 
     * Not Supported, not overridable.
     * 
     */
    public final function delete($set_record_objects_data_to_empty_array=false): bool{
        
        $this->_throwNotSupportedException(__FUNCTION__);
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
     * Not Supported, not overridable.
     * @return mixed[]
     */
    public final function getInitialData():array {
        
        return [];
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
     * Not Supported, not overridable.
     * @return mixed[]
     */
    public final function &getInitialDataByRef(): array {
        
        return [];
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
     * behalf of this record.
     */
    public function getModel(): \GDAO\Model {
        
        return $this->model;
    }
    
    /**
     * 
     * @return string name of the primary-key column of the db table this record belongs to.
     * 
     */
    public function getPrimaryCol(): string {

        return $this->model->getPrimaryColName();
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
     * 
     * Not overridable.
     *  
     */
    public final function isChanged($col = null): ?bool {
        
        return false;
    }
    
    /**
     * 
     * Cannot be a new record, not overridable.
     * 
     */
    public final function isNew(): bool {
        
        return false;
    }
    
    /**
     * 
     * This method partially or completely overwrites pre-existing data and 
     * replaces it with the new data.
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
                
            } else if ($data_2_load instanceof \GDAO\Model\RecordInterface) {

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
        }// else if ( is_array($cols_2_load) && count($cols_2_load) > 0 )
        
        return $this;
    }

    /**
     * 
     * Not Supported, not overridable.
     * 
     */
    public final function markAsNew(): self {
        
        return $this;
    }
    
    /**
     * 
     * Not Supported, not overridable.
     * 
     */
    public final function markAsNotNew(): self {
        
        return $this;
    }
    
    /**
     * 
     * Not Supported, not overridable.
     * 
     */
    public final function setStateToNew(): self {
        
        return $this;
    }

    /**
     * 
     * Not Supported, not overridable.
     * 
     */
    public final function save($data_2_save = null): ?bool {
        
        return null;
    }
    
    /**
     * 
     * Not Supported, not overridable.
     * 
     */
    public final function saveInTransaction($data_2_save = null): ?bool {
        
        return null;
    }
    
    /**
     * 
     * Set the \GDAO\Model object for this record.
     * 
     * @param \GDAO\Model $model
     */
    public function setModel(\GDAO\Model $model): self{
        
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
    public final function offsetSet($key, $val): void {

        $this->__set($key, $val);
    }

    /**
     * 
     * ArrayAccess: unset a key.
     * 
     * @param string $key The requested key.
     * 
     */
    public final function offsetUnset($key): void {

        $this->__unset($key);
    }

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
            
        } else if ( array_key_exists($key, $this->related_data) ) {

            return $this->related_data[$key];
            
        } else if ( array_key_exists($key, $this->non_table_col_and_non_related_data) ) { 
            
            return $this->non_table_col_and_non_related_data[$key];
            
        } else if ( 
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getTableColNames()) 
        ) {
            //$key is a valid db column in the db table assoiciated with this 
            //model's record. Initialize it to a null value since it has not 
            //yet been set.
            $this->data[$key] = null;
            
            return $this->data[$key];
            
        } else if( 
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
    public final function __set($key, $val): void {
        
        $this->_throwNotSupportedException(__FUNCTION__);
    }

    /**
     * 
     * Removes a key and its value in the data.
     * 
     * @param string $key The requested data key.
     * 
     */
    public final function __unset($key): void {
        
        $this->_throwNotSupportedException(__FUNCTION__);
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
