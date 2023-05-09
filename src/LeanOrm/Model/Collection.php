<?php
declare(strict_types=1);
namespace LeanOrm\Model;

/**
 * 
 * Represents a collection of \GDAO\Model\RecordInterface objects.
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2022, Rotexsoft
 */
class Collection implements \GDAO\Model\CollectionInterface
{
    protected \GDAO\Model $model;

    /**
     * 
     * @var \GDAO\Model\RecordInterface[] array of \GDAO\Model\RecordInterface records
     * 
     */
    protected array $data = [];
    
    /**
     * 
     * @param \GDAO\Model $model The model object that transfers data between the db and this collection.
     * @param \GDAO\Model\RecordInterface[] ...$data
     */
    public function __construct(
        \GDAO\Model $model, \GDAO\Model\RecordInterface ...$data
    ) {
        $this->setModel($model);
        $this->data = $data;
    }
    
    /**
     * 
     * Deletes each record in the collection from the database, but leaves the
     * record objects with their data inside the collection object.
     * 
     * Call $this->removeAll() to empty the collection of the record objects.
     * 
     * @return bool|array true if all records were successfully deleted or an
     *                    array of keys in the collection for the records that 
     *                    couldn't be successfully deleted. It's most likely a 
     *                    PDOException would be thrown if the deletion failed.
     * 
     * @throws \PDOException 
     * @throws \LeanOrm\CantDeleteReadOnlyRecordFromDBException
     * 
     */
    public function deleteAll(): bool|array {
        
        $this->preDeleteAll();
        
        $result = true;
        
        foreach($this->data as $record) {
            
            if( $record instanceof ReadOnlyRecord ) {
                
                $msg = "ERROR: Can't delete ReadOnlyRecord in Collection from"
                     . " the database in " . get_class($this) . '::' . __FUNCTION__ . '(...).'
                     . PHP_EOL .'Undeleted record' . var_export($record, true) . PHP_EOL;
                throw new \LeanOrm\CantDeleteReadOnlyRecordFromDBException($msg);
            }
        }

        if( $this->count() > 0 ) {

            $result = [];

            //generate list of keys of records in this collection
            //that were not successfully saved.

            foreach( $this->data as $key=>$record ) {
                
                $delete_result = $record->delete();

                if( !$delete_result && $delete_result !== null ) {

                    //record still exists in the db table
                    //it wasn't successfully deleted.
                    $result[] = $key;
                }
            }

            if( count($result) <= 0 ) {

                $result = true;
            }
        }
        
        $this->postDeleteAll();
        
        return $result;
    }
    
    /**
     * 
     * Returns an array of all values for a single column in the collection.
     *
     * @param string $col The column name to retrieve values for.
     *
     * @return array An array of key-value pairs where the key is the collection 
     *               element key, and the value is the column value for that
     *               element.
     * 
     */
    public function getColVals($col): array {
        
        $list = [];
        
        foreach ($this->data as $key => $record) {
            
            $list[$key] = $record->$col;
        }
        
        return $list;
    }
    
    /**
     * 
     * Returns all the keys for this collection.
     * @return int[]|string[]
     */
    public function getKeys(): array {
        
        return array_keys($this->data);
    }
    
    /**
     * 
     * Returns the model from which the data originates.
     * 
     * @return \GDAO\Model The origin model object.
     * 
     */
    public function getModel(): \GDAO\Model {
        
        return $this->model;
    }
    
    /**
     * 
     * Are there any records in the collection?
     * 
     * @return bool True if empty, false if not.
     * 
     */
    public function isEmpty(): bool {
        
        return $this->data === [];
    }
    
    /**
     * 
     * Load the collection with a list of records.
     * @param \GDAO\Model\RecordInterface[] ...$data_2_load
     */
    public function loadData(\GDAO\Model\RecordInterface ...$data_2_load): static {
        
        $this->data = $data_2_load;
        
        return $this;
    }
    
    
    /**
     * 
     * Removes all records from the collection but **does not** delete them
     * from the database.
     * 
     */
    public function removeAll(): static {
        
        $keys =  array_keys($this->data);
        
        foreach( $keys as $key ) {
            
            $this->data[$key] = null;
            unset($this->data[$key]);
        }
        
        $this->data = [];
        
        return $this;
    }

