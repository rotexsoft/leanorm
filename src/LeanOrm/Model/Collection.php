<?php

namespace LeanOrm\Model;

/**
 * 
 * Represents a collection of \GDAO\Model\RecordInterface objects.
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2015, Rotimi Adegbamigbe
 */
class Collection implements \GDAO\Model\CollectionInterface
{
    /**
     *
     * @var \GDAO\Model
     * 
     */
    protected $_model;

    /**
     * 
     * @var array of \GDAO\Model\RecordInterface records
     * 
     */
    protected $_data = array();
    
    /**
     * 
     * \GDAO\Model\RecordsList is only used to enforce strict typing.
     * Ie. all the records in the collection are of type \GDAO\Model\RecordInterface
     * or any of its sub-classes.
     * 
     * $this->_data should be assigned the value of 
     * \GDAO\Model\RecordsList->toArray(). In this case $data->toArray().
     * 
     * @param \GDAO\Model\RecordsList $data list of instances of \GDAO\Model\RecordInterface
     * @param \GDAO\Model $model The model object that transfers data between the db and this collection.
     * @param array $extra_opts an array that may be used to pass initialization 
     *                          value(s) for protected and / or private properties
     *                          of this class
     */
    public function __construct(
        \GDAO\Model\RecordsList $data, \GDAO\Model $model, array $extra_opts=array()
    ) {
        $this->setModel($model);
        $this->_data = $data->toArray();
        
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
	public function deleteAll() {
        
        $this->_preDeleteAll();
        
        $result = true;
        
        foreach($this->_data as $record) {
            
            if( $record instanceof ReadOnlyRecord ) {
                
                $msg = "ERROR: Can't delete ReadOnlyRecord in Collection from"
                     . " the database in " . get_class($this) . '::' . __FUNCTION__ . '(...).'
                     . PHP_EOL .'Undeleted record' . var_export($record, true) . PHP_EOL;
                throw new \LeanOrm\CantDeleteReadOnlyRecordFromDBException($msg);
            }
        }
        
        try {

            if( $this->count() > 0 ) {

                $result = array();

                //generate list of keys of records in this collection
                //that were not successfully saved.

                foreach( $this->_data as $coll_key=>$record ) {

                    if( $record->delete() !== true ) {

                        //record still exists in the db table
                        //it wasn't successfully deleted.
                        $result[] = $coll_key;
                    }
                }
            }

        }  catch(\Exception $e) {

            throw $e;
        }
        
        $this->_postDeleteAll();
        
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
	public function getColVals($col) {
        
        $list = array();
        
        foreach ($this->_data as $key => $record) {
            
            $list[$key] = $record->$col;
        }
        
        return $list;
    }
    
    /**
     * 
     * Returns all the keys for this collection.
     * 
     * @return array
     * 
     */
    public function getKeys() {
        
        return array_keys($this->_data);
    }
    
    /**
     * 
     * Returns the model from which the data originates.
     * 
     * @return \GDAO\Model The origin model object.
     * 
     */
	public function getModel() {
        
        return $this->_model;
    }
    
    /**
     * 
     * Are there any records in the collection?
     * 
     * @return bool True if empty, false if not.
     * 
     */
	public function isEmpty() {
        
        return empty($this->_data);
    }
    
    /**
     * 
     * Load the collection with a list of records.
     * 
     * \GDAO\Model\RecordsList is used instead of an array because
     * \GDAO\Model\RecordsList can only contain instances of \GDAO\Model\RecordInterface
     * or its descendants. We only ever want instances of \GDAO\Model\RecordInterface or
     * its descendants inside a collection.
     * 
     * @param \GDAO\Model\RecordsList $data_2_load
     * 
     * @return void
     * 
     */
	public function loadData(\GDAO\Model\RecordsList $data_2_load){
        
        $this->_data = $data_2_load->getData();
    }
    
    
    /**
     * 
     * Removes all records from the collection but **does not** delete them
     * from the database.
     * 
     * @return void
     * 
     */
	public function removeAll() {
        
        $keys =  array_keys($this->_data);
        
        foreach( $keys as $key ) {
            
            $this->_data[$key] = null;
            unset($this->_data[$key]);
        }
        
        $this->_data = array();
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
    public function saveAll($group_inserts_together=false) {
        
        $this->_preSaveAll();
        
        $result = true;
        $keys_4_unsuccessfully_saved_records = array();
        
        if ( $group_inserts_together ) {
            
            $data_2_save_4_new_records = array();

            foreach ( $this->_data as $key => $record ) {

                if( $record instanceof ReadOnlyRecord ) {

                    $msg = "ERROR: Can't save ReadOnlyRecord in Collection to"
                         . " the database in " . get_class($this) . '::' . __FUNCTION__ . '(...).'
                         . PHP_EOL .'Undeleted record' . var_export($record, true) . PHP_EOL;
                    throw new \LeanOrm\CantDeleteReadOnlyRecordFromDBException($msg);
                }
                
                if( $record->isNew()) {
                    
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
                count($data_2_save_4_new_records) > 0
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
            
            foreach ( $this->_data as $key=>$record ) {
                
                if( $record->save() === false ) {
                    
                    $keys_4_unsuccessfully_saved_records[] = $key;
                }
            }
        }
        
        if( count($keys_4_unsuccessfully_saved_records) > 0 ) {
            
            $result = $keys_4_unsuccessfully_saved_records;
        }
        
        $this->_postSaveAll();

        return $result;
    }
    
    /**
     * 
     * Injects the model from which the data originates.
     * 
     * @param \GDAO\Model $model The origin model object.
     * 
     * @return void
     * 
     */
	public function setModel(\GDAO\Model $model) {
        
        $this->_model = $model;
    }
    
    /**
     * 
     * Returns an array representation of an instance of this class.
     * 
     * @return array an array representation of an instance of this class.
     * 
     */
    public function toArray() {

        return get_object_vars($this);
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
     * @return bool
     * 
     */
    public function offsetExists($key) {
        
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
     * ArrayAccess: set a key value; appends to the array when using []
     * notation.
     *  
     * @param string $key The requested key.
     * 
     * @param \GDAO\Model\RecordInterface $val The value to set it to.
     * 
     * @return void
     * 
     * @throws \GDAO\Model\CollectionCanOnlyContainGDAORecordsException
     * 
     */
    public function offsetSet($key, $val) {

        if( !($val instanceof RecordInterface) ) {
            
            $msg = "ERROR: Only instances of \\GDAO\\Model\\RecordInterface or its"
                   . " sub-classes can be added to a Collection. You tried to"
                   . " insert the following item: " 
                   . PHP_EOL . var_export($val, true) . PHP_EOL;
            
            throw new \GDAO\Model\CollectionCanOnlyContainGDAORecordsException($msg);
        }
        
        if ($key === null) {
            
            //support for $this[] = $record; syntax
            
            $key = $this->count();
            
            if (! $key) {
                
                $key = 0;
            }
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
     * @return void
     * 
     */
    public function offsetUnset($key) {
        
        $this->__unset($key);
    }

    /**
     * 
     * Countable: how many keys are there?
     * 
     * @return int
     * 
     */
    public function count() {
        
        return count($this->_data);
    }

    /**
     * 
     * IteratorAggregate: returns an external iterator for this collection.
     * 
     * @return \Iterator an Iterator eg. an instance of \ArrayIterator
     * 
     */
    public function getIterator() {
        
        return new \ArrayIterator($this->_data);
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
     * @return \GDAO\Model\RecordInterface
     * 
     */
    public function __get($key) {
        
        if (array_key_exists($key, $this->_data)) {

            return $this->_data[$key];
            
        } else {

            $msg = "ERROR: Item with key '$key' does not exist in " 
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
     * @return void
     * 
     */
    public function __isset($key) {
        
        return array_key_exists($key, $this->_data);
    }

    /**
     * 
     * Set a key value.
     * 
     * 
     * @param string $key The requested key.
     * @param \GDAO\Model\RecordInterface $val The value to set it to.
     * 
     * @return void
     *  
     */
    public function __set($key, \GDAO\Model\RecordInterface $val) {
       
        // set the value
        $this->_data[$key] = $val;
    }

    /**
     * 
     * Returns a string representation of an instance of this class.
     * 
     * @return array a string representation of an instance of this class.
     * 
     */
    public function __toString() {
        
        return var_export($this->toArray(), true);
    }

    /**
     * 
     * Removes a record with the specified key from the collection.
     * 
     * @param string $key The requested data key.
     * 
     * @return void
     * 
     */
    public function __unset($key) {
        
        unset($this->_data[$key]);
    }
    
    //Hooks
    
    /**
     * 
     * User-defined pre-delete logic.
     * 
     * Implementers of this class should add a call to this method as the 
     * first line of code in their implementation of $this->deleteAll()
     * 
     * @return void
     * 
     */
    public function _preDeleteAll() { }
    
    /**
     * 
     * User-defined post-delete logic.
     * 
     * Implementers of this class should add a call to this method as the 
     * last line of code in their implementation of $this->deleteAll()
     * 
     * @return void
     * 
     */
    public function _postDeleteAll() { }
    
    /**
     * 
     * User-defined pre-save logic for the collection.
     * 
     * Implementers of this class should add a call to this method as the 
     * first line of code in their implementation of $this->save(...)
     * 
     * @return void
     * 
     */
    public function _preSaveAll() { }
    
    /**
     * 
     * User-defined post-save logic for the collection.
     * 
     * Implementers of this class should add a call to this method as the 
     * last line of code in their implementation of $this->save(...)
     * 
     * @return void
     * 
     */
    public function _postSaveAll() { }
}