    /**
     * 
     * Saves all the records from this collection to the database one-by-one,
     * inserting or updating as needed. 
     * 
     * For better performance, it can gather all records for inserts together
     * and then perform a single insert of multiple rows with one sql operation.
     * 
     * Updates cannot be batched together (they must be performed one-by-one) 
     * because there seems to be no neat update equivalent for bulk inserts:
     * 
     * example bulk insert:
     * 
     *      INSERT INTO mytable
     *                 (id, title)
     *          VALUES ('1', 'Lord of the Rings'),
     *                 ('2', 'Harry Potter');
     * 
     * @param bool $group_inserts_together true to group all records to be 
     *                                     inserted together in order to perform 
     *                                     a single sql insert operation, false
     *                                     to perform one-by-one inserts.
     * 
     * @return bool|array true if all inserts and updates were successful or
     *                    return an array of keys in the collection for the 
     *                    records that couldn't be successfully inserted or
     *                    updated. It's most likely a PDOException would be
     *                    thrown if an insert or update fails.
     * 
     * @throws \PDOException
     * 
     */
    public function saveAll($group_inserts_together=false): bool|array {
        
        $this->preSaveAll($group_inserts_together);
        
        $result = true;
        $keys_4_unsuccessfully_saved_records = [];
        
        if ( $group_inserts_together ) {
            
            $data_2_save_4_new_records = [];
            
            /** @var \GDAO\Model\RecordInterface $record */
            foreach ( $this->data as $key => $record ) {

                $this->throwExceptionOnSaveOfReadOnlyRecord($record, __FUNCTION__);
                
                if( $record->isNew()) {
                    
                    // Let's make sure that the table name associated with the
                    // model this record was created with is the same as the 
                    // table name of the model this collection was created with
                    // because the bulk insert will be performed via the 
                    // insertMany method of the model this collection was 
                    // created with.
                    if(
                        $record->getModel()->getTableName() 
                        !== $this->getModel()->getTableName()
                    ) {
                        $record_class_name = get_class($record);
                        $record_table_name = $record->getModel()->getTableName();
                        
                        $collection_class_name = get_class($this);
                        $collection_table_name = $this->getModel()->getTableName();
                        
                        $method_name = __FUNCTION__;
                        
                        $msg = "ERROR: Can't save a record of type `{$record_class_name}`"
                            . " belonging to table `{$record_table_name}` in the database via the " 
                            . " `{$method_name}` method in a Collection of type `{$collection_class_name}`"
                            . " whose model is associated with the table `{$collection_table_name}` in the"
                            . " database. " . PHP_EOL .  get_class($this) . '::' . $method_name . '(...).'
                            . PHP_EOL .'Unsaved record' . var_export($record, true) . PHP_EOL;
                        throw new TableNameMismatchInCollectionSaveAllException($msg);
                    }
                    
                    //The record is new and must be inserted into the db.
                    //Get the data to insert, whilst preserving its 
                    //association with its key in this collection.
                    $data_2_save_4_new_records[$key] = $record->getData();
                    
                } else if( $record->save() === false ) {

                    //The record is not new, but the attempt to update it failed.
                    //Store its key in this collection into the array of keys
                    //of records that could not be successfully saved.
                    $keys_4_unsuccessfully_saved_records[] = $key;
                }
            }
            
            //Try bulk insertion of new records
            if( 
                $data_2_save_4_new_records !== []
                && !$this->getModel()->insertMany($data_2_save_4_new_records) 
            ) {
                //bulk insert failed, none of the new records got saved
                //gather all their keys in this collection and add them
                //to the keys to be returned.
                $keys_4_unsuccessfully_saved_records = array_merge(
                                        $keys_4_unsuccessfully_saved_records, 
                                        array_keys($data_2_save_4_new_records)
                                    );
            }
        } else {
            
            foreach ( $this->data as $key=>$record ) {
                
                $this->throwExceptionOnSaveOfReadOnlyRecord($record, __FUNCTION__);
                
                if( $record->save() === false ) {
                    
                    $keys_4_unsuccessfully_saved_records[] = $key;
                }
            }
        }
        
        if( $keys_4_unsuccessfully_saved_records !== [] ) {
            
            $result = $keys_4_unsuccessfully_saved_records;
        }
        
        $this->postSaveAll($result, $group_inserts_together);

        return $result;
    }
    
    protected function throwExceptionOnSaveOfReadOnlyRecord(
        \GDAO\Model\RecordInterface $record, string $calling_function
    ): void {
        
        if( $record instanceof ReadOnlyRecord ) {

            $msg = "ERROR: Can't save ReadOnlyRecord in Collection to"
                 . " the database in " . get_class($this) . '::' . $calling_function . '(...).'
                 . PHP_EOL .'Undeleted record' . var_export($record, true) . PHP_EOL;
            throw new \LeanOrm\CantSaveReadOnlyRecordException($msg);
        }
    }
    
    /**
     * 
     * Injects the model from which the data originates.
     * 
     * @param \GDAO\Model $model The origin model object.
     * 
     * 
     */
    public function setModel(\GDAO\Model $model): static {
        
        $this->model = $model;
        
        return $this;
    }
    
    /**
     * 
     * Returns an array representation of an instance of this class.
     * 
     * @return array an array representation of an instance of this class.
     * 
     */
    public function toArray(): array {

        $result = [];
        
        foreach ($this as $key=>$record) {
            
            $result[$key] = $record->toArray();
        }
        
        return $result;
    }
    
    /////////////////////
    // Interface Methods
    /////////////////////
    
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
    public function offsetGet($key): \GDAO\Model\RecordInterface {
        
        return $this->__get($key);
    }

    /**
     * 
     * ArrayAccess: set a key value; appends to the array when using []
     * notation.
     *  
     * @param string $key The requested key.
     * 
     * @param \GDAO\Model\RecordInterface $val The value to set it to.
     * 
     * 
     * @throws \GDAO\Model\CollectionCanOnlyContainGDAORecordsException
     * 
     */
    public function offsetSet(mixed $key, mixed $val): void {
        
        if( !($val instanceof \GDAO\Model\RecordInterface) ) {
            
            $msg = "ERROR: Only instances of " . \GDAO\Model\RecordInterface::class . " or its"
                   . " sub-classes can be added to a Collection. You tried to"
                   . " insert the following item: " 
                   . PHP_EOL . var_export($val, true) . PHP_EOL;
            
            throw new \GDAO\Model\CollectionCanOnlyContainGDAORecordsException($msg);
        }
        
        if ($key === null) {
            
            //support for $this[] = $record; syntax
            
            $key = $this->count();
        }
        
        $this->__set($key, $val);
    }

    /**
     * 
     * ArrayAccess: unset a key. 
     * Removes a record with the specified key from the collection.
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

    /**
     * 
     * IteratorAggregate: returns an external iterator for this collection.
     * 
     * @return \ArrayIterator an Iterator eg. an instance of \ArrayIterator
     * 
     */
    public function getIterator(): \ArrayIterator {
        
        return new \ArrayIterator($this->data);
    }

    /////////////////////
    // Magic Methods
    /////////////////////
    
    /**
     * 
     * Returns a record from the collection based on its key value.
     * 
     * @param int|string $key The sequential or associative key value for the
     *                        record.
     * 
     */
    public function __get($key): \GDAO\Model\RecordInterface {
        
        if (array_key_exists($key, $this->data)) {

            return $this->data[$key];
            
        } else {

            $msg = sprintf("ERROR: Item with key '%s' does not exist in ", $key) 
                   . get_class($this) .'.'. PHP_EOL . $this->__toString();
            
            throw new \GDAO\Model\ItemNotFoundInCollectionException($msg);
        }
    }

    /**
     * 
     * Does a certain key exist in the data?
     * 
     * @param string $key The requested data key.
     *  
     */
    public function __isset($key): bool {
        
        return array_key_exists($key, $this->data);
    }

    /**
     * 
     * Set a key value.
     * 
     * @param string $key The requested key.
     * @param \GDAO\Model\RecordInterface $val The value to set it to.
     *   
     */
    public function __set($key, \GDAO\Model\RecordInterface $val): void {
        
        // set the value
        $this->data[$key] = $val;
    }

    /**
     * 
     * Returns a string representation of an instance of this class.
     * 
     * @return string a string representation of an instance of this class.
     * 
     */
    public function __toString(): string {
        
        return var_export($this->toArray(), true);
    }

    /**
     * 
     * Removes a record with the specified key from the collection.
     * 
     * @param string $key The requested data key.
     * 
     */
    public function __unset($key): void {
        
        unset($this->data[$key]);
    }
    
    //Hooks
    
    /**
     * {@inheritDoc}
     */
    public function preDeleteAll(): void { }
    
    /**
     * {@inheritDoc}
     */
    public function postDeleteAll(): void { }
    
    /**
     * {@inheritDoc}
     */
    public function preSaveAll(bool $group_inserts_together=false): void { }
    
    /**
     * {@inheritDoc}
     */
    public function postSaveAll($save_all_result, bool $group_inserts_together=false): void { }
}
